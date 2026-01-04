<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;

class UserImportTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_can_import_users_from_json_file(): void
    {
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
        ];

        $filePath = tempnam(sys_get_temp_dir(), 'users_') . '.json';
        file_put_contents($filePath, json_encode($users));

        $this->artisan("user:import {$filePath}")
            ->expectsOutputToContain('Importing: john@example.com')
            ->expectsOutputToContain('Importing: jane@example.com')
            ->expectsOutputToContain('Finished processing.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

        unlink($filePath);
    }

    public function test_it_obeys_the_limit_option(): void
    {
        $users = [
            ['name' => 'User 1', 'email' => 'user1@example.com'],
            ['name' => 'User 2', 'email' => 'user2@example.com'],
            ['name' => 'User 3', 'email' => 'user3@example.com'],
        ];

        $filePath = tempnam(sys_get_temp_dir(), 'users_') . '.json';
        file_put_contents($filePath, json_encode($users));

        $this->artisan("user:import {$filePath} --limit=2")
            ->expectsOutputToContain('Importing: user1@example.com')
            ->expectsOutputToContain('Importing: user2@example.com')
            ->doesntExpectOutputToContain('Importing: user3@example.com')
            ->assertExitCode(0);

        $this->assertEquals(2, User::count());

        unlink($filePath);
    }

    public function test_it_skips_existing_users_and_shows_warning(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $users = [
            ['name' => 'Existing User', 'email' => 'existing@example.com'],
            ['name' => 'New User', 'email' => 'new@example.com'],
        ];

        $filePath = tempnam(sys_get_temp_dir(), 'users_') . '.json';
        file_put_contents($filePath, json_encode($users));

        $this->artisan("user:import {$filePath}")
            ->expectsOutputToContain('Skipping: existing@example.com (already exists)')
            ->expectsOutputToContain('Importing: new@example.com')
            ->assertExitCode(0);

        $this->assertEquals(2, User::count());

        unlink($filePath);
    }
}
