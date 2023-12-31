<?php

namespace App\Services;

use App\Contracts\DataParserService;
use App\Exceptions\InvalidFileException;
use App\Exceptions\TableAlreadyExistsException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\QueryException;
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
            // URL: File Download
            $file_data = file_get_contents($file_path);

            // Save the file temporarily
            $temp_file_path = sys_get_temp_dir() . '/' . basename($file_path);
            file_put_contents($temp_file_path, $file_data);

            $file_path = $temp_file_path;
        } else {
            // Local file path
            if (!file_exists($file_path)) {
                // Log the error
                $error_message = "File ($file_path) does not exist";
                Log::channel('dataimportlog')->error($error_message);

                // Throw a FileNotFoundException
                throw new FileNotFoundException($error_message);
            }
        }

        // The purpose of this statement is to handle errors my way
        libxml_use_internal_errors(true);

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

    public function discoverColumns(mixed $file, bool $useOriginalColumnNames = true): array
    {
        // Get the first Row and convert it to array
        $firstElement = $file->children()[0];
        $dataArray = json_decode(json_encode($firstElement), true);

        // Flatten the array
        $flattenedArray = Arr::dot($dataArray);

        // Filter out keys starting with '@' (attributes) "For Now :)"
        $filteredArray = array_filter($flattenedArray, function ($value, $key) {
            return !preg_match('/^@/', $key);
        }, ARRAY_FILTER_USE_BOTH);

        $columns = array_keys($filteredArray);


        if (!$useOriginalColumnNames) {
            $columns = array_map(function ($index) {
                return "col_" . ($index + 1);
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
            throw new TableAlreadyExistsException("Table '$table' already exists.");
        }

        Schema::create($table, function (Blueprint $table) use ($columns, $timestamps) {
            $table->id();
            foreach ($columns as $originalColumnName => $validatedColumnName) {
                $table->longText($validatedColumnName)->nullable();
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
     * @param bool $useOriginalColumnNames
     * @return bool
     * @throws Exception
     */
    public function importData(mixed $file, string $table, bool $useOriginalColumnNames = true): bool
    {
        foreach ($file->children() as $dataElement) {
            $data = [];
            $columnNumber = 1;

            $this->traverseXmlData($dataElement, $data, $columnNumber, $useOriginalColumnNames);

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
        $modifiedColumn = preg_replace('/.*\./', '', $column);

        if (empty($modifiedColumn)) {
            throw new \InvalidArgumentException("Invalid column name: $column");
        }

        return $modifiedColumn;
    }

    private function traverseXmlData($dataElement, &$data, &$columnNumber, $useOriginalColumnNames): void
    {
        foreach ($dataElement->children() as $child) {
            if ($useOriginalColumnNames) {
                $columnName = $child->getName();

                // Check if the child element has children (nested elements)
                if ($child->count() > 0) {
                    $this->traverseXmlData($child, $data, $columnNumber, $useOriginalColumnNames);
                } else {
                    $data[$columnName] = trim((string)$child);
                }
            } else {
                $data['col_' . $columnNumber] = trim((string)$child);
                $columnNumber++;
            }
        }
    }
}
