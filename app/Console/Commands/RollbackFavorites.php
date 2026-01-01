<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollbackFavorites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'favorites:rollback {--chunk=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback favorites data to previous state';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunk = (int) $this->option('chunk') ?? 100;

        DB::table('favorites')
            ->whereNotNull('favoritable_id')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) {
                DB::table('favorites')
                    ->whereIn('id', $rows->pluck('id'))
                    ->where('favoritable_type', '=', \App\Models\Post::class)
                    ->update([
                        'post_id' => DB::raw('favoritable_id'),
                        'favoritable_id' => null,
                        'favoritable_type' => null,
                    ]);

                usleep(200_000); // throttle to avoid DB spikes
            });

        $this->info('Rollback completed.');
        return self::SUCCESS;
    }
}
