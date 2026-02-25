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

    protected function amenitiesList(): Attribute
    {
        return Attribute::make(
            get: function () {
                // 1. Si services es null o está vacío, devolvemos array vacío
                if (empty($this->services)) {
                    return [];
                }

                // 2. Extraemos los IDs del JSON (igual que en tu controller)
                $ids = collect($this->services)->pluck('id')->filter();

                // 3. Hacemos la consulta (cacheable si quisieras optimizar más)
                return ServiciosSubcategoria::whereIn('id', $ids)
                    ->pluck('name')
                    ->values()
                    ->toArray();
            }
        );
    }

    protected function gallery(): Attribute
    {
        return Attribute::make(
            get: function () {
                $images = $this->images;

                if (empty($images) || !is_array($images)) {
                    return [];
                }

                return collect($images)
                    ->flatten(1)     // Combina los arrays de principal, secundaria y adicional
                    ->pluck('url')   // Saca solo la URL
                    ->filter()       // Quita nulos
                    ->values()       // Reindexa [0, 1, 2...]
                    ->toArray();     // Convierte a array nativo de PHP
            }
        );
    }
}
