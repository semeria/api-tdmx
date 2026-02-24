<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tour extends Model
{
    protected $table = 'tours';
    protected $casts = [
        'services' => 'array',
        'images' => 'array',
        'google_maps' => 'array'
    ];

    protected $fillable = [
        'name'
    ];

    public function destino()
    {
        return $this->belongsTo(Destinos::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tour) {
            $tour->slug = Str::slug($tour->name);
        });

        static::saving(function ($tour) {
            $tour->slug = Str::slug($tour->name);
        });
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'tour_team');
    }

    protected function principalImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->images)) {
                    return null;
                }
                $images = $this->images;

                if (isset($images['principal']) && is_array($images['principal']) && count($images['principal']) > 0) {

                    return $images['principal'][0]['url'] ?? null;
                }
                return null;
            }
        );
    }
}
