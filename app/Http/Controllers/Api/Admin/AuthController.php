<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Group(
    'Admin Authentication',
    'Authenticate with email and password to get a Sanctum Bearer token, then send that token to protected /api/admin endpoints.'
)]
class AuthController extends Controller
{
    /**
     * @throws ValidationException
     */
    #[Endpoint(
        title: 'Login to admin API',
        description: 'Submit admin credentials to receive a Sanctum token. Use the returned token as `Authorization: Bearer {token}` for protected admin endpoints.'
    )]
    #[BodyParameter('email', 'Admin account email address.', example: 'e.a.gamal@gmail')]
    #[BodyParameter('password', 'Admin account password.', example: '123456')]
    #[BodyParameter('device_name', 'Optional device label for the issued token.', required: false, example: 'Postman')]
    #[Response(
        200,
        description: 'Login successful. Returns the Sanctum Bearer token and the authenticated user.',
        examples: [[
            'token' => '1|sanctum-example-token',
            'token_type' => 'Bearer',
            'user' => [
                'id' => 1,
                'name' => 'E. A. Gamal',
                'email' => 'e.a.gamal@gmail',
            ],
        ]]
    )]
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $tokenName = $request->string('device_name')->toString()
            ?: $request->userAgent()
            ?: 'admin-api';

        $token = $user->createToken(Str::limit($tokenName, 255, ''));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    #[Endpoint(
        title: 'Get current admin user',
        description: 'Returns the currently authenticated admin user. Requires a valid Sanctum Bearer token.'
    )]
    #[Response(
        200,
        description: 'Authenticated admin user.',
        examples: [[
            'data' => [
                'id' => 1,
                'name' => 'E. A. Gamal',
                'email' => 'e.a.gamal@gmail',
            ],
        ]]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    #[Endpoint(
        title: 'Logout from admin API',
        description: 'Revokes the currently used Sanctum token. Requires a valid Sanctum Bearer token.'
    )]
    #[Response(
        200,
        description: 'Token revoked successfully.',
        examples: [[
            'message' => 'Logged out successfully.',
        ]]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
