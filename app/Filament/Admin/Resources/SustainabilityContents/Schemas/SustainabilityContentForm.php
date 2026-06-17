<?php

namespace App\Filament\Admin\Resources\SustainabilityContents\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
//use App\Filament\Forms\Components\SeoMetaSection;

class SustainabilityContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('categories')
                    ->relationship(
                        'categories',
                        'name',
                        fn ($query) => $query->where('is_active', true)
                    )
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn ($state, callable $set)
                            => $set('slug', Str::slug($state))
                    ),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                FileUpload::make('featured_image')
                    ->image()
                    ->directory('sustainability'),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->required(),

                DateTimePicker::make('published_at'),

                RichEditor::make('content')
                    ->columnSpanFull(),

                //SeoMetaSection::make(),

            ]);
    }
}