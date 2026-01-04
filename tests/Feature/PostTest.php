<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Notifications\PostCreated;
use Illuminate\Support\Facades\Queue;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_create_a_post()
    {
        $response = $this->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertStatus(401);
    }

    public function test_a_user_can_create_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'body',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post',
                    'body' => 'This is a test post.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);
    }

    public function test_a_user_can_update_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'body' => 'Updated body.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Updated title',
            'body' => 'Updated body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_not_update_a_post_by_other_user()
    {
        $john = User::factory()->create(['name' => 'John']);
        $jack = User::factory()->create(['name' => 'Jack']);

        $response = $this->actingAs($john)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($jack)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'title' => 'Original title',
            'body' => 'Original body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_destroy_one_of_his_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->deleteJson(route('posts.destroy', ['post' => $id]));

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', [
            'id' => $id,
        ]);
    }

     public function test_it_sends_notifications_to_followers()
    {
        Notification::fake();

        $author = User::factory()->create();
        $followers = User::factory(3)->create();

        foreach ($followers as $follower) {
            $follower->favorites()->create([
                'favoritable_id' => $author->id,
                'favoritable_type' => User::class,
            ]);
        }

        $post = Post::factory()->create(['user_id' => $author->id]);

        $job = new NotifyFollowersOfNewPost($post);
        $job->handle();

        Notification::assertSentTo($followers, PostCreated::class);
    }

    public function test_a_user_created_post_can_notify_followers()
    {
        Queue::fake();

        $author = User::factory()->create();
        $followers = User::factory(5)->create();

        foreach($followers as $follower) {
            $this->actingAs($follower)
                ->postJson(route('favorites.storeUser', ['user' => $author]))
                ->assertCreated();
        }

        $this->actingAs($author)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ])
        ->assertCreated();

        $this->assertDatabaseHas('posts', [
            'title' => 'My title',
            'body' => 'My body.',
        ]);

        Queue::assertPushed(NotifyFollowersOfNewPost::class, function ($job) {
            return $job->post->title === 'My title';
        });
    }
}
