<?php

namespace App\Services\FileUpload;

use Illuminate\Http\UploadedFile;

interface FileUploadStrategy
{
    public function upload(UploadedFile $file, string $path): array;
    public function validate(UploadedFile $file): bool;
}
