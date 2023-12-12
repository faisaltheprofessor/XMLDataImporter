<?php

namespace App\Console\Commands;

use App\Facades\DataParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportToDB extends Command
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
        $filePath = storage_path('app/feed.xml');
        $xml = DataParser::parseData($filePath);
        $columns = DataParser::detectColumns($xml, true, true);
        $table = DataParser::createTable($columns);
        DataParser::insertData($xml, $table);
        return true;
    }
}
