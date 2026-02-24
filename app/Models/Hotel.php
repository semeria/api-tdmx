<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Hotel extends Model
{
    protected $table = 'hotels';
    protected $casts = [
        'services' => 'array',
        'images' => 'array',
        'google_maps' => 'array'
    ];

    protected $appends = ['amenities_list'];

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

        static::creating(function ($hotel) {
            $hotel->slug = Str::slug($hotel->name);
        });

        static::saving(function ($hotel) {
            $hotel->slug = Str::slug($hotel->name);
        });
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'hotel_team');
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

    protected function principalImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // 1. Verificamos si existe el campo images y no es nulo
                if (empty($this->images)) {
                    return null; // O una URL de imagen por defecto ('/img/placeholder.jpg')
                }

                // 2. Intentamos acceder a la estructura images['principal']
                // Como 'images' ya es un array (gracias al cast), accedemos directamente.
                // Asumimos que 'principal' es un array y queremos el primer elemento o su campo 'url'

                $images = $this->images;

                // Opción A: Si 'principal' es un solo objeto con campo 'url'
                // return $images['principal']['url'] ?? null;

                // Opción B: Si 'principal' es un array de objetos (ej. [{url: '...'}, {url: '...'}])
                // y quieres la primera imagen de ese grupo:
                if (isset($images['principal']) && is_array($images['principal']) && count($images['principal']) > 0) {
                    // Ajusta esto según si tu estructura es ['url' => '...'] o [{'url' => '...'}]
                    // Si es un array de objetos:
                    return $images['principal'][0]['url'] ?? null;

                    // Si 'principal' ya es el objeto directo:
                    // return $images['principal']['url'] ?? null;
                }

                return null;
            }
        );
    }
}
