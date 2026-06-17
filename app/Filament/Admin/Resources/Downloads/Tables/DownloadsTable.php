<?php

namespace App\Filament\Admin\Resources\Downloads\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DownloadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                ImageColumn::make('thumbnail'),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version'),

                TextColumn::make('author.name')
                    ->label('Author'),

                TextColumn::make('download_count')
                    ->sortable(),

            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}