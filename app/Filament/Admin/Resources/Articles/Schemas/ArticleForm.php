<?php

namespace App\Filament\Admin\Resources\Articles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
//use App\Filament\Forms\Components\SeoMetaSection;

class ArticleForm
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


                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                CheckboxList::make('categories')
                    ->relationship('categories', 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', true)
                    )
                    ->columns(2),

                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->required(),
                
                DateTimePicker::make('published_at'),

                FileUpload::make('featured_image')
                    ->image()
                    ->directory('articles'),

                Textarea::make('excerpt')
                    ->rows(4)
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->columnSpanFull(),

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

               // SeoMetaSection::make(),

            ]);

    }
}