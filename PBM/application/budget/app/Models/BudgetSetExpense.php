<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetSetExpense extends Model
{
    use HasFactory;

    protected $table = 'budget_set_expenses';
    protected $primaryKey = 'budget_set_expenses_id';
    public $timestamps = false;
    protected $guarded = [];
}
