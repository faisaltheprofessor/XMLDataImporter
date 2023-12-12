<?php

namespace App\Services;

use SimpleXMLElement;

interface DataParserService
{
    public function parseData(string $file_path): SimpleXMLElement;

    public function detectColumns(mixed $file, bool $firstRowIsHeader = true): array;

    public function createTable(array $columns, string $table = null, bool $timestamps = false): string;

    public function insertData(mixed $file, $table): void;

}
