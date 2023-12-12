<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SimpleXMLElement;

class XMLParserService implements DataParserService
{
    public function parseData(string $file_path): SimpleXMLElement
    {
        $xmlFilePath = storage_path('app/feed.xml');

        $xmlData = file_get_contents($file_path);

        // Parse XML data
        try {
            $xml = new SimpleXMLElement($xmlData);
        } catch (\Exception $e) {
        }

        return $xml;
    }

    public function detectColumns(mixed $xml, bool $firstRowIsHeader = true): array
    {
        $firstElement = $xml->children()[0];

        // Convert XML object to array
        $dataArray = json_decode(json_encode($firstElement), true);


        return array_keys(Arr::dot($dataArray));
    }

    public function createTable(array $columns, string $table = null, bool $timestamps = false): string
    {
        $table = $table ?? 'table_' . time();
        Schema::create($table, function (Blueprint $table) use ($columns, $timestamps) {
            $table->id();
            foreach ($columns as $column) {
                $table->longText($column);
            }

            if ($timestamps) {
                $table->timestamps();
            }
        });

        return $table;
    }

    /**
     * @throws \Exception
     */
    public function insertData(mixed $file, $table): void
    {
        foreach ($file->children() as $dataElement) {
            $data = [];

            foreach ($dataElement->children() as $child) {
                $data[$child->getName()] = trim((string)$child);
            }

            try {
                DB::table($table)->insert($data);
            } catch (QueryException $e) {
                throw new \Exception('Error inserting data: ' . $e->getMessage());
            }
        }
    }
}
