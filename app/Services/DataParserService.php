<?php

namespace App\Services;

use Illuminate\Support\Facades\File;


class DataParserService
{
    /**
     * @param $file_path
     * @return string|null
     */
    public function readFile($file_path): ?string
    {
        try {
            if (File::exists($file_path)) {
                return File::get($file_path);
            } else {
                echo "File not found at path: " . $file_path;
                return null;
            }
        } catch (\Throwable $e) {
            echo "Error reading file: " . $file_path;
            return null;
        }
    }

    
}
