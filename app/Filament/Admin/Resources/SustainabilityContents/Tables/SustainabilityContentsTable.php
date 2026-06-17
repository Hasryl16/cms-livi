<?php

namespace App\Filament\Admin\Resources\SustainabilityContents\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SustainabilityContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                ImageColumn::make('featured_image'),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('Author'),

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