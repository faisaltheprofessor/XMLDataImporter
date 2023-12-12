<?php

namespace App\Services;

use App\Exceptions\InvalidFileException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SimpleXMLElement;

class XMLParserService implements DataParserService
{
    /**
     * @throws InvalidFileException
     */
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
            throw new InvalidFileException("Invalid File");
        }

        return $xml;
    }

    public function detectColumns(mixed $file, bool $firstRowIsHeader = true): array
    {
        $firstElement = $file->children()[0];

        // Convert XML object to array
        $dataArray = json_decode(json_encode($firstElement), true);


        $originalColumns =  array_keys(Arr::dot($dataArray));

        return collect($originalColumns)->mapWithKeys(function($column) {
            return [$column => $this->validateColumnName($column)];
        })->all();
    }

    public function createTable(string $table, array $columns, bool $timestamps = false): bool
    {
        Schema::create($table, function (Blueprint $table) use ($columns, $timestamps) {
            $table->id();
            foreach ($columns as $originalColumnName => $validatedColumnName ) {
                $table->longText($validatedColumnName);
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

    private function validateColumnName(string $column): string
    {
        // Remove non-alphanumeric characters and ensure it starts with a letter
        $modifiedColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $modifiedColumn = preg_replace('/^[^a-zA-Z]+/', '', $modifiedColumn);

        if (empty($modifiedColumn)) {
            throw new \InvalidArgumentException("Invalid column name: $column");
        }

        return $modifiedColumn;
    }
}
