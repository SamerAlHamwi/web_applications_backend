<?php
namespace App\Services\FileUpload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // or Imagick\Driver

class ImageUploadStrategy implements FileUploadStrategy
{
    private const MAX_SIZE = 5120; // 5MB in KB
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    private const MAX_WIDTH = 1920;
    private const MAX_HEIGHT = 1920;

    private ImageManager $manager;

    public function __construct()
    {
        // Initialize ImageManager with GD driver
        $this->manager = new ImageManager(new Driver());
    }

    public function validate(UploadedFile $file): bool
    {
        return $file->getSize() <= (self::MAX_SIZE * 1024)
            && in_array($file->getMimeType(), self::ALLOWED_MIMES);
    }

    public function upload(UploadedFile $file, string $path): array
    {
        if (!$this->validate($file)) {
            throw new \Exception('Invalid image file');
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $path . '/' . $filename;

        // Resize image if too large
        $image = $this->manager->read($file);

        if ($image->width() > self::MAX_WIDTH || $image->height() > self::MAX_HEIGHT) {
            $image->scale(width: self::MAX_WIDTH, height: self::MAX_HEIGHT);
        }

        // Save to storage
        Storage::put($filePath, (string) $image->encode());

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => 'image',
            'mime_type' => $file->getMimeType(),
            'file_size' => Storage::size($filePath),
        ];
    }
}
