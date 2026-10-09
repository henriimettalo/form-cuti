<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyGroup extends Model
{
    protected $fillable = ['coordinator', 'members'];

    protected function casts(): array
    {
        return ['number' => 'integer', 'members' => 'array'];
    }

    public function roster(): array
    {
        return [
            'number' => $this->number,
            'coordinator' => $this->coordinator,
            'members' => $this->members,
        ];
    }
}
