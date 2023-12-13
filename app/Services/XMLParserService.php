<?php

namespace App\Services;

use App\Exceptions\InvalidFileException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use SimpleXMLElement;

class XMLParserService implements DataParserService
{
    /**
     * @param string $file_path
     * @return SimpleXMLElement|null
     * @throws FileNotFoundException
     * @throws InvalidFileException
     */
    public function parseData(string $file_path): ?SimpleXMLElement
    {
        if (filter_var($file_path, FILTER_VALIDATE_URL) !== false) {
            // File Download
            $file_data = file_get_contents($file_path);

            // Save the file temporarily
            $temp_file_path = sys_get_temp_dir() . '/' . basename($file_path);
            file_put_contents($temp_file_path, $file_data);

            $file_path = $temp_file_path;
        }

        if (!file_exists($file_path)) {
            Log::channel('dataimportlog')->error("File ($file_path) does not exist");
            throw new FileNotFoundException('File does not exist');
        }

        //        The purpose of this statement is to handle errors my way
        libxml_use_internal_errors(true);

        $xmlData = file_get_contents($file_path);
        // Load XML data
        $xml = simplexml_load_file($file_path);

        // Check if loading was successful
        if ($xml === false) {
            Log::channel('dataimportlog')->error('Invalid File');
            throw new InvalidFileException('Invalid File');
        }
        Log::channel('dataimportlog')->info("File ($file_path) read successfully");
        return $xml;
    }

    public function discoverColumns(mixed $file, bool $firstRowIsHeader = true): array
    {
        //        Get the first Row and convert it to array
        $firstElement = $file->children()[0];
        $dataArray = json_decode(json_encode($firstElement), true);

        //        Flatten the array
        $columns = array_keys(Arr::dot($dataArray));

        if (!$firstRowIsHeader) {
            $columns = array_map(function ($index) {
                return "col_" . $index + 1;
            }, array_keys($columns));

            Log::channel('dataimportlog')->info('Columns discovered', array_values($columns));
            return $columns;
        }


        $columns = collect($columns)->mapWithKeys(function ($column) {
            return [$column => $this->validateColumnName($column)];
        })->all();

        Log::channel('dataimportlog')->info('Columns discovered and validated', array_values($columns));

        return $columns;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param bool $timestamps
     * @return bool
     * @throws Exception
     */
    public function createTable(string $table, array $columns, bool $timestamps = false): bool
    {
        if (Schema::hasTable($table)) {
            Log::channel('dataimportlog')->error('Table exists');
            throw new \Exception("Table '$table' already exists.");
        }

        Schema::create($table, function (Blueprint $table) use ($columns, $timestamps) {
            $table->id();
            foreach ($columns as $originalColumnName => $validatedColumnName) {
                $table->longText($validatedColumnName);
            }

            if ($timestamps) {
                $table->timestamps();
            }

            Log::channel('dataimportlog')->info("Table created");
        });

        return true;
    }

    /**
     * @param mixed $file
     * @param string $table
     * @param bool $firstRowIsHeader
     * @return bool
     * @throws Exception
     */
    public function insertData(mixed $file, string $table, bool $firstRowIsHeader = true): bool
    {
        foreach ($file->children() as $dataElement) {
            $data = [];
            $columnNumber = 1;


            foreach ($dataElement->children() as $child) {
                if ($firstRowIsHeader) {
                    $data[$child->getName()] = trim((string)$child);
                } else {
                    $data['col_' . $columnNumber] = trim((string)$child);
                    $columnNumber++;
                }
            }

            try {
                DB::table($table)->insert($data);
            } catch (QueryException $e) {
                Log::channel('dataimportlog')->error('Error inserting data: ' . $e->getMessage());
                throw new Exception('Error inserting data: ' . $e->getMessage());
            }
        }

        Log::channel('dataimportlog')->info('Data imported to Table');

        return true;
    }

    /**
     * @param string $column
     * @return string
     */
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
