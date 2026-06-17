<?php

namespace App\Filament\Admin\Resources\MediaLibraries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaLibraryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('folder_id')
                    ->relationship('folder', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('title')
                    ->required(),

                TextInput::make('alt_text'),

                Textarea::make('caption')
                    ->rows(3),

                FileUpload::make('path')
                    ->disk('public')
                    ->directory('media')
                    ->moveFiles()
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'video/mp4',
                        'video/webm',
                        'application/pdf',
                    ])
            ]);
    }
}