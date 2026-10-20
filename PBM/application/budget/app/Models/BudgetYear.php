<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetYear extends Model
{
    use HasFactory;

    protected $table = 'budget_set_year';
    protected $primaryKey = 'budget_year_id';
    public $timestamps = false;
    protected $guarded = [];
}
