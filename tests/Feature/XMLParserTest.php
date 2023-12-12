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

    public function test_parseData_discovers_columns_correctly(): void
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

    protected function test_files_path(string $file): string
    {
        return base_path('tests/Files/' . $file);
    }
}
