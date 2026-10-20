<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCost extends Model
{
    use HasFactory;

    protected $table = 'budget_set_cost';

    protected $primaryKey = 'budget_cost_id';

    public $timestamps = false;

    protected $guarded = [];
}
