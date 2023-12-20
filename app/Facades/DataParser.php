<?php

namespace App\Facades;

use App\Services\XMLParserService;
use Illuminate\Support\Facades\Facade;
use SimpleXMLElement;

/**
 * @method static null|SimpleXMLElement parseData(string $file_path) parses xml file
 * @method static array discoverColumns(mixed $file, bool $useOriginalColumnNames = true) Determines the names of columns from the xml file
 * @method static bool createTable(string $table, array $columns, bool $timestamps = false) creates the table
 * @method static bool importData(mixed $file, string $table, bool $useOriginalColumnNames = true) imports the records from parsed file into database
 */
class DataParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return XMLParserService::class;
    }
}
