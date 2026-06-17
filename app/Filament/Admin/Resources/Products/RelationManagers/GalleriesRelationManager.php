<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;

class GalleriesRelationManager extends RelationManager
{
    protected static string $relationship = 'galleries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                FileUpload::make('media_path')
                    ->image()
                    ->directory('products/gallery')
                    ->required(),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('media_path')
            ->columns([
                ImageColumn::make('media_path')
                    ->label('Image'),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
