<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $guarded = [];
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        return self::first()->$key ?? $default;
    }

    public static function setSetting(string $key, mixed $value): void
    {
        self::first()->update([$key => $value]);
    }
}
