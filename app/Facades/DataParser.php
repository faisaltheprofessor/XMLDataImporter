<?php

namespace App\Facades;

use App\Services\XMLParserService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static readFile(string $file_path) Returns file path
 * @method static parseData(string $file_path) parses the data
 * @method static detectColumns(mixed $file, bool $firstRowIsHeader = true) returns the columns
 * @method static createTable(string $table, array $columns, bool $timestamps = false) create table
 * @method static insertData(mixed $file, $table) inserts the records from parsed file into database
 */

class DataParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return XMLParserService::class;
    }
}
