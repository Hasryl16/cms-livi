<?php

namespace App\Filament\Admin\Resources\ArticleCategories\Pages;

use App\Filament\Admin\Resources\ArticleCategories\ArticleCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleCategory extends CreateRecord
{
    protected static string $resource = ArticleCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
