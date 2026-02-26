<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperEvent
 */
class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'ended_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class)->orderBy('sort_order');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('sort_order');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::query()->where('slug', $slug)->first() !== null) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return Str::limit($slug, 100, '');
    }
}
