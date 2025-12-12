<?php

namespace App\Services\FileUpload;

use Illuminate\Http\UploadedFile;

class FileUploadService
{
    private FileUploadStrategy $strategy;

    public function setStrategy(FileUploadStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function upload(UploadedFile $file, string $path): array
    {
        return $this->strategy->upload($file, $path);
    }

    public function uploadMultiple(array $files, string $path): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            $uploadedFiles[] = $this->upload($file, $path);
        }

        return $uploadedFiles;
    }
}
