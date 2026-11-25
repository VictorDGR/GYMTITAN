<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Canje extends Model
{
    protected $fillable = ['cliente_id', 'premio_id', 'user_id', 'puntos'];

    protected function casts(): array
    {
        return ['puntos' => 'integer'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function premio(): BelongsTo
    {
        return $this->belongsTo(Premio::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
