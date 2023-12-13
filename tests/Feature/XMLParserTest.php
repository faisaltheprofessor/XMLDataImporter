<?php

namespace Tests\Feature;

use App\Exceptions\InvalidFileException;
use App\Exceptions\TableAlreadyExistsException;
use App\Facades\DataParser;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class XMLParserTest extends TestCase
{
    use RefreshDatabase;
    public function test_throws_exception_when_file_does_not_exist(): void
    {
        $this->expectException(FileNotFoundException::class);
        $xmlData = DataParser::parseData('invalid/path');
    }

    public function test_throws_exception_when_file_is_invalid(): void
    {
        $this->expectException(InvalidFileException::class);
        DataParser::parseData($this->getTestingFilesPath('invalid.xml'));
    }

    public function test_discovers_columns_if_first_row_is_header(): void
    {
        $columns = [
            'entity_id',
            'CategoryName',
            'sku',
            'name',
            'description',
            'shortdesc',
            'price',
            'link',
            'image',
            'Brand',
            'Rating',
            'CaffeineType',
            'Count',
            'Flavored',
            'Seasonal',
            'Instock',
            'Facebook',
            'IsKCup',
        ];

        $xmlData = DataParser::parseData($this->getTestingFilesPath('feed.xml'));
        $detectedColumns = DataParser::discoverColumns($xmlData);
        $this->assertEquals($columns, array_values($detectedColumns));
    }


    public function test_sets_column_names_if_first_row_is_not_header(): void
    {
        $columns = [
            'col_1',
            'col_2',
            'col_3',
            'col_4',
            'col_5',
            'col_6',
            'col_7',
            'col_8',
            'col_9',
            'col_10',
            'col_11',
            'col_12',
            'col_13',
            'col_14',
            'col_15',
            'col_16',
            'col_17',
            'col_18',
        ];

        $xmlData = DataParser::parseData($this->getTestingFilesPath('feed.xml'));
        $detectedColumns = DataParser::discoverColumns($xmlData, false);
        $this->assertEquals($columns, array_values($detectedColumns));
    }

    public function test_creates_table_with_right_columns_when_first_row_is_header(): void
    {
        $table = 'test_xml_data';
        $expectedColumns = [
            'id' => 'INTEGER',
            'entity_id' => 'TEXT',
            'CategoryName' => 'TEXT',
            'sku' => 'TEXT',
            'name' => 'TEXT',
            'description' => 'TEXT',
            'shortdesc' => 'TEXT',
            'price' => 'TEXT',
            'link' => 'TEXT',
            'image' => 'TEXT',
            'Brand' => 'TEXT',
            'Rating' => 'TEXT',
            'CaffeineType' => 'TEXT',
            'Count' => 'TEXT',
            'Flavored' => 'TEXT',
            'Seasonal' => 'TEXT',
            'Instock' => 'TEXT',
            'Facebook' => 'TEXT',
            'IsKCup' => 'TEXT'
        ];

        $xmlData = DataParser::parseData($this->getTestingFilesPath('feed.xml'));
        $discoveredColumns = DataParser::discoverColumns($xmlData, true);
        DataParser::createTable($table, array_keys($discoveredColumns));

        $actualColumns = collect(DB::select("PRAGMA table_info($table)"))
            ->pluck('type', 'name')
            ->all();

        $this->assertEquals($expectedColumns, $actualColumns);
    }

    public function test_crates_table_with_right_columns_when_first_row_is_not_header(): void
    {
        $table = 'test_xml_data';
        $expectedColumns = [
            'id' => 'INTEGER',
            'col_1' => 'TEXT',
            'col_2' => 'TEXT',
            'col_3' => 'TEXT',
            'col_4' => 'TEXT',
            'col_5' => 'TEXT',
            'col_6' => 'TEXT',
            'col_7' => 'TEXT',
            'col_8' => 'TEXT',
            'col_9' => 'TEXT',
            'col_10' => 'TEXT',
            'col_11' => 'TEXT',
            'col_12' => 'TEXT',
            'col_13' => 'TEXT',
            'col_14' => 'TEXT',
            'col_15' => 'TEXT',
            'col_16' => 'TEXT',
            'col_17' => 'TEXT',
            'col_18' => 'TEXT',
        ];

        $xmlData = DataParser::parseData($this->getTestingFilesPath('feed.xml'));
        $discoveredColumns = DataParser::discoverColumns($xmlData, false);
        DataParser::createTable($table, array_keys($discoveredColumns));


        $columns = collect(\Illuminate\Support\Facades\DB::select("PRAGMA table_info($table)"));

        /**
         * when columns have similar beginnings like col_1, col_2, then the statement above returns only 1,2,3
         * mapping through column names and adding col_ to the beginning of each column except ID
         * @todo can be improved
         * */
        $actualColumns = $columns->mapWithKeys(function ($column) {
            if ($column->name !== 'id') {
                $columnName = 'col_' . $column->name + 1;
            } else {
                $columnName = $column->name;
            }
            $columnType = $column->type;
            return [$columnName => $columnType];
        })->toArray();

        $this->assertEquals($expectedColumns, $actualColumns);
    }

    public function test_imports_all_records(): void
    {
        $table = 'test_xml_data';
        $xmlData = DataParser::parseData($this->getTestingFilesPath('feed.xml'));
        $discoveredColumns = DataParser::discoverColumns($xmlData, true);
        DataParser::createTable($table, array_keys($discoveredColumns));
        DataParser::insertData($xmlData, $table);

        $numberOfRecordsOnFile = count($xmlData);
        $numberOfRecordsInDatabase = DB::select("select count(id) as count from $table")[0]->count;
        $this->assertEquals($numberOfRecordsOnFile, $numberOfRecordsInDatabase);
    }

    public function test_throws_exception_if_table_exists(): void
    {
        DataParser::createTable('test_table', ['random_col_1', 'random_col_2']);
        $this->expectException(TableAlreadyExistsException::class);
        DataParser::createTable('test_table', ['random_col_3', 'random_col_3']);
    }

    protected function getTestingFilesPath(string $file): string
    {
        return base_path('tests/Files/' . $file);
    }
}
