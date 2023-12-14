<?php

namespace App\Contracts;

use SimpleXMLElement;

interface DataParserService
{
    public function parseData(string $file_path): ?SimpleXMLElement;

    public function discoverColumns(mixed $file, bool $firstRowIsHeader = true): array;

    public function createTable(string $table, array $columns, bool $timestamps = false): bool;

    public function importData(mixed $file, string $table, bool $firstRowIsHeader = false): bool;
}
