<?php

namespace App\Filament\Admin\Resources\MediaLibraries\Pages;

use App\Filament\Admin\Resources\MediaLibraries\MediaLibraryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateMediaLibrary extends CreateRecord
{
    protected static string $resource = MediaLibraryResource::class;

   

    //auto detect media type based on file extension and set uploaded_by to current user
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();

        $data['file_name'] = basename($data['path']);

        $data['disk'] = 'public';

        $extension = strtolower(
            pathinfo($data['path'], PATHINFO_EXTENSION)
        );

        $data['media_type'] = match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) => 'image',
            in_array($extension, ['mp4', 'webm']) => 'video',
            default => 'document',
        };

        $data['mime_type'] = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            default => null,
        };

        $fullPath = storage_path('app/public/' . $data['path']);

        if (file_exists($fullPath)) {
            $data['size'] = filesize($fullPath);
        }

        return $data;
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['uploaded_by'] = auth()->id();

    //     $data['file_name'] = basename($data['path']);

    //     $data['disk'] = 'public';

    //     $extension = strtolower(
    //         pathinfo($data['path'], PATHINFO_EXTENSION)
    //     );

    //     $data['mime_type'] = match ($extension) {
    //         'jpg', 'jpeg' => 'image/jpeg',
    //         'png' => 'image/png',
    //         'webp' => 'image/webp',
    //         'mp4' => 'video/mp4',
    //         'webm' => 'video/webm',
    //         'pdf' => 'application/pdf',
    //         default => null,
    //     };

    //     $data['media_type'] = match (true) {
    //         in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) => 'image',
    //         in_array($extension, ['mp4', 'webm']) => 'video',
    //         default => 'document',
    //     };

    //     return $data;
    // }

     //redirect to index page after create
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
