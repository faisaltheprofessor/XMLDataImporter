<?php

namespace App\Console\Commands;

use App\Facades\DataParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
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

            $xml = DataParser::parseData($filePath);

            $firstRowIsHeader = $this->confirmFirstRowAsHeader();

            $this->tableName = $this->getTableName();

            $this->processDataImport($xml, $firstRowIsHeader);

        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit;
        }

        $this->displayImportedDataExcerpt();
    }

    protected function confirmFirstRowAsHeader(): bool
    {
        return confirm(
            label: 'Set first Row as the header?',
            default: true,
            yes: 'Yes',
            no: 'No',
            hint: 'If not, column names will be set as column_1, column_2 ...'
        );
    }

    protected function processDataImport($xml, bool $firstRowIsHeader): void
    {
        spin(
            function () use ($xml, $firstRowIsHeader) {
                info('Detected Columns ✅');

                $this->columns = DataParser::discoverColumns($xml, $firstRowIsHeader, true);
                info(implode(',' . PHP_EOL, $this->columns));

                info("Creating Table ($this->tableName) ...");

                if (DataParser::createTable($this->tableName, $this->columns)) {
                    info('✅');
                    info('Importing Data');

                    DataParser::insertData($xml, $this->tableName, $firstRowIsHeader);
                }

                info(PHP_EOL . '🚀 Done ✅' . PHP_EOL);
            },
            'Processing...'
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
            default: true,
            yes: 'Yes',
            no: 'No',
            hint: 'The first 20 rows'
        );

        if ($seeData) {
            $this->displayImportedData();
        }
    }

    protected function displayImportedData(): void
    {
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

        table($this->columns, $records);
    }
}
