<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CustomerIdentityConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Customers\UpdateCustomerProfileService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('customer'));
    }

    public function update(UpdateProfileRequest $request, UpdateCustomerProfileService $service)
    {
        try {
            $user = $service->update($request->user(), $request->validated());
        } catch (CustomerIdentityConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [$exception->field => [$exception->getMessage()]],
            ], 409);
        } catch (QueryException) {
            return response()->json([
                'message' => 'Esiste già un cliente con questi dati.',
            ], 409);
        }

        return (new UserResource($user))->additional(['message' => 'Profilo aggiornato.']);
    }
}
