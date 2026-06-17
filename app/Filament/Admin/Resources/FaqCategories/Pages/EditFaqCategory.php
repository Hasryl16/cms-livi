<?php

namespace App\Filament\Admin\Resources\FaqCategories\Pages;

use App\Filament\Admin\Resources\FaqCategories\FaqCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaqCategory extends EditRecord
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
     //redirect to faq list page after create new faq
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
