<?php

namespace App\Services\FileUpload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PdfUploadStrategy implements FileUploadStrategy
{
    private const MAX_SIZE = 10240; // 10MB in KB
    private const ALLOWED_MIME = 'application/pdf';

    public function validate(UploadedFile $file): bool
    {
        return $file->getSize() <= ($this::MAX_SIZE * 1024)
            && $file->getMimeType() === $this::ALLOWED_MIME;
    }

    public function upload(UploadedFile $file, string $path): array
    {
        if (!$this->validate($file)) {
            throw new \Exception('Invalid PDF file');
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.pdf';
        $filePath = $path . '/' . $filename;

        // Store file
        Storage::putFileAs($path, $file, $filename);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => 'pdf',
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }
}
