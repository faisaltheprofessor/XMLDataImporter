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
    protected $signature = 'app:import-xml-to-db';

    protected $description = 'Command description';

    protected string $tableName;

    protected array $columns;

    public function handle(): void
    {
        try {
            $filePath = $this->getFilePath();

            //        Parse XML
            $xml = DataParser::parseData($filePath);

            $firstRowIsHeader = confirm(
                label: 'Set first Row as the header?',
                default: true,
                yes: 'Yes',
                no: 'No',
                hint: 'If not, column names will be set as column_1, column_2 ...'
            );

            info('Detected Columns ✅');
            //        Detect Columns
            $this->columns = DataParser::discoverColumns($xml, $firstRowIsHeader, true);
            info(implode(',' . PHP_EOL, $this->columns));

            $this->tableName = $this->getTableName();
            info("Creating Table ($this->tableName) ...");

            //        Create Table with the given columns

            if (DataParser::createTable($this->tableName, $this->columns)) {
                info('✅');
                info('Importing Data');

                //            Insert Data
                DataParser::insertData($xml, $this->tableName, $firstRowIsHeader);
            }

            info('🚀 Done ✅');
        } catch (\Exception $e) {
            info($e->getMessage());
            exit;
        }

        $this->displayImportedDataExcerpt();
    }

    protected function getFilePath(): string
    {
        $defaultFilePath = base_path('tests/Files/feed.xml');
        $filePath = text(
            label: '📁 Where is the file at?',
            placeholder: 'default: test/Files/feed.xml',
            hint: 'Drag and drop the file or even provide File URL, the rest will be taken care of'
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

    // Asking for and Displaying an excerpt of imported data
    protected function displayImportedDataExcerpt(): void
    {
        $seeData = confirm(
            label: 'Do you want to see the imported data?',
            default: true,
            yes: 'Yes',
            no: 'No',
            hint: 'The first 20 rows'
        );
        if ($seeData) {
            $records = DB::table($this->tableName)
                ->select(...array_values($this->columns))
                ->take(20)
                ->get()
                ->map(function ($item) {
                    return array_map(function ($value) {
                        return Str::limit($value, 20, '...');
                    }, array_values((array)$item));
                })
                ->toArray();

            table(
                $this->columns,
                $records
            );
        }
    }
}
