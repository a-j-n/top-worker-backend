<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTopPersonRequest;
use App\Http\Requests\UpdateTopPersonRequest;
use App\Http\Resources\TopPersonResource;
use App\Models\TopPerson;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

#[Group(
    'Admin Top People',
    'Manage approved entries and pending top people submissions from the admin API.'
)]
class TopPersonController extends Controller
{
    /**
     * List all top people for admin management.
     */
    public function index(): AnonymousResourceCollection
    {
        return TopPersonResource::collection(
            TopPerson::query()
                ->with('category')
                ->latest()
                ->get()
        );
    }

    /**
     * List only pending top people for admin approval.
     */
    #[Endpoint(
        title: 'List pending top people',
        description: 'Returns only top people that are still waiting for admin approval.'
    )]
    #[ApiResponse(
        200,
        description: 'Pending top people retrieved successfully.',
        examples: [[
            'data' => [
                [
                    'id' => 12,
                    'name' => 'Ahmed Gamal',
                    'phone' => '+201001234567',
                    'bio' => 'Experienced plumbing technician specializing in residential maintenance.',
                    'avatar' => 'top-people/ahmed-gamal.jpg',
                    'avatar_url' => 'https://haader.fra1.cdn.digitaloceanspaces.com/toplist/top-people/ahmed-gamal.jpg',
                    'category_id' => 1,
                    'is_approved' => false,
                    'category' => [
                        'id' => 1,
                        'name' => 'Plumbing',
                        'name_ar' => 'سباكة',
                        'created_at' => '2026-03-13T00:00:00.000000Z',
                        'updated_at' => '2026-03-13T00:00:00.000000Z',
                    ],
                    'created_at' => '2026-03-16T00:00:00.000000Z',
                    'updated_at' => '2026-03-16T00:00:00.000000Z',
                ],
            ],
        ]]
    )]
    public function pending(): AnonymousResourceCollection
    {
        return TopPersonResource::collection(
            TopPerson::query()
                ->with('category')
                ->where('is_approved', false)
                ->latest()
                ->get()
        );
    }

    /**
     * Create a new top person.
     */
    public function store(StoreTopPersonRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->storePublicly('top-people');
        }

        $topPerson = TopPerson::query()->create($validated);

        return TopPersonResource::make($topPerson->load('category'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single top person.
     */
    public function show(TopPerson $topPerson): TopPersonResource
    {
        return TopPersonResource::make($topPerson->load('category'));
    }

    /**
     * Approve a pending top person.
     */
    #[Endpoint(
        title: 'Approve pending top person',
        description: 'Marks a pending top person as approved so it becomes visible in the public API.'
    )]
    #[PathParameter('topPerson', 'The top person ID.', type: 'integer', example: 12)]
    #[ApiResponse(
        200,
        description: 'Top person approved successfully.',
        examples: [[
            'data' => [
                'id' => 12,
                'name' => 'Ahmed Gamal',
                'phone' => '+201001234567',
                'bio' => 'Experienced plumbing technician specializing in residential maintenance.',
                'avatar' => 'top-people/ahmed-gamal.jpg',
                'avatar_url' => 'https://haader.fra1.cdn.digitaloceanspaces.com/toplist/top-people/ahmed-gamal.jpg',
                'category_id' => 1,
                'is_approved' => true,
                'category' => [
                    'id' => 1,
                    'name' => 'Plumbing',
                    'name_ar' => 'سباكة',
                    'created_at' => '2026-03-13T00:00:00.000000Z',
                    'updated_at' => '2026-03-13T00:00:00.000000Z',
                ],
                'created_at' => '2026-03-16T00:00:00.000000Z',
                'updated_at' => '2026-03-16T00:00:00.000000Z',
            ],
        ]]
    )]
    public function approve(TopPerson $topPerson): TopPersonResource
    {
        $topPerson->update([
            'is_approved' => true,
        ]);

        return TopPersonResource::make($topPerson->refresh()->load('category'));
    }

    /**
     * Update an existing top person.
     */
    public function update(UpdateTopPersonRequest $request, TopPerson $topPerson): TopPersonResource
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($topPerson);
            $validated['avatar'] = $request->file('avatar')->storePublicly('top-people');
        } elseif ($request->exists('avatar') && $request->input('avatar') === null) {
            $this->deleteAvatar($topPerson);
            $validated['avatar'] = null;
        }

        $topPerson->update($validated);

        return TopPersonResource::make($topPerson->refresh()->load('category'));
    }

    /**
     * Delete a top person.
     */
    #[Endpoint(
        title: 'Delete top person',
        description: 'Deletes a top person by ID. This can be used to reject pending submissions or remove existing entries.'
    )]
    #[PathParameter('topPerson', 'The top person ID.', type: 'integer', example: 12)]
    #[ApiResponse(
        204,
        description: 'Top person deleted successfully.'
    )]
    public function destroy(TopPerson $topPerson): Response
    {
        $this->deleteAvatar($topPerson);
        $topPerson->delete();

        return response()->noContent();
    }

    private function deleteAvatar(TopPerson $topPerson): void
    {
        if ($topPerson->avatar === null) {
            return;
        }

        Storage::delete($topPerson->avatar);
    }
}
