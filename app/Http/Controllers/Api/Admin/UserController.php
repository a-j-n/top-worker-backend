<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group(
    'Admin Users',
    'Manage admin-accessible users through authenticated admin API endpoints.'
)]
class UserController extends Controller
{
    /**
     * List all users for admin management.
     */
    #[Endpoint(
        title: 'List admin users',
        description: 'Returns all users available to the admin panel ordered by name and email.'
    )]
    #[ApiResponse(
        200,
        description: 'Users retrieved successfully.',
        examples: [[
            'data' => [
                [
                    'id' => 1,
                    'name' => 'E. A. Gamal',
                    'email' => 'e.a.gamal@gmail.com',
                    'email_verified_at' => '2026-03-13T00:00:00.000000Z',
                    'created_at' => '2026-03-13T00:00:00.000000Z',
                    'updated_at' => '2026-03-13T00:00:00.000000Z',
                ],
                [
                    'id' => 2,
                    'name' => 'Admin Assistant',
                    'email' => 'assistant@example.com',
                    'email_verified_at' => null,
                    'created_at' => '2026-03-14T00:00:00.000000Z',
                    'updated_at' => '2026-03-14T00:00:00.000000Z',
                ],
            ],
        ]]
    )]
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::query()
                ->orderBy('name')
                ->orderBy('email')
                ->get()
        );
    }

    /**
     * Create a new user.
     */
    #[Endpoint(
        title: 'Create admin user',
        description: 'Creates a new user record for admin-side management.'
    )]
    #[BodyParameter('name', 'User display name.', example: 'New Admin User')]
    #[BodyParameter('email', 'User email address. Must be unique.', example: 'new-admin@example.com')]
    #[BodyParameter('password', 'Plain-text password for the new user. It will be hashed before storage.', example: 'password123')]
    #[ApiResponse(
        201,
        description: 'User created successfully.',
        examples: [[
            'data' => [
                'id' => 3,
                'name' => 'New Admin User',
                'email' => 'new-admin@example.com',
                'email_verified_at' => null,
                'created_at' => '2026-03-16T00:00:00.000000Z',
                'updated_at' => '2026-03-16T00:00:00.000000Z',
            ],
        ]]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single user.
     */
    #[Endpoint(
        title: 'Show admin user',
        description: 'Returns a single user by ID for admin-side inspection.'
    )]
    #[PathParameter('user', 'The user ID.', type: 'integer', example: 1)]
    #[ApiResponse(
        200,
        description: 'User retrieved successfully.',
        examples: [[
            'data' => [
                'id' => 1,
                'name' => 'E. A. Gamal',
                'email' => 'e.a.gamal@gmail.com',
                'email_verified_at' => '2026-03-13T00:00:00.000000Z',
                'created_at' => '2026-03-13T00:00:00.000000Z',
                'updated_at' => '2026-03-13T00:00:00.000000Z',
            ],
        ]]
    )]
    public function show(User $user): UserResource
    {
        return UserResource::make($user);
    }

    /**
     * Update an existing user.
     */
    #[Endpoint(
        title: 'Update admin user',
        description: 'Updates a user. You may send any subset of name, email, and password.'
    )]
    #[PathParameter('user', 'The user ID.', type: 'integer', example: 1)]
    #[BodyParameter('name', 'Updated user display name.', required: false, example: 'Updated Admin User')]
    #[BodyParameter('email', 'Updated unique email address.', required: false, example: 'updated-admin@example.com')]
    #[BodyParameter('password', 'Updated plain-text password. It will be hashed before storage.', required: false, example: 'new-password123')]
    #[ApiResponse(
        200,
        description: 'User updated successfully.',
        examples: [[
            'data' => [
                'id' => 1,
                'name' => 'Updated Admin User',
                'email' => 'updated-admin@example.com',
                'email_verified_at' => '2026-03-13T00:00:00.000000Z',
                'created_at' => '2026-03-13T00:00:00.000000Z',
                'updated_at' => '2026-03-16T00:00:00.000000Z',
            ],
        ]]
    )]
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        return UserResource::make($user->refresh());
    }

    /**
     * Delete a user.
     */
    #[Endpoint(
        title: 'Delete admin user',
        description: 'Deletes a user by ID.'
    )]
    #[PathParameter('user', 'The user ID.', type: 'integer', example: 1)]
    #[ApiResponse(
        204,
        description: 'User deleted successfully.'
    )]
    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}
