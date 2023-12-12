<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SimpleXMLElement;

class XMLParserService implements DataParserService
{
    public function parseData($file): bool
    {
        $xmlFilePath = storage_path('app/feed.xml');

        $xmlData = file_get_contents($file);

        // Parse XML data
        try {
            $xml = new SimpleXMLElement($xmlData);
        } catch (\Exception $e) {
        }

        $columns = $this->detectColumns($xml, true, true);

        $table = $this->createTable($columns);
        $this->insertData($xml, $table);

        return true;
    }

    protected function detectColumns(SimpleXMLElement $xml, bool $firstRowIsHeader = true): array
    {
        $firstElement = $xml->children()[0];

        // Convert XML object to array
        $dataArray = json_decode(json_encode($firstElement), true);

        $dataArray = collect($dataArray)->mapWithKeys(function ($value, $key) {
            return [$key => $value];
        })->all();

        return array_keys(Arr::dot($dataArray));
    }

    protected function createTable($columns, $table = null, bool $timestamps = false): string
    {
        $table = $table ?? 'table_' . time();
        Schema::create($table, function (Blueprint $table) use ($columns, $timestamps) {
            $table->id();
            foreach ($columns as $column) {
                $table->string($column);
            }

            if ($timestamps) {
                $table->timestamps();
            }
        });

        return $table;
    }

    protected function insertData(SimpleXMLElement $xml, $table)
    {
        foreach ($xml->children() as $dataElement) {
            $data = [];

            foreach ($dataElement->children() as $child) {
                $data[$child->getName()] = trim((string)$child);
            }

            try {
                DB::table($table)->insert($data);
            } catch (QueryException $e) {
                return 'Error inserting data: ' . $e->getMessage();
            }
        }
    }
}
