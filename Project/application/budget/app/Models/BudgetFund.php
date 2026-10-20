<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetFund extends Model
{
    use HasFactory;

    protected $table = 'budget_set_fund';
    protected $primaryKey = 'budget_fund_id';
    public $timestamps = false;
    protected $guarded = [];
}
