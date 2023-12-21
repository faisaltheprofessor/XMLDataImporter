<?php

namespace App\Console\Commands;

use App\Facades\DataParser;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        } catch (Exception $e) {
            $this->error($e->getMessage());
            exit;
        }

        $this->displayImportedDataExcerpt();
    }

    /**
     * Displays a confirm dialog box to use the original column names or cols_1, cols_2, cols_3 ...
     * @return bool
     */
    protected function confirmUseOriginalColumnNames(): bool
    {
        return confirm(
            label: 'Use original column names for the table?',
            hint: 'If no, column names will be set as col_1, col_2 ...'
        );
    }

    /**
     * Imports data from xml file into database table
     * @param $xml
     * @param bool $useOriginalColumnNames
     * @return void
     */
    protected function processDataImport($xml, bool $useOriginalColumnNames): void
    {
        spin(
            function () use ($xml, $useOriginalColumnNames) {
                info('Discovered Columns:');

                $this->columns = DataParser::discoverColumns($xml, $useOriginalColumnNames);
                info(implode(',' . PHP_EOL, $this->columns));

                info("Creating Table: $this->tableName");

                if (DataParser::createTable($this->tableName, $this->columns)) {
                    info(PHP_EOL . 'Table Created Successfully ✅');
                    info(PHP_EOL . 'Importing Data into the table');

                    DataParser::importData($xml, $this->tableName, $useOriginalColumnNames);
                }

                info(PHP_EOL . 'Data Import Completed Successfully ✅' . PHP_EOL);
            },
            '⏳ This might take a moment...'
        );
    }

    /**
     * Asks the user for the file location
     * @return string
     */
    protected function getFilePath(): string
    {
        $defaultFilePath = base_path('tests/files/feed.xml');
        return trim(text(
            label: 'Enter the file location:',
            placeholder: 'default: test/files/feed.xml',
            hint: 'Drag and drop the file or provide a file URL'
        )) ?: $defaultFilePath;
    }

    /**
     * Asks the user for the table name
     * @return string
     */
    protected function getTableName(): string
    {
        $defaultTableName = 'table_' . time();
        return text(
            label: 'Enter the name for the new table:',
            placeholder: 'default: ' . $defaultTableName,
            hint: 'Press enter to use the default name'
        ) ?: $defaultTableName;
    }

    /**
     * Asks the user if they want to see the first 20 records of the imported data
     * @return void
     */
    protected function displayImportedDataExcerpt(): void
    {
        $seeData = confirm(
            label: 'Do you want to view the imported data?',
            hint: 'The first 20 rows of imported data (if available)'
        );


        if ($seeData) {
            $this->displayImportedData();
        }

        info('Thank you for using the program. The program will now exit.');
    }

    /**
     * Displays the imported data as a table
     * @return void
     */
    protected function displayImportedData(): void
    {

        $displayAllColumns = confirm(
            'Display all columns of the table?',
            default: false,
            hint: 'Note Displaying all records may cause visual distortion if the table is wide'
        );

        if ($displayAllColumns) {
            $columns = $this->columns;
        } else {
            $columns = multiselect(
                label: 'Select the columns to display:',
                options: $this->columns,
                default: $this->getFirstElements($this->columns),
                hint: 'Use the arrow keys to navigate and spacebar to make a selection'
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

    /**
     * Returns the first three columns
     * The purpose is that the first three columns are preselected when the user is asked to select what columns to display
     * @param $array
     * @return array
     */
    protected function getFirstElements($array): array
    {
        return array_slice($array, 0, min(count($array), 3));
    }
}
