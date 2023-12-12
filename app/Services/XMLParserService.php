<?php

namespace App\Services;

use Dotenv\Exception\InvalidFileException;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nette\FileNotFoundException;
use SimpleXMLElement;

class XMLParserService implements DataParserService
{
    public function parseData(string $file_path): ?SimpleXMLElement
    {
        if (!file_exists($file_path)) {
            throw new FileNotFoundException('File does not exist');
        }

        $xmlData = file_get_contents($file_path);
        // Load XML data
        $xml = simplexml_load_file($file_path);

        // Check if loading was successful
        if ($xml === false) {
            throw new InvalidFileException("Invalid XML File");
        }

        return $xml;
    }

    public function detectColumns(mixed $file, bool $firstRowIsHeader = true): array
    {
        $firstElement = $file->children()[0];

        // Convert XML object to array
        $dataArray = json_decode(json_encode($firstElement), true);


        return array_keys(Arr::dot($dataArray));
    }

    public function createTable(string $table, array $columns, bool $timestamps = false): bool
    {
        Schema::create($table, function (Blueprint $table) use ($columns, $timestamps) {
            $table->id();
            foreach ($columns as $column) {
                $table->longText($column);
            }

            if ($timestamps) {
                $table->timestamps();
            }
        });

        return true;
    }

    /**
     * @throws Exception
     */
    public function insertData(mixed $file, $table): bool
    {
        foreach ($file->children() as $dataElement) {
            $data = [];

            foreach ($dataElement->children() as $child) {
                $data[$child->getName()] = trim((string)$child);
            }

            try {
                DB::table($table)->insert($data);
            } catch (QueryException $e) {
                throw new Exception('Error inserting data: ' . $e->getMessage());
            }
        }

        return true;
    }
}
