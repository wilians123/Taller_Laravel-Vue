<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tarea;
use App\Models\Usuario;

class TareaController extends Controller
{
    /**
     * Listar todas las tareas con información del usuario asignado
     */
    public function index()
    {
        $tareas = Tarea::with('usuario:id,nombre,email')
            ->select('id', 'titulo', 'descripcion', 'estado', 'prioridad', 'fecha_vencimiento', 'usuario_id', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tareas);
    }

    /**
     * Crear nueva tarea
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:pendiente,en_progreso,completada',
            'prioridad' => 'required|in:baja,media,alta',
            'fecha_vencimiento' => 'required|date|after_or_equal:today',
            'usuario_id' => 'required|exists:usuarios,id'
        ]);

        $tarea = Tarea::create($validated);

        // Cargar la relación para devolver la respuesta completa
        $tarea->load('usuario:id,nombre,email');

        return response()->json([
            'message' => 'Tarea creada correctamente',
            'data' => $tarea
        ], 201);
    }

    /**
     * Mostrar tarea específica
     */
    public function show(string $id)
    {
        $tarea = Tarea::with('usuario:id,nombre,email')->findOrFail($id);
        return response()->json($tarea);
    }

    /**
     * Actualizar tarea
     */
    public function update(Request $request, string $id)
    {
        $tarea = Tarea::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:200',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|required|in:pendiente,en_progreso,completada',
            'prioridad' => 'sometimes|required|in:baja,media,alta',
            'fecha_vencimiento' => 'sometimes|required|date',
            'usuario_id' => 'sometimes|required|exists:usuarios,id'
        ]);

        $tarea->update($validated);
        $tarea->load('usuario:id,nombre,email');

        return response()->json([
            'message' => 'Tarea actualizada correctamente',
            'data' => $tarea
        ]);
    }

    /**
     * Eliminar tarea
     */
    public function destroy(string $id)
    {
        $tarea = Tarea::findOrFail($id);
        $tarea->delete();

        return response()->json([
            'message' => 'Tarea eliminada correctamente'
        ]);
    }

    /**
     * Listar tareas pendientes para el reporte Excel
     */
    public function tareasPendientes()
    {
        $tareas = Tarea::with('usuario:id,nombre,email')
            ->where('estado', 'pendiente')
            ->select('id', 'titulo', 'descripcion', 'prioridad', 'fecha_vencimiento', 'usuario_id', 'created_at')
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return response()->json($tareas);
    }
}
