<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use App\Models\ProductCategory;
//use App\Filament\Forms\Components\SeoMetaSection;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('category_id')
                    ->options(ProductCategory::pluck('name','id')->toArray())
                    //->relationship('category', 'name')
                    ->required()
                    ->searchable(),

                // Select::make('category_id')
                // ->options(
                //     \App\Models\ProductCategory::pluck('name', 'id')
                // )
                // ->searchable()
                // ->required(),

                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn ($state, callable $set)
                            => $set('slug', Str::slug($state))
                    ),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('sku'),

                FileUpload::make('featured_image')
                    ->image()
                    ->directory('products'),

                Toggle::make('featured')
                    ->default(false),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->required(),

                DateTimePicker::make('published_at'),

                CheckboxList::make('industries')
                    ->relationship('industries', 'name')
                    ->columns(2),

                Textarea::make('short_description')
                    ->rows(3)
                    ->columnSpanFull(),

                RichEditor::make('description')
                    ->columnSpanFull(),

                //SeoMetaSection::make(),
            

            ]);
    }

}