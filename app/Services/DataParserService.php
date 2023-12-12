<?php

namespace App\Services;

use SimpleXMLElement;

interface DataParserService
{
    public function parseData(string $file_path): ?SimpleXMLElement;

    public function detectColumns(mixed $file, bool $firstRowIsHeader = true): array;

    public function createTable(string $table, array $columns, bool $timestamps = false): bool;

    public function insertData(mixed $file, string $table, bool $firstRowIsHeader = false): bool;
}
