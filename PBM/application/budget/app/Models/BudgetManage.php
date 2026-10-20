<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetManage extends Model
{
    use HasFactory;

    protected $table = 'budget_manage';
    protected $primaryKey = 'budget_manage_id';
    public $timestamps = false;
    protected $guarded = [];

    public function manageLists(): HasMany
    {
        return $this->hasMany(BudgetManageList::class, 'budget_manage_id', 'budget_manage_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(BudgetManageExpense::class, 'budget_manage_id', 'budget_manage_id');
    }
}
