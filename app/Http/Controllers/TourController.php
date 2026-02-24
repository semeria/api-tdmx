<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tours = Tour::with('destino')
            ->select('id', 'name', 'destino_id', 'slug', 'images', 'active')
            ->get()
            ->map(function ($tour) {
                return [
                    'id' => $tour->id,
                    'name' => $tour->name,
                    'destino_id' => $tour->destino_id,
                    'destino' => $tour->destino?->name,
                    'slug' => $tour->slug,
                    'images' => $tour->images,
                    'active' => $tour->active,
                    'principal' => $tour->principal_image_url, //Aqui va cargar la imagen principal
                ];
            });
        return response()->json($tours);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $tour = new Tour();
        $tour->name = $request->name;
        $tour->address = $request->address;
        $tour->description = $request->description;
        $tour->services = $request->services;
        $tour->destino_id = $request->destino_id;
        $tour->price = $request->price;
        $tour->google_maps = $request->google_maps;
        $tour->reviews = $request->reviews;
        $tour->active = $request->active;
        $tour->save();

        return response()->json($tour);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $tour = Tour::findOrFail($id);
            return response()->json($tour);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El tour seleccionado no existe'
            ], 404);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $tour = Tour::findOrFail($id);
            $tour->name = $request->name;
            $tour->address = $request->address;
            $tour->description = $request->description;
            $tour->services = $request->services;
            $tour->images = $request->images;
            $tour->destino_id = $request->destino_id;
            $tour->price = $request->price;
            $tour->google_maps = $request->google_maps;
            $tour->active = $request->active;
            $tour->reviews = $request->reviews;
            $tour->save();

            return response()->json($tour);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El tour seleccionado no existe'
            ], 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Tour::destroy($id);
        return response()->json(['message' => 'El tour seleccionado no existe']);
    }

    public function getTourBySlug(string $slug) {
        $tour = Tour::where('slug', $slug)
            ->with('teams')
            ->firstOrFail();
        return response()->json($tour);
    }
}
