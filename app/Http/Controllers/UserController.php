<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Helpers\ActivityLogger;

class UserController extends Controller
{
    /**
     * Solo el rol administrador puede gestionar usuarios.
     */
    private function autorizarAdministrador(): void
    {
        abort_if(auth()->user()->role !== 'administrador', 403);
    }

    /**
     * Vista principal (listado).
     */
    public function index(Request $request)
    {
        $this->autorizarAdministrador();

        $users = User::orderBy('id', 'desc')->get();

        // Si es AJAX, solo renderiza la sección content (para loadContent)
        if ($request->ajax()) {
            return view('users.index', compact('users'))
                ->renderSections()['content'];
        }

        return view('users.index', compact('users'));
    }

    /**
     * API: Crear usuario
     * POST /api/users/store
     */
    public function store(Request $request)
    {
        $this->autorizarAdministrador();

        try {

            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
                'role'     => ['required', Rule::in(['administrador', 'consultor'])],
            ]);

            $user = User::create([
                'name'     => $validated['name'],
                'username' => $validated['username'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => $validated['role'],
            ]);

            // ✅ Registrar actividad
            ActivityLogger::created(User::class, $user->id, $user->name);

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Usuario creado correctamente.',
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrió un error al crear el usuario.',
            ], 500);
        }
    }

    /**
     * API: Actualizar usuario
     * POST /api/users/update
     */
    public function update(Request $request)
    {
        $this->autorizarAdministrador();

        try {

            $validated = $request->validate([
                'id'       => ['required', 'exists:users,id'],
                'name'     => ['required', 'string', 'max:255'],
                'username' => [
                    'required', 'string', 'max:255',
                    Rule::unique('users', 'username')->ignore($request->id),
                ],
                'email' => [
                    'required', 'email', 'max:255',
                    Rule::unique('users', 'email')->ignore($request->id),
                ],
                'password' => ['nullable', 'string', 'min:6'],
                'role'     => ['required', Rule::in(['administrador', 'consultor'])],
            ]);

            $user = User::findOrFail($validated['id']);

            // No permitir editar tu propio rol (recomendado)
            if (auth()->id() === $user->id && $validated['role'] !== $user->role) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'No puedes cambiar tu propio rol.',
                ], 422);
            }

            // ✅ Guardar valores anteriores para el log
            $oldValues = [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ];

            $user->name = $validated['name'];
            $user->username = $validated['username'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];

            // Solo actualiza password si el campo viene lleno
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            // ✅ Registrar actividad
            ActivityLogger::updated(
                User::class,
                $user->id,
                $user->name,
                $oldValues,
                [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            );

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Usuario actualizado correctamente.',
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrió un error al actualizar el usuario.',
            ], 500);
        }
    }

    /**
     * API: Eliminar usuario
     * DELETE /api/users/delete/{id}
     */
    public function destroy($id)
    {
        abort_if(auth()->user()->role !== 'administrador', 403);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        if ($user->name === 'Administrador') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario administrador no puede ser eliminado.'
            ], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'success'=> false,
                'message'=> 'No puedes eliminar tu propio usuario'
            ], 403);
        }


        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.'
        ]);
    }
}