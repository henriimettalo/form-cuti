<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class DutyHoliday extends Model
{
    protected $fillable = ['holiday_date', 'name'];

    protected function casts(): array
    {
        return ['holiday_date' => 'date'];
    }

    protected function holidayDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => CarbonImmutable::parse($value)->toDateString());
    }
}
