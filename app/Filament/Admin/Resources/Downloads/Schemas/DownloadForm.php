<?php

namespace App\Filament\Admin\Resources\Downloads\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
//use App\Filament\Forms\Components\SeoMetaSection;

class DownloadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn ($state, callable $set)
                            => $set('slug', Str::slug($state))
                    ),

                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('categories')
                    ->relationship(
                        'categories',
                        'name',
                        fn ($query) => $query->where('is_active', true)
                    )
                    ->multiple()
                    ->searchable()
                    ->preload(),

                // Select::make('tags')
                //     ->relationship('tags', 'name')
                //     ->multiple()
                //     ->searchable()
                //     ->preload(),

                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('slug')
                            ->required(),
                    ]),


                

                TextInput::make('version'),

                FileUpload::make('thumbnail')
                    ->image()
                    ->directory('downloads/thumbnails'),

                FileUpload::make('file_path')
                    ->required()
                    ->directory('downloads/files'),

                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),

               // SeoMetaSection::make(),

            ]);
    }
}