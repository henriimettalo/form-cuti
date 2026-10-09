<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CeremonySchedule extends Model
{
    public const TYPES = [
        'apel' => 'Apel / Upacara',
        'piket_loket' => 'Piket Loket',
    ];

    protected $fillable = [
        'title',
        'type',
        'event_date',
        'start_time',
        'location',
        'department_id',
        'leader',
        'notes',
        'created_by',
        'duty_group_id',
        'duty_roster',
        'duty_date',
        'rotation_start_department_id',
        'ceremony_group_number',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'duty_roster' => 'array',
            'duty_date' => 'date',
            'ceremony_group_number' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function rotationStartDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'rotation_start_department_id');
    }

    public function dutyGroup(): BelongsTo
    {
        return $this->belongsTo(DutyGroup::class);
    }

    protected function eventDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => CarbonImmutable::parse($value)->toDateString());
    }

    protected function dutyDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value ? CarbonImmutable::parse($value)->toDateString() : null);
    }

    public static function normalizeType(mixed $type): mixed
    {
        return $type === 'upacara' ? 'apel' : $type;
    }

    public function typeLabel(): string
    {
        return self::TYPES[self::normalizeType($this->type)];
    }
}
