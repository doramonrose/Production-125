<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCategoryBudget extends Model
{
    use HasFactory;

    protected $table = 'budget_set_category_budget';
    protected $primaryKey = 'budget_category_budget_id';
    public $timestamps = false;
    protected $guarded = [];
}
