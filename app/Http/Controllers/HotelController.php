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
                    'principal' => $hotel->principal_image_url, //Aqui va cargar la imagen principal
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
                'amenities' => $hotel->amenities_list,

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
