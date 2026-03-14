<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListPublicTopPeopleRequest;
use App\Http\Requests\StorePublicTopPersonRequest;
use App\Http\Resources\TopPersonResource;
use App\Models\TopPerson;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group(
    'Public Top People',
    'Browse approved top people and submit new candidates for admin review.'
)]
class PublicTopPersonController extends Controller
{
    /**
     * List approved top people with their category.
     */
    #[Endpoint(
        title: 'List approved top people',
        description: 'Returns only approved top people together with their category. Supports pagination, category filtering, and name search.'
    )]
    #[QueryParameter('category_id', 'Filter approved top people by category ID.', required: false, type: 'integer', example: 1)]
    #[QueryParameter('search', 'Search approved top people by name.', required: false, type: 'string', example: 'Ahmed')]
    #[QueryParameter('per_page', 'How many approved top people to return per page.', required: false, type: 'integer', example: 15)]
    #[QueryParameter('page', 'The paginated page number.', required: false, type: 'integer', example: 1)]
    public function index(ListPublicTopPeopleRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TopPersonResource::collection(
            TopPerson::query()
                ->with('category')
                ->where('is_approved', true)
                ->when(
                    $validated['category_id'] ?? null,
                    fn ($query, int $categoryId) => $query->where('category_id', $categoryId)
                )
                ->when(
                    $validated['search'] ?? null,
                    fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")
                )
                ->latest()
                ->paginate($validated['per_page'] ?? 15)
                ->withQueryString()
        );
    }

    #[Endpoint(
        title: 'Submit a top person',
        description: 'Creates a new top person submission. Public submissions are always saved with `is_approved` set to `false` until an admin approves them.'
    )]
    #[BodyParameter('name', 'Top person name.', example: 'Ahmed Gamal')]
    #[BodyParameter('phone', 'Top person phone number.', example: '+201001234567')]
    #[BodyParameter('category_id', 'Category ID for the submitted top person.', example: 1)]
    #[BodyParameter('avatar', 'Optional avatar image file.', required: false, type: 'string', format: 'binary')]
    #[ApiResponse(
        201,
        description: 'Submission created successfully and is pending admin approval.',
        examples: [[
            'message' => 'Top person submitted successfully and is pending admin approval.',
            'data' => [
                'id' => 1,
                'name' => 'Ahmed Gamal',
                'phone' => '+201001234567',
                'avatar' => 'top-people/example-avatar.jpg',
                'avatar_url' => 'https://haader.fra1.cdn.digitaloceanspaces.com/toplist/top-people/example-avatar.jpg',
                'category_id' => 1,
                'is_approved' => false,
                'category' => [
                    'id' => 1,
                    'name' => 'Actors',
                    'name_ar' => 'ممثلون',
                    'created_at' => '2026-03-13T00:00:00.000000Z',
                    'updated_at' => '2026-03-13T00:00:00.000000Z',
                ],
            ],
        ]]
    )]
    public function store(StorePublicTopPersonRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->storePublicly('top-people');
        }

        $topPerson = TopPerson::query()->create([
            ...$validated,
            'is_approved' => false,
        ]);

        return TopPersonResource::make($topPerson->load('category'))
            ->additional([
                'message' => 'Top person submitted successfully and is pending admin approval.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
