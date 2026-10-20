<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCategoryPay extends Model
{
    use HasFactory;

    protected $table = 'budget_set_category_pay';
    protected $primaryKey = 'budget_category_pay_id';
    public $timestamps = false;
    protected $guarded = [];
}
