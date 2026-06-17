<?php

namespace App\Filament\Admin\Resources\MediaLibraries\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;


class MediaLibrariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

    

        

        ImageColumn::make('path')
            ->disk('public')
            ->label('Preview')
            ->square(),

        TextColumn::make('title')
            ->searchable(),

        TextColumn::make('media_type')
            ->badge(),

        TextColumn::make('folder.name')
            ->label('Folder'),

        TextColumn::make('size')
            ->formatStateUsing(
                fn ($state) => round($state / 1024, 1) . ' KB'
            ),

        TextColumn::make('uploader.name')
            ->label('Uploaded By'),

            ])
            
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->filters([
                SelectFilter::make('media_type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                        'document' => 'Document',
                    ]),
            ]);
    }
}