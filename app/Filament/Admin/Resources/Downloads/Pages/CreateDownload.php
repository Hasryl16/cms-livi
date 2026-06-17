<?php

namespace App\Filament\Admin\Resources\Downloads\Pages;

use App\Filament\Admin\Resources\Downloads\DownloadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDownload extends CreateRecord
{
    protected static string $resource = DownloadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] ??= auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
