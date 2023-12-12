<?php

namespace App\Facades;

use App\Services\DataParserService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static readFile(string $file_path) Returns file path
 */
class DataParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataParserService::class;
    }
}
