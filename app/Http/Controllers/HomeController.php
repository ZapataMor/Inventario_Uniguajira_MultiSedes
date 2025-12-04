<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Simulamos la obtención de tareas como lo hacía PHP clásico
        $dataTasks = [
            'pendientes' => Task::where('status', 'pending')->get(),
            'completadas' => Task::where('status', 'completed')->get(),
        ];

        // si el usuario es consultor, muestra la vista de consultor
        $user = $request->user();
        if ($user && isset($user->role) && $user->role === 'consultor') {
            if ($request->ajax()) {
                $view = view('home.consultor', compact('dataTasks'))->renderSections();
                return $view['content'] ?? $view;
            }
            return view('home.consultor', compact('dataTasks'));
        }

        // si es una carga AJAX, solo renderiza el contenido interno
        if ($request->ajax()) {
            $view = view('home.index', compact('dataTasks'))->renderSections();
            return $view['content'] ?? $view;
        }
        
        // si es carga normal (primera vez), usa el layout completo
        return view('home.index', compact('dataTasks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
