<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelurahanDutySchedule extends Model
{
    protected $fillable = ['department_id', 'event_date', 'group_number', 'coordinator', 'members', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'group_number' => 'integer', 'members' => 'array'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    protected function eventDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value ? CarbonImmutable::parse($value)->toDateString() : null);
    }
}
