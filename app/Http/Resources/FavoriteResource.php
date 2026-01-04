<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FavoriteResource extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $users = $posts = [];

        foreach ($this->resource as $favorite) {
            $favoritable = $favorite->favoritable;

            if ($favoritable instanceof \App\Models\Post) {
                $posts[] = $favoritable;
            } elseif ($favoritable instanceof \App\Models\User) {
                $users[] = $favoritable;
            }
        }

        // Deduplicate users
        $users = collect($users)->unique('id')->values();

        return [
            'posts' => PostResource::collection($posts),
            'users' => UserResource::collection($users),
        ];
    }
}
