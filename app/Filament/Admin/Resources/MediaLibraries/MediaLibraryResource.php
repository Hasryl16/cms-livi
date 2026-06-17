<?php

namespace App\Filament\Admin\Resources\MediaLibraries;

use App\Filament\Admin\Resources\MediaLibraries\Pages\CreateMediaLibrary;
use App\Filament\Admin\Resources\MediaLibraries\Pages\EditMediaLibrary;
use App\Filament\Admin\Resources\MediaLibraries\Pages\ListMediaLibraries;
use App\Filament\Admin\Resources\MediaLibraries\Schemas\MediaLibraryForm;
use App\Filament\Admin\Resources\MediaLibraries\Tables\MediaLibrariesTable;
use App\Models\MediaLibrary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaLibraryResource extends Resource
{
    protected static ?string $model = MediaLibrary::class;

    protected static string|\UnitEnum|null $navigationGroup = 'MEDIA';

    protected static ?int $navigationSort = 110;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'file_name';

    public static function form(Schema $schema): Schema
    {
        return MediaLibraryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaLibrariesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaLibraries::route('/'),
            'create' => CreateMediaLibrary::route('/create'),
            'edit' => EditMediaLibrary::route('/{record}/edit'),
        ];
    }
}
