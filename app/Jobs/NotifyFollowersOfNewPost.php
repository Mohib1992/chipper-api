<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyFollowersOfNewPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $post;

    /**
     * Create a new job instance.
     */
    public function __construct($post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $author = $this->post->user;

        // Count followers to calculate staggered delays
        $delay = now();

        $author->favoritedBy()->with('user')->chunk(200, function ($favorites) use (&$delay) {
            foreach ($favorites as $favorite) {
                $follower = $favorite->user;
                if ($follower) {
                    $follower->notify((new \App\Notifications\PostCreated($this->post))->delay($delay));
                    $delay = $delay->addSeconds(2);
                }
            }
        });
    }
}
