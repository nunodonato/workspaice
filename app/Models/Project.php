<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    public $guarded = [];

    public $casts = [
        'files' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
