<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetSource extends Model
{
    use HasFactory;

    protected $table = 'budget_set_source';
    protected $primaryKey = 'budget_source_id';
    public $timestamps = false;
    protected $guarded = [];
}
