<?php

namespace App\Filament\Admin\Resources\Faqs\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('category_id')
                    ->relationship(
                        'category',
                        'name',
                        fn ($query) => $query->where('is_active', true)
                    )
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),

                Textarea::make('question')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                RichEditor::make('answer')
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->default(true),

            ]);
    }
}