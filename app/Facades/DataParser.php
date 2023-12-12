<?php

namespace App\Facades;

use App\Services\XMLParserService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static readFile(string $file_path) Returns file path
 * @method static parseData(string $file_path) parses the data, creates table and stores in db */
class DataParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return XMLParserService::class;
    }
}
