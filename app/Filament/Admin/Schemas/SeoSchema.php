<?php

namespace App\Filament\Admin\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class SeoSchema
{
    public static function schema(): array
    {
        return [

            TextInput::make('meta_title')
                ->label('Meta Title')
                ->maxLength(255)
                ->helperText('Recommended: 50-60 characters'),

            Textarea::make('meta_description')
                ->label('Meta Description')
                ->rows(3)
                ->maxLength(160)
                ->helperText('Recommended: 150-160 characters'),

            TextInput::make('meta_keywords')
                ->label('Meta Keywords'),

            TextInput::make('canonical_url')
                ->label('Canonical URL')
                ->url(),

            FileUpload::make('og_image')
                ->label('Open Graph Image')
                ->image()
                ->directory('seo'),
        ];
    }
}