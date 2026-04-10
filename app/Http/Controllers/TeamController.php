<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Team;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = Team::with(['hotels', 'tours'])->get();
        $formatted = $teams->map(function ($team) {
            return [
                'id' => $team->id,
                'order' => $team->order,
                'name' => $team->name,
                'role' => $team->role,
                'bio' => $team->bio,
                'image_url' => $team->image_url,
                'talents' => $team->talents,
                'hotel_ids' => $team->hotels->pluck('id'),
                'tour_ids' => $team->tours->pluck('id'),
                'hotels' => $team->hotels,
                'tours' => $team->tours,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validación recomendada
            $request->validate([
                'name' => 'required|string',
                'order' => 'required|integer',
                'role' => 'required|string',
                'bio' => 'required|string',
                'image_url' => 'nullable|string',
                'talents' => 'nullable|array',
                'hotel_ids' => 'nullable|array',
                'tour_ids' => 'nullable|array',
                'hotel_ids.*' => 'exists:hotels,id',
                'tour_ids.*' => 'exists:tours,id',
            ]);

            $team = Team::create([
                'name' => $request->name,
                'order' => $request->order,
                'role' => $request->role,
                'bio' => $request->bio,
                'image_url' => $request->image_url,
                'talents' => $request->talents,
            ]);

            if ($request->filled('hotel_ids')) {
                $team->hotels()->attach($request->hotel_ids);
            }

            if ($request->filled('tour_ids')) {
                $team->tours()->attach($request->tour_ids);
            }

            return response()->json([
                'team' => $team,
                'hotel_ids' => $team->hotels->pluck('id'),
                'tour_ids' => $team->tours->pluck('id'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al guardar el equipo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Carga el team con la relación 'hotels'
            $team = Team::with('hotels')->findOrFail($id);

            return response()->json([
                'name' => $team->name,
                'role' => $team->role,
                'bio' => $team->bio,
                'order' => $team->order,
                'image_url' => $team->image_url,
                'talents' => $team->talents,
                'hotel_ids' => $team->hotels->pluck('id'),
                'tour_ids' => $team->tours->pluck('id'),
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El miembro no existe.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validación (opcional pero recomendada)
            $request->validate([
                'name' => 'required|string',
                'order' => 'required|integer|unique:teams,order,' . $id,
                'role' => 'required|string',
                'bio' => 'required|string',
                'image_url' => 'nullable|string',
                'talents' => 'nullable|array',
                'hotel_ids' => 'nullable|array',
                'tour_ids' => 'nullable|array',
                'hotel_ids.*' => 'exists:hotels,id',
                'tour_ids.*' => 'exists:tours,id',
            ], [
                'order.unique' => 'El numero de orden ya existe, por favor elija otro.',
            ]);

            // Encuentra el team o lanza 404
            $team = Team::findOrFail($id);

            // Actualiza campos
            $team->update([
                'name' => $request->name,
                'order' => $request->order,
                'role' => $request->role,
                'bio' => $request->bio,
                'image_url' => $request->image_url,
                'talents' => $request->talents,
            ]);

            // Sincroniza hoteles si vienen en la petición
            if ($request->filled('hotel_ids')) {
                $team->hotels()->sync($request->hotel_ids);
            }

            if ($request->filled('tour_ids')) {
                $team->tours()->sync($request->tour_ids);
            }

            // Respuesta con los hoteles actualizados
            return response()->json([
                'team' => $team->load('hotels'), // ← carga hoteles directamente
                'hotel_ids' => $team->hotels->pluck('id'),
                'tour_ids' => $team->tours->pluck('id')
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El miembro no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el equipo',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Team::destroy($id);
        return response()->json('El miembro ha sido eliminado.');
    }

    public function reorder(Request $request)
    {
        // Validamos que 'ranks' sea un array y tenga el formato correcto
        $request->validate([
            'ranks' => 'required|array',
            'ranks.*.id' => 'required|exists:teams,id',
            'ranks.*.order' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->ranks as $rank) {
                Team::where('id', $rank['id'])->update([
                    'order' => $rank['order']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Orden actualizado correctamente',
                'status' => 'success'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error reordenando equipo: " . $e->getMessage());

            return response()->json([
                'message' => 'Error al procesar el reordenamiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
