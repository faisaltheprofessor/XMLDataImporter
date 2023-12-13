<?php

namespace Tests\Feature;

use App\Exceptions\InvalidFileException;
use App\Facades\DataParser;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class XMLParserTest extends TestCase
{
    use RefreshDatabase;
    public function test_parseData_throws_exception_when_file_not_found(): void
    {
        $this->expectException(FileNotFoundException::class);
        $xmlData = DataParser::parseData('invalid/path');
    }

    public function test_parseData_throws_exception_when_file_is_invalid(): void
    {
        $this->expectException(InvalidFileException::class);
        $xmlData = DataParser::parseData($this->test_files_path('invalid.xml'));
    }

    public function test_parseData_discovers_columns_if_first_row_is_header(): void
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

        $xmlData = DataParser::parseData($this->test_files_path('feed.xml'));
        $detectedColumns = DataParser::discoverColumns($xmlData);
        $this->assertEquals($columns, array_values($detectedColumns));
    }


    public function test_parseData_sets_column_names_if_first_row_is_not_header(): void
    {
        $columns = [
            'column_1',
            'column_2',
            'column_3',
            'column_4',
            'column_5',
            'column_6',
            'column_7',
            'column_8',
            'column_9',
            'column_10',
            'column_11',
            'column_12',
            'column_13',
            'column_14',
            'column_15',
            'column_16',
            'column_17',
            'column_18',
        ];

        $xmlData = DataParser::parseData($this->test_files_path('feed.xml'));
        $detectedColumns = DataParser::discoverColumns($xmlData, false);
        $this->assertEquals($columns, array_values($detectedColumns));
    }

    public function test_parseData_creates_table_with_right_columns_when_first_row_is_header(): void
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

        $xmlData = DataParser::parseData($this->test_files_path('feed.xml'));
        $discoveredColumns = DataParser::discoverColumns($xmlData, true);
        DataParser::createTable($table, array_keys($discoveredColumns));

        $actualColumns = collect(DB::select("PRAGMA table_info($table)"))
            ->pluck('type', 'name')
            ->all();

        $this->assertEquals($expectedColumns, $actualColumns);
    }

    protected function test_files_path(string $file): string
    {
        return base_path('tests/Files/' . $file);
    }
}
