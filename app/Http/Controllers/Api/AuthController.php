<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CustomerIdentityConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegisterCustomerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterCustomerService $service)
    {
        try {
            $user = $service->register($request->validated());
        } catch (CustomerIdentityConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [$exception->field => [$exception->getMessage()]],
            ], 409);
        } catch (QueryException) {
            return response()->json([
                'message' => 'Non è stato possibile completare la registrazione perché alcuni dati risultano già utilizzati.',
            ], 409);
        }

        Auth::guard('customer')->login($user);
        $request->session()->regenerate();

        return (new UserResource($user))->additional([
            'message' => 'Registrazione completata.',
        ])->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = [
            'email' => mb_strtolower(trim($request->string('email')->toString())),
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            return response()->json([
                'message' => 'Email o password non corretti.',
                'errors' => ['email' => ['Controlla l’indirizzo email e la password inseriti.']],
            ], 401);
        }

        $request->session()->regenerate();
        $user = Auth::guard('customer')->user();

        if (! $user->active) {
            Auth::guard('customer')->logout();
            $request->session()->migrate(true);
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Il tuo account è stato disattivato. Contatta l’assistenza per maggiori informazioni.',
                'errors' => ['email' => ['Questo account non è attualmente abilitato all’accesso.']],
            ], 403);
        }

        return (new UserResource($user->load('customer')))->additional([
            'message' => 'Accesso effettuato.',
        ]);
    }

    public function user(Request $request): UserResource
    {
        return new UserResource($request->user('customer')->load('customer'));
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->migrate(true);
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Disconnessione completata.']);
    }
}
