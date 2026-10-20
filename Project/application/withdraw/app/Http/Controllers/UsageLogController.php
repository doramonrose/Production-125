<?php

namespace App\Http\Controllers;

use App\Support\UsageLogDescriber;
use App\Support\UsageLogRecordContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UsageLogController extends Controller
{
    public function index(Request $request, UsageLogDescriber $describer, UsageLogRecordContext $records): View
    {
        $role = (string) $request->session()->get('pbm.system_role', '');
        abort_unless($role === 'admin', 403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'staff_id' => trim((string) $request->query('staff_id', '')),
            'method' => strtoupper(trim((string) $request->query('method', ''))),
            'role' => trim((string) $request->query('role', '')),
            'date_from' => $this->dateFilter((string) $request->query('date_from', '')),
            'date_to' => $this->dateFilter((string) $request->query('date_to', '')),
        ];

        $logs = null;
        $users = [];
        $ready = Schema::hasTable('system_action_logs');
        if ($ready) {
            $users = $this->userOptions();
            $query = DB::table('system_action_logs')->orderByDesc('id');
            if ($filters['q'] !== '') {
                $like = '%'.$filters['q'].'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('request_path', 'like', $like)
                        ->orWhere('route_name', 'like', $like)
                        ->orWhere('staff_id', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhere('exception_message', 'like', $like);
                    if (Schema::hasColumn('system_action_logs', 'staff_name')) {
                        $inner->orWhere('staff_name', 'like', $like);
                    }
                    if (Schema::hasColumn('system_action_logs', 'action_summary')) {
                        $inner->orWhere('action_summary', 'like', $like);
                    }
                });
            }
            if (in_array($filters['method'], ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $query->where('request_method', $filters['method']);
            }
            if ($filters['staff_id'] !== '') {
                $query->where('staff_id', $filters['staff_id']);
            }
            if (in_array($filters['role'], ['admin', 'member', 'viewer'], true)) {
                $query->where('system_role', $filters['role']);
            }
            if ($filters['date_from'] !== '') {
                $query->where('occurred_at', '>=', $filters['date_from'].' 00:00:00');
            }
            if ($filters['date_to'] !== '') {
                $query->where('occurred_at', '<=', $filters['date_to'].' 23:59:59');
            }

            $logs = $query->paginate(40)->withQueryString();
            $inspected = [];
            $withdrawIds = [];
            foreach ($logs as $row) {
                $inspected[(int) $row->id] = $records->inspectLog($row);
                if ($inspected[(int) $row->id]['id'] > 0) {
                    $withdrawIds[] = $inspected[(int) $row->id]['id'];
                }
            }
            $loaded = $records->load($withdrawIds);
            $staffNames = $this->staffNames($logs->getCollection()
                ->filter(fn ($row) => trim((string) ($row->staff_name ?? '')) === '')
                ->map(fn ($row) => trim((string) ($row->staff_id ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->all());
            $logs->getCollection()->transform(function ($row) use ($describer, $inspected, $loaded, $staffNames) {
                $detail = $inspected[(int) $row->id] ?? ['id' => 0, 'action' => '', 'type_label' => '', 'line' => '', 'scope' => ''];
                $record = $loaded[$detail['id']] ?? null;
                $typeLabel = (string) ($record['type_label'] ?? $detail['type_label']);
                $line = (string) ($record['line'] ?? $detail['line']);
                $row->display_action = $describer->describe(
                    (string) ($row->request_method ?? 'GET'),
                    $row->route_name ?? null,
                    (string) ($row->request_path ?? ''),
                    [],
                    [
                        'action' => $detail['action'],
                        'id' => $detail['id'],
                        'type_label' => $typeLabel,
                        'scope' => $detail['scope'],
                        'has_record' => $line !== '',
                    ]
                );
                $row->display_record = $line;
                $row->display_listed = $this->listedItems($row);
                $row->display_list_note = match (true) {
                    $row->display_listed === null && in_array((string) ($row->route_name ?? ''), ['withdraw.general.api.list', 'withdraw.salary.api.list'], true) && $line === '' => 'เป็นการเปิดหน้ารวม จึงไม่ได้ชี้รายการใดรายการหนึ่ง รายการที่กดเปิดจะถูกบันทึกแยกในแถวถัดไป',
                    in_array((string) ($row->route_name ?? ''), ['withdraw.general.api.budget-source-options', 'withdraw.salary.api.budget-source-options'], true) && $line === '' => 'เป็นการโหลดรายการงบประมาณทั้งหมดสำหรับช่องงบประมาณในฟอร์ม ไม่ได้เปิดงบประมาณรายการใดรายการหนึ่ง',
                    default => '',
                };
                $storedName = trim((string) ($row->staff_name ?? ''));
                $staffId = trim((string) ($row->staff_id ?? ''));
                $row->display_name = $storedName !== ''
                    ? $storedName
                    : ($staffNames[$staffId] ?? ($staffId !== '' ? $staffId : 'ไม่ทราบผู้ใช้'));

                return $row;
            });
        }

        return view('usage-log.index', [
            'title' => 'บันทึกการใช้งาน',
            'ready' => $ready,
            'filters' => $filters,
            'users' => $users,
            'logs' => $logs,
        ]);
    }

    /**
     * @param  list<string>  $staffIds
     * @return array<string, string>
     */
    private function staffNames(array $staffIds): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map(
            static fn ($id) => trim((string) $id),
            $staffIds
        ))));
        if ($staffIds === [] || ! Schema::hasTable('users')) {
            return [];
        }

        $rows = DB::table('users')
            ->where(function ($query) use ($staffIds) {
                $query->whereIn('user_id', $staffIds)
                    ->orWhereIn('staff_code', $staffIds);
            })
            ->get(['user_id', 'staff_code', 'name', 'name_th', 'name_en']);

        $names = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row->name_th ?? ''));
            if ($name === '') {
                $name = trim((string) ($row->name ?? ''));
            }
            if ($name === '') {
                $name = trim((string) ($row->name_en ?? ''));
            }
            if ($name === '' || preg_match('/^\d+$/', $name) === 1 || preg_match('/^PBM User\s+\d+$/', $name) === 1) {
                continue;
            }
            foreach ([$row->user_id, $row->staff_code] as $key) {
                $key = trim((string) $key);
                if ($key !== '') {
                    $names[$key] = $name;
                }
            }
        }

        return $names;
    }

    private function dateFilter(string $value): string
    {
        $value = trim($value);
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTime && $date->format('Y-m-d') === $value ? $value : '';
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function userOptions(): array
    {
        $ids = DB::table('system_action_logs')
            ->whereNotNull('staff_id')
            ->where('staff_id', '!=', '')
            ->distinct()
            ->pluck('staff_id')
            ->map(static fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $names = $this->staffNames($ids);
        $users = [];
        foreach ($ids as $id) {
            $users[] = [
                'id' => $id,
                'label' => isset($names[$id]) ? $names[$id].' ('.$id.')' : $id,
            ];
        }
        usort($users, static fn (array $left, array $right): int => strnatcasecmp($left['label'], $right['label']));

        return $users;
    }

    /**
     * @return list<string>|null
     */
    private function listedItems(object $log): ?array
    {
        if (! Schema::hasColumn('system_action_logs', 'listed_items') || $log->listed_items === null || $log->listed_items === '') {
            return null;
        }
        $decoded = json_decode((string) $log->listed_items, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : null;
    }
}
