<?php

namespace App\Console\Commands;

use App\Facades\DataParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class ImportToDB extends Command
{
    protected $signature = 'app:import-xml-to-db';

    protected $description = 'Import XML data into the database';

    protected string $tableName;

    protected array $columns;

    public function handle(): void
    {
        try {
            $filePath = $this->getFilePath();

            $xml = spin(function () use ($filePath) {
                return DataParser::parseData($filePath);
            }, 'Processing...');

            $useOriginalColumnNames = $this->confirmUseOriginalColumnNames();

            $this->tableName = $this->getTableName();

            $this->processDataImport($xml, $useOriginalColumnNames);

        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit;
        }

        $this->displayImportedDataExcerpt();
    }

    protected function confirmUseOriginalColumnNames(): bool
    {
        return confirm(
            label: 'Use original column names?',
            hint: 'If no, column names will be set as col_1, col_2 ...'
        );
    }

    protected function processDataImport($xml, bool $useOriginalColumnNames): void
    {
        spin(
            function () use ($xml, $useOriginalColumnNames) {
                info('Detected Columns ✅');

                $this->columns = DataParser::discoverColumns($xml, $useOriginalColumnNames);
                info(implode(',' . PHP_EOL, $this->columns));

                info("Creating Table ($this->tableName)");

                if (DataParser::createTable($this->tableName, $this->columns)) {
                    info(PHP_EOL . 'Table Created ✅');
                    info(PHP_EOL . 'Importing Data');

                    DataParser::importData($xml, $this->tableName, $useOriginalColumnNames);
                }

                info(PHP_EOL . '🚀 Done ✅' . PHP_EOL);
            },
            'processing...'
        );
    }

    protected function getFilePath(): string
    {
        $defaultFilePath = base_path('tests/Files/feed.xml');
        return text(
            label: '📁 Where is the file at?',
            placeholder: 'default: test/Files/feed.xml',
            hint: 'Drag and drop or even provide File URL, the rest will be taken care of'
        ) ?: $defaultFilePath;
    }

    protected function getTableName(): string
    {
        $defaultTableName = 'table_' . time();
        return text(
            label: '📈 What should we call the new table?',
            placeholder: 'default: ' . $defaultTableName,
            hint: 'Hit enter for default'
        ) ?: $defaultTableName;
    }

    protected function displayImportedDataExcerpt(): void
    {
        $seeData = confirm(
            label: 'Do you want to see the imported data?',
            hint: 'The first 20 rows'
        );


        if ($seeData) {
            $this->displayImportedData();
        }

        info('👋 The program will now exit. Thank you for using it.');
    }

    protected function displayImportedData(): void
    {

        $displayAllColumns = confirm(
            'Display all columns?',
            default: false,
            hint: 'Displaying all records may widen the table causing scrambling and visual distortion.'
        );

        if($displayAllColumns) {
            $columns = $this->columns;
        } else {

            $columns = multiselect(
                label: 'What columns?',
                options: $this->columns,
                default: $this->getFirstElements($this->columns),
                hint: 'Use the arrow keys to navigate and press the spacebar to make a selection.'
            );
        }

        $records = DB::table($this->tableName)
            ->select(...array_values($columns))
            ->take(20)
            ->get()
            ->map(function ($item) {
                return array_map(function ($value) {
                    return $value;
                }, array_values((array)$item));
            })
            ->toArray();

        table($columns, $records);
    }

    protected function getFirstElements($array): array
    {
        return array_slice($array, 0, min(count($array), 3));
    }
}
