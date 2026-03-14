<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTopPersonRequest;
use App\Http\Requests\UpdateTopPersonRequest;
use App\Http\Resources\TopPersonResource;
use App\Models\TopPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

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
