<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class BackfillFavorites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'favorites:backfill {--chunk=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill existing favorate data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunk = (int) $this->option('chunk');

        DB::table('favorites')
            ->whereNull('favoritable_id')
            ->whereNotNull('post_id')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) {
                DB::table('favorites')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update([
                        'favoritable_id'   => DB::raw('post_id'),
                        'favoritable_type' => Post::class,
                    ]);

                usleep(200_000); // throttle to avoid DB spikes
            });
  
        $this->info('Backfill completed.');

        if (Schema::hasColumn('favorites','post_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->dropColumn('post_id');
            });
            $this->info('Dropped post_id column from favorites table.');
        }

        return self::SUCCESS;   
    }
}
