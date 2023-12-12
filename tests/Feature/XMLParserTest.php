<?php

namespace Tests\Feature;

use App\Exceptions\InvalidFileException;
use App\Facades\DataParser;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Tests\TestCase;

class XMLParserTest extends TestCase
{
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
        $detectedColumns = DataParser::detectColumns($xmlData);
        $this->assertEquals($columns, array_values($detectedColumns));
    }


    public function test_parseData_discovers_columns_if_first_row_is_not_header(): void
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
        $detectedColumns = DataParser::detectColumns($xmlData, false);
        $this->assertEquals($columns, array_values($detectedColumns));
    }


    protected function test_files_path(string $file): string
    {
        return base_path('tests/Files/'.$file);
    }
}
