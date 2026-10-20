<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetManageExpenseSub extends Model
{
    use HasFactory;

    protected $table = 'budget_manage_expenses_sub';
    protected $primaryKey = 'budget_expenses_sub_id';
    public $timestamps = false;
    protected $guarded = [];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(BudgetManageExpense::class, 'budget_expenses_id', 'budget_expenses_id');
    }
}
