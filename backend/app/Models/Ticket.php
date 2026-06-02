<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    // Pakai UUID bukan integer
    public $incrementing = false;
    protected $keyType = 'string';

    // Kolom yang boleh diisi
    protected $fillable = [
        'id',
        'raw_text',
        'lang_hint',
        'category',
        'urgency',
        'category_score',
        'urgency_score',
        'keywords',
        'status',
        'admin_note',
        'resolved_at',
    ];

    // Cast tipe data otomatis
    protected $casts = [
        'keywords'       => 'array',
        'category_score' => 'float',
        'urgency_score'  => 'float',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'resolved_at'    => 'datetime',
    ];

    // Generate UUID otomatis saat tiket dibuat
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}