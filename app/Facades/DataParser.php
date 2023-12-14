<?php

namespace App\Facades;

use App\Services\XMLParserService;
use Illuminate\Support\Facades\Facade;

/**  @param string $file_path
 * @method static parseData(string $file_path) parses xml file
 * @method static discoverColumns(mixed $file, bool $useOriginalColumnNames = true) returns the columns
 * @method static createTable(string $table, array $columns, bool $timestamps = false) creates table
 * @method static importData(mixed $file, string $table, bool $useOriginalColumnNames = true) imports the records from parsed file into database
 */
class DataParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return XMLParserService::class;
    }
}
