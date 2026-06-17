<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class SeoMetaSection
{
    public static function make(): Section
    {
        return Section::make('SEO')
            ->relationship('seoMeta')
            ->schema([

                TextInput::make('meta_title')
                    ->maxLength(60),

                Textarea::make('meta_description')
                    ->rows(3)
                    ->maxLength(160),

                TextInput::make('focus_keyword'),

                TextInput::make('og_title'),

                Textarea::make('og_description')
                    ->rows(3),

                FileUpload::make('og_image')
                    ->image()
                    ->directory('seo'),

                TextInput::make('canonical_url')
                    ->url(),

                Select::make('robots')
                    ->options([
                        'index,follow' => 'Index, Follow',
                        'noindex,follow' => 'No Index, Follow',
                        'index,nofollow' => 'Index, No Follow',
                        'noindex,nofollow' => 'No Index, No Follow',
                    ])
                    ->default('index,follow'),

            ])
            ->columns(2);
    }
}