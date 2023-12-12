<?php

namespace App\Console\Commands;

use App\Facades\DataParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class importToDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-to-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = DataParser::readFile(Storage::get('feed.xml'));

    }
}
