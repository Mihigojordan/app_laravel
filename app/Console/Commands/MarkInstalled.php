<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MarkInstalled extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-installed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark the app as installed without running migrate:fresh, for databases that are already set up';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Storage::disk('public')->put('installed', 'OK');
        $this->info('App marked as installed. The /setup wizard is now disabled.');
    }
}
