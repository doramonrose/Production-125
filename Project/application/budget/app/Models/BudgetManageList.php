<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetManageList extends Model
{
    use HasFactory;

    protected $table = 'budget_manage_list';
    protected $primaryKey = 'budget_manage_list_id';
    public $timestamps = false;
    protected $guarded = [];

    public function budgetManage(): BelongsTo
    {
        return $this->belongsTo(BudgetManage::class, 'budget_manage_id', 'budget_manage_id');
    }
}
