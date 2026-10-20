<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetPlan extends Model
{
    use HasFactory;

    protected $table = 'budget_set_plan';
    protected $primaryKey = 'budget_plan_code';
    public $timestamps = false;
    protected $guarded = [];

    public function events(): HasMany
    {
        return $this->hasMany(BudgetEvent::class, 'budget_plan_code', 'budget_plan_code');
    }
}
