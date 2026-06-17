<?php

namespace App\Filament\Admin\Resources\Faqs\Pages;

use App\Filament\Admin\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

    //auto author_id when create new faq
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // use Auth facade id() to avoid static analysis issues with auth() helper
        $data['author_id'] ??= \Illuminate\Support\Facades\Auth::id();

        return $data;
    }
    //redirect to faq list page after create new faq
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
