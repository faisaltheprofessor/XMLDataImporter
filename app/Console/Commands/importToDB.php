<?php

namespace App\Console\Commands;

use App\Facades\DataParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

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
        $filePath = $this->getFilePath();


//        Parse XML
        $xml = DataParser::parseData($filePath);

        info("Detected Columns ✅");
//        Detect Columns
        $columns = DataParser::detectColumns($xml, true, true);
        info("Detected: ");
        info('[' . implode(',' . PHP_EOL, $columns) . ' ]');

        $tableName = $this->getTableName();
        info("Creating Table ($tableName) ...");

//        Create Table with the given columns
        if (DataParser::createTable($tableName, $columns)) {
            info("✅");
            info("Importing Data");

//            Insert Data
            DataParser::insertData($xml, $tableName);
        }

        info("🚀 Done ✅");

        $seeData = confirm(
            label: 'Do you want to see the data?',
            default: true,
            yes: 'Yes',
            no: 'No',
            hint: 'The first 20 rows'
        );


// Asking for and Displaying an excerpt of imported data
        if ($seeData) {
            $records = DB::table($tableName)
                ->select(...$columns)
                ->take(20)
                ->get()
                ->map(function ($item) {
                    return array_map(function ($value) {
                        return Str::limit($value, 20, '...');
                    }, array_values((array)$item));
                })
                ->toArray();

            table(
                $columns,
                $records
            );
        }
    }


    protected function getFilePath(): string
    {
        $defaultFilePath = storage_path('app/feed.xml');
        $filePath = text(
            label: '📁 Where is the file at?',
            placeholder: 'default: storage/app/feed.xml',
            hint: 'Use absolute or relevant paths'
        );

        return $filePath ?: $defaultFilePath;
    }

    protected function getTableName(): string
    {
        $defaultTableName = 'table_' . time();
        $tableName = text(
            label: '📈 What should we call the new table?',
            placeholder: 'default: ' . $defaultTableName,
            hint: 'Hit enter for default'
        );

        return $tableName ?: $defaultTableName;
    }

}
