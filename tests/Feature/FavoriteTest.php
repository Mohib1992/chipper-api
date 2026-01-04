<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => get_class($post),
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => get_class($post),
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => get_class($post),
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }

    public function test_a_user_can_favorite_a_user()
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $this->actingAs($follower)
            ->postJson(route('favorites.storeUser', ['user' => $followed]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $followed->id,
            'favoritable_type' => get_class($followed),
            'user_id' => $follower->id,
        ]);
    }

    public function test_a_user_can_remove_a_user_from_his_favorites()
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $this->actingAs($follower)
            ->postJson(route('favorites.storeUser', ['user' => $followed]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $followed->id,
            'favoritable_type' => get_class($followed),
            'user_id' => $follower->id,
        ]);

        $this->actingAs($follower)
            ->deleteJson(route('favorites.destroyUser', ['user' => $followed]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_id' => $followed->id,
            'favoritable_type' => get_class($followed),
            'user_id' => $follower->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_user()
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $this->actingAs($follower)
            ->deleteJson(route('favorites.destroyUser', ['user' => $followed]))
            ->assertNotFound();
    }

    public function test_a_user_can_not_favorite_a_non_existing_user()
    {
        $follower = User::factory()->create();

        $this->actingAs($follower)
            ->postJson(route('favorites.storeUser', ['user' => 1000000]))
            ->assertNotFound();
    }

    public function test_a_user_can_not_favorite_itself()
    {
        $follower = User::factory()->create();

        $this->actingAs($follower)
            ->postJson(route('favorites.storeUser', ['user' => $follower->id]))
            ->assertBadRequest();
    }   

    public function test_a_user_can_get_all_favorites_items()
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(5)->create();
        $followers = User::factory()->count(5)->create();

        foreach ($followers as $follower) {
            $this->actingAs($user)
                ->postJson(route('favorites.storeUser', ['user' => $follower]))
                ->assertCreated();
        }   

        foreach ($posts as $post) {
            $this->actingAs($user)
                ->postJson(route('favorites.store', ['post' => $post]))
                ->assertCreated();
        }       

        $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk()
            ->assertJsonCount(5, 'data.posts')
            ->assertJsonCount(5, 'data.users')
            ->assertJson(
                [
                    'data' => [
                        'posts' => json_decode(PostResource::collection($posts)->toJson(), true),
                        'users' => json_decode(UserResource::collection($followers)->toJson(), true)
                    ]
                ]
            )
            ->assertJsonStructure([
                'data' => [
                    'posts' => [
                        '*' => ['id','title','body','user' => ['id', 'name']]
                    ],
                    'users' => [
                        '*' => ['id', 'name']
                    ]
                ],
            ]);
    }
}
