<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetActivity extends Model
{
    use HasFactory;

    protected $table = 'budget_set_activity';
    protected $primaryKey = 'budget_activity_code';
    public $timestamps = false;
    protected $guarded = [];

    public function event(): BelongsTo
    {
        return $this->belongsTo(BudgetEvent::class, 'budget_event_code', 'budget_event_code');
    }
}
