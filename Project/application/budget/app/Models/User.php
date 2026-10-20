<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_id',
        'name_th',
        'name_en',
        'staff_code',
        'position',
        'department',
        'image_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'user_id';
    }

    public function menuPermissions(): HasMany
    {
        return $this->hasMany(UserMenuPermission::class, 'user_id', 'user_id');
    }

    public function canAccessMenu(string $menuKey): bool
    {
        $sessionRole = (string) session('pbm.system_role', '');
        $isPlatformAdmin = (bool) session('pbm.is_platform_admin', false);

        if ($isPlatformAdmin || $sessionRole === 'admin') {
            return true;
        }

        if ($sessionRole === 'member') {
            return in_array($menuKey, ['dashboard', 'budget-manage'], true);
        }

        if ($sessionRole === 'viewer') {
            return $menuKey === 'dashboard';
        }

        $granted = $this->menuPermissions()->pluck('menu_key');

        if ($granted->isEmpty()) {
            return in_array($menuKey, ['dashboard', 'profile'], true);
        }

        // งาน + กิจกรรมรวมเป็นหน้าเดียว: สิทธิ์เดิม activity หรือ event ใช้ได้ทั้งคู่
        if ($menuKey === 'budget-settings-event' || $menuKey === 'budget-settings-activity') {
            return $granted->contains('budget-settings-event')
                || $granted->contains('budget-settings-activity');
        }

        return $granted->contains($menuKey);
    }
}
