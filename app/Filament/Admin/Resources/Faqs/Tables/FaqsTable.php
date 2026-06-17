<?php

namespace App\Filament\Admin\Resources\Faqs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('question')
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category'),

                TextColumn::make('author.name')
                    ->label('Author'),

                TextColumn::make('sort_order'),

                IconColumn::make('is_active')
                    ->boolean(),

            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}