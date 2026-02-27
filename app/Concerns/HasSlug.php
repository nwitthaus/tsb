<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
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
