<?php
/**
 * กรอบแผน (set_type) แยกตามปีงบประมาณ + ชนิดรายการลูก (ด้าน / SO)
 * ใช้กับหน้าความสอดคล้องโครงการ (PlanWise)
 */

if (!function_exists('kpi_type_year_norm')) {
	function kpi_type_year_norm(string $year): array
	{
		$year = trim($year);
		$digits = preg_replace('/\D+/', '', $year);
		$out = [];
		if ($year !== '') {
			$out[] = $year;
		}
		if ($digits !== '') {
			$out[] = $digits;
			$y2 = substr($digits, -2);
			if ($y2 !== '' && $y2 !== $digits) {
				$out[] = $y2;
			}
		}
		return array_values(array_unique($out));
	}
}

if (!function_exists('kpi_type_year_ensure_schema')) {
	function kpi_type_year_ensure_schema($db): void
	{
		static $done = false;
		if ($done || !($db instanceof mysqli)) {
			return;
		}
		$done = true;

		$ensure = static function ($db, string $table, string $col, string $def) {
			$esc = mysqli_real_escape_string($db, $col);
			$q = @mysqli_query($db, "SHOW COLUMNS FROM `{$table}` LIKE '{$esc}'");
			if ($q && mysqli_num_rows($q) === 0) {
				@mysqli_query($db, "ALTER TABLE `{$table}` ADD COLUMN {$def}");
			}
		};

		$ensure($db, 'set_type', 'kpi_year', "`kpi_year` varchar(10) DEFAULT NULL AFTER `id`");
		$ensure($db, 'set_type', 'child_kind', "`child_kind` varchar(10) NOT NULL DEFAULT 'side' AFTER `name`");
		$ensure($db, 'set_type', 'sort_order', "`sort_order` int(11) NOT NULL DEFAULT 0 AFTER `child_kind`");

		$ensure($db, 'set_side', 'kpi_year', "`kpi_year` varchar(10) DEFAULT NULL AFTER `id`");
		$ensure($db, 'set_side', 'code_type', "`code_type` int(11) DEFAULT NULL AFTER `kpi_year`");
		$ensure($db, 'set_side', 'sort_order', "`sort_order` int(11) NOT NULL DEFAULT 0 AFTER `name`");

		$ensure($db, 'set_so', 'kpi_year', "`kpi_year` varchar(10) DEFAULT NULL AFTER `id`");
		$ensure($db, 'set_so', 'code_type', "`code_type` int(11) DEFAULT NULL AFTER `kpi_year`");
		$ensure($db, 'set_so', 'sort_order', "`sort_order` int(11) NOT NULL DEFAULT 0 AFTER `name`");
	}
}

if (!function_exists('kpi_type_child_kind')) {
	function kpi_type_child_kind($typeRow): string
	{
		if (!is_array($typeRow)) {
			return 'side';
		}
		$kind = strtolower(trim((string) ($typeRow['child_kind'] ?? '')));
		return $kind === 'so' ? 'so' : 'side';
	}
}

if (!function_exists('kpi_type_year_sql_in')) {
	function kpi_type_year_sql_in($db, string $year): string
	{
		$parts = [];
		foreach (kpi_type_year_norm($year) as $y) {
			$parts[] = "'" . mysqli_real_escape_string($db, $y) . "'";
		}
		return $parts ? implode(',', $parts) : "''";
	}
}

if (!function_exists('kpi_type_list_by_year')) {
	/**
	 * @return list<array<string,mixed>>
	 */
	function kpi_type_list_by_year($db, string $year): array
	{
		if (!($db instanceof mysqli)) {
			return [];
		}
		if (function_exists('kpi_type_year_ensure_schema')) {
			kpi_type_year_ensure_schema($db);
		}
		$yearIn = kpi_type_year_sql_in($db, $year);
		$sql = "SELECT * FROM set_type
			WHERE status = '1'
			  AND kpi_year IN ({$yearIn})
			ORDER BY sort_order ASC, id ASC";
		$q = @mysqli_query($db, $sql);
		$rows = [];
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$rows[] = $r;
		}
		if ($rows) {
			return $rows;
		}
		// fallback: ประเภทที่ยังไม่ระบุปี
		$sql2 = "SELECT * FROM set_type
			WHERE status = '1'
			  AND (kpi_year IS NULL OR TRIM(kpi_year) = '')
			ORDER BY sort_order ASC, id ASC";
		$q2 = @mysqli_query($db, $sql2);
		while ($q2 && ($r = mysqli_fetch_assoc($q2))) {
			$rows[] = $r;
		}
		return $rows;
	}
}

if (!function_exists('kpi_type_items')) {
	/**
	 * รายการลูกของกรอบแผน: ด้าน (set_side) หรือ SO (set_so)
	 *
	 * @return list<array{id:string|int,name:string}>
	 */
	function kpi_type_items($db, int $typeId, string $year, string $kind = 'side'): array
	{
		if (!($db instanceof mysqli) || $typeId <= 0) {
			return [];
		}
		if (function_exists('kpi_type_year_ensure_schema')) {
			kpi_type_year_ensure_schema($db);
		}
		$kind = strtolower(trim($kind)) === 'so' ? 'so' : 'side';
		$table = $kind === 'so' ? 'set_so' : 'set_side';
		$yearIn = kpi_type_year_sql_in($db, $year);
		// ใช้เฉพาะ type ของปีที่เลือก — ไม่ดึง id ปีอื่นที่ชื่อซ้ำ
		$sql = "SELECT id, name FROM `{$table}`
			WHERE status = '1'
			  AND code_type = " . (int) $typeId . "
			  AND kpi_year IN ({$yearIn})
			ORDER BY sort_order ASC, id ASC";
		$q = @mysqli_query($db, $sql);
		$out = [];
		$seen = [];
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$id = (string) ($r['id'] ?? '');
			$name = trim((string) ($r['name'] ?? ''));
			if ($id === '' || isset($seen[$id])) {
				continue;
			}
			$seen[$id] = true;
			$out[] = ['id' => $id, 'name' => $name];
		}
		// fallback: รายการที่ยังไม่ระบุปี (ของ type นี้เท่านั้น)
		if (!$out) {
			$sql2 = "SELECT id, name FROM `{$table}`
				WHERE status = '1'
				  AND code_type = " . (int) $typeId . "
				  AND (kpi_year IS NULL OR TRIM(kpi_year) = '')
				ORDER BY sort_order ASC, id ASC";
			$q2 = @mysqli_query($db, $sql2);
			while ($q2 && ($r = mysqli_fetch_assoc($q2))) {
				$id = (string) ($r['id'] ?? '');
				$name = trim((string) ($r['name'] ?? ''));
				if ($id === '' || isset($seen[$id])) {
					continue;
				}
				$seen[$id] = true;
				$out[] = ['id' => $id, 'name' => $name];
			}
		}
		return $out;
	}
}
