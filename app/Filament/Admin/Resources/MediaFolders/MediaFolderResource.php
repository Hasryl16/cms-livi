<?php

namespace App\Filament\Admin\Resources\MediaFolders;

use App\Filament\Admin\Resources\MediaFolders\Pages\CreateMediaFolder;
use App\Filament\Admin\Resources\MediaFolders\Pages\EditMediaFolder;
use App\Filament\Admin\Resources\MediaFolders\Pages\ListMediaFolders;
use App\Filament\Admin\Resources\MediaFolders\Schemas\MediaFolderForm;
use App\Filament\Admin\Resources\MediaFolders\Tables\MediaFoldersTable;
use App\Models\MediaFolder;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaFolderResource extends Resource
{
    protected static ?string $model = MediaFolder::class;

    protected static string|UnitEnum|null $navigationGroup = 'MEDIA';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MediaFolderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaFoldersTable::configure($table);
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
            'index' => ListMediaFolders::route('/'),
            'create' => CreateMediaFolder::route('/create'),
            'edit' => EditMediaFolder::route('/{record}/edit'),
        ];
    }
}
