<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetEvent extends Model
{
    use HasFactory;

    protected $table = 'budget_set_event';
    protected $primaryKey = 'budget_event_code';
    public $timestamps = false;
    protected $guarded = [];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_code', 'budget_plan_code');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BudgetActivity::class, 'budget_event_code', 'budget_event_code');
    }
}
