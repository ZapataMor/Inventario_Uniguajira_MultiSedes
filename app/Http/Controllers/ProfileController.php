<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ActivityLogger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use PasswordValidationRules;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la vista de mi perfil.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($request->ajax()) {
            return view('profile.show', compact('user'))
                ->renderSections()['content'];
        }

        return view('profile.show', compact('user'));
    }

    /**
     * Actualizar la informacion basica del perfil autenticado.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('users', 'username')->ignore($user->id),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
            ]);

            $oldValues = [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ];

            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            ActivityLogger::updated(
                User::class,
                $user->id,
                $user->name,
                $oldValues,
                [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            );

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Perfil actualizado correctamente.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrio un error al actualizar el perfil.',
            ], 500);
        }
    }

    /**
     * Actualizar la contrasena del usuario autenticado.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => $this->passwordRules(),
            ]);

            $user->password = $validated['password'];
            $user->save();

            ActivityLogger::custom(
                'update',
                'Actualizo la contrasena de su cuenta.',
                [
                    'model' => 'User',
                    'model_id' => $user->id,
                ]
            );

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Contrasena actualizada correctamente.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrio un error al actualizar la contrasena.',
            ], 500);
        }
    }
}
