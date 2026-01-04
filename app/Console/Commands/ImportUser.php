<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\LazyCollection;
use App\Models\User;
use JsonMachine\Items;
use Illuminate\Support\Facades\Hash;

class ImportUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:import {url} {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a given URL.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $limit = (int) $this->option('limit');
        
        $items = Items::fromFile($url);
        
        if ($limit > 0) {
            $items = LazyCollection::make($items)->take($limit);
            $total = $limit; // Using limit as total for the progress bar if limited
        } else {
            $total = iterator_count(Items::fromFile($url));
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');

        $progressBar->start();

        foreach ($items as $user) {
            if (!$this->validateUser($user)) {
                $progressBar->finish();
                $this->newLine();
                $this->error("Invalid user data encountered.");
                return Command::FAILURE;
            }   

            if (User::where('email', $user->email)->exists()) {
                $progressBar->clear();
                $this->warn(" Skipping: {$user->email} (already exists)");
                $progressBar->display(); // Redraw the progress bar
                $progressBar->advance();
                continue;
            }

            try {
                User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);

                $progressBar->clear();
                $this->info("Importing: {$user->email}");
                $progressBar->display(); // Redraw the progress bar
                $progressBar->advance();

            } catch (\Exception $e) {
                $progressBar->clear();
                $this->error(" Failed to create user #{$user->email}: {$e->getMessage()}");
                $progressBar->display(); // Redraw the progress bar
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        $this->info('Finished processing.');
        return Command::SUCCESS;
    }

    public function validateUser($user)
    {
        return isset($user->email) && isset($user->name);
    }   
}
