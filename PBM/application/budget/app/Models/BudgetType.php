<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetType extends Model
{
    use HasFactory;

    protected $table = 'budget_set_type';
    protected $primaryKey = 'budget_type_id';
    public $timestamps = false;
    protected $guarded = [];
}
