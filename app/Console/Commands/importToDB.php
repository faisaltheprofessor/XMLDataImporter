<?php

namespace App\Console\Commands;

use App\Facades\DataParser;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;
use function Laravel\Prompts\info;

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
    public function handle(): void
    {
        $filePath= $this->getFilePath();

//        Parse XML
        $xml = DataParser::parseData($filePath);

        info("Detected Columns ✅");
//        Detect Columns
        $columns = DataParser::detectColumns($xml, true, true);
        info("Detected: ");
        info('[' . implode(','. PHP_EOL, $columns)  . ' ]');

        $tableName = $this->getTableName();
        info("Creating Table ($tableName) ...");

//        Create Table with the given columns
        if(DataParser::createTable($tableName, $columns))
        {
            info("✅");
            info("Importing Data");
//            Insert Data
            DataParser::insertData($xml, $tableName);
        }

        info("🚀 Done ✅");
    }


    protected function getFilePath(): string {
        $defaultFilePath = storage_path('app/feed.xml');
        $filePath = text(
            label: '📁 Where is the file at?',
            placeholder: 'default: storage/app/feed.xml',
            hint: 'Use absolute or relevant paths'
        );

        return  $filePath ?: $defaultFilePath;
    }

    protected function getTableName(): string {
        $defaultTableName = 'table_' . time();
        $tableName = text(
            label: '📈 What should we call the new table?',
            placeholder: 'default: ' . $defaultTableName,
            hint: 'Hit enter for default'
        );

        return $tableName ?: $defaultTableName;
    }

}
