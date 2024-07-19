<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $casts = [
        'input' => 'json',
    ];

    public function project(): belongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function images(): hasMany
    {
        return $this->hasMany(Image::class);
    }
}
