<?php

namespace App\Filament\Admin\Resources\Faqs\Pages;

use App\Filament\Admin\Resources\Faqs\FaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaq extends EditRecord
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    //redirect to faq list page after update faq
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
