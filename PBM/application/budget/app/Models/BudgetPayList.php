<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetPayList extends Model
{
    use HasFactory;

    protected $table = 'budget_set_pay_list';
    protected $primaryKey = 'budget_pay_id';
    public $timestamps = false;
    protected $guarded = [];
}
