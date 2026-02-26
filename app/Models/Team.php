<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperTeam
 */
class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'table_number',
        'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    /**
     * Get the display name for this team (name, table number, or both).
     */
    public function displayName(): string
    {
        if ($this->name && $this->table_number) {
            return $this->name;
        }

        return $this->name ?? 'Table '.$this->table_number;
    }
}
