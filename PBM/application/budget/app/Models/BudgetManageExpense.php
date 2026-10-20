<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetManageExpense extends Model
{
    use HasFactory;

    protected $table = 'budget_manage_expenses';
    protected $primaryKey = 'budget_expenses_id';
    public $timestamps = false;
    protected $guarded = [];

    public function budgetManage(): BelongsTo
    {
        return $this->belongsTo(BudgetManage::class, 'budget_manage_id', 'budget_manage_id');
    }

    public function subExpenses(): HasMany
    {
        return $this->hasMany(BudgetManageExpenseSub::class, 'budget_expenses_id', 'budget_expenses_id');
    }
}
