<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\FavoriteResource;
use App\Http\Requests\CreateFavoriteRequest;
use Illuminate\Support\Facades\Log;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
         $favorites = $request->user()
            ->favorites()
            ->with(['favoritable' => function ($query) {
                $query->morphWith([
                    Post::class => ['user'],
                ]);
            }])
            ->get();

        return new FavoriteResource($favorites);
    }

    public function store(CreateFavoriteRequest $request, Post $post)
    {
        $request->user()->favorites()->firstOrCreate(['favoritable_id' => $post->id, 'favoritable_type' => get_class($post)]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Post $post)
    {
        $favorite = $request->user()->favorites()->where('favoritable_id', $post->id)->where('favoritable_type', get_class($post))->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }

    public function storeUser(CreateFavoriteRequest $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->noContent(Response::HTTP_BAD_REQUEST);
        }

        $request->user()->favorites()->firstOrCreate(['favoritable_id' => $user->id, 'favoritable_type' => get_class($user)]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroyUser(Request $request, User $user)
    {
        $favorite = $request->user()->favorites()->where('favoritable_id', $user->id)->where('favoritable_type', get_class($user))->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }
}
