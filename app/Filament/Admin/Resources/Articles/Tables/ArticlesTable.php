<?php

namespace App\Filament\Admin\Resources\Articles\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                ImageColumn::make('featured_image')
                    ->label('Image'),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                // TextColumn::make('category.name')
                //     ->label('Category'),
                
                // TextColumn::make('categories.name')
                //     ->badge(),  

                TextColumn::make('categories.name')
                    ->listWithLineBreaks(),

                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('published_at')
                    ->dateTime(),

            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}