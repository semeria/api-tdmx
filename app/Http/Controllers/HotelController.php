<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\ServiciosSubcategoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index()
    {
        $hoteles = Hotel::with('destino')
        ->select('id', 'name', 'destino_id', 'slug', 'images', 'active')
            ->get()
            ->map(function ($hotel) {
                return [
                    'id' => $hotel->id,
                    'name' => $hotel->name,
                    'destino_id' => $hotel->destino_id,
                    'destino' => $hotel->destino?->name,
                    'slug' => $hotel->slug,
                    'images' => $hotel->images,
                    'active' => $hotel->active,
                ];
            });

        return response()->json($hoteles);
    }

    public function store(Request $request)
    {
        $hotel = new Hotel();
        $hotel->name = $request->name;
        $hotel->address = $request->address;
        $hotel->description = $request->description;
        $hotel->services = $request->services;
        $hotel->destino_id = $request->destino_id;
        $hotel->google_maps = $request->google_maps;
        $hotel->price = $request->price;
        $hotel->active = $request->active;
        $hotel->save();
        return response()->json($hotel);
    }

    public function show(string $id)
    {
        try {
            // 1. Cargamos el hotel y su destino
            $hotel = Hotel::with('destino')->findOrFail($id);

            // 2. Procesamos el JSON de servicios
            $servicesCollection = collect($hotel->services);

            // A. Extraemos los IDs de las SUBCATEGORÍAS (el campo 'id' del JSON)
            // Esto nos dará algo como: [2, 1, 4, 6, 3]
            $subcategoriaIds = $servicesCollection->pluck('id')->filter();

            // B. Buscamos los nombres de esas subcategorías en la base de datos
            // Usamos whereIn con los IDs que acabamos de sacar
            $amenitiesList = ServiciosSubcategoria::whereIn('id', $subcategoriaIds)
                ->pluck('name') // Solo queremos el nombre
                ->values()      // Reindexamos para tener un array limpio
                ->toArray();

            // 3. Construimos la respuesta
            $data = [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'destino_id' => $hotel->destino_id,
                'destino' => $hotel->destino?->name,

                'address' => $hotel->address,
                'description' => $hotel->description,
                'price' => $hotel->price,
                'reviews' => $hotel->reviews,
                'google_maps' => $hotel->google_maps,
                'images' => $hotel->images,
                'services' => $hotel->services,

                // AQUÍ ESTÁ EL CAMBIO:
                // Devuelve un array simple de strings con los nombres de las subcategorías
                // Ejemplo: ["Wifi", "Toallas de playa", "Bar en la piscina"]
                'amenities' => $amenitiesList,

                'active' => $hotel->active,
            ];

            return response()->json($data);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El hotel seleccionado no existe'
            ], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $hotel = Hotel::findOrFail($id);
            $hotel->name = $request->name;
            $hotel->address = $request->address;
            $hotel->description = $request->description;
            $hotel->services = $request->services;
            $hotel->images = $request->images;
            $hotel->destino_id = $request->destino_id;
            $hotel->price = $request->price;
            $hotel->google_maps = $request->google_maps;
            $hotel->active = $request->active;
            $hotel->reviews = $request->reviews;
            $hotel->save();
            return response()->json($hotel);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El hotel seleccionado no existe'
            ], 404);
        }
    }

    public function destroy(string $id)
    {
        Hotel::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    public function getHotelBySlug(string $slug) {
        $hotel = Hotel::where('slug', $slug)
            ->with('teams')
            ->firstOrFail();
        return response()->json($hotel);
    }
}
