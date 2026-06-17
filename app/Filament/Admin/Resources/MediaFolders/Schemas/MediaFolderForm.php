<?php

namespace App\Filament\Admin\Resources\MediaFolders\Schemas;

use App\Models\MediaFolder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MediaFolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn ($state, $set)
                            => $set('slug', Str::slug($state))
                    ),

                TextInput::make('slug')
                    ->required(),

                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),

            ]);
    }
}