<?php

namespace App\Filament\Admin\Resources\Downloads;

use App\Filament\Admin\Resources\Downloads\Pages\CreateDownload;
use App\Filament\Admin\Resources\Downloads\Pages\EditDownload;
use App\Filament\Admin\Resources\Downloads\Pages\ListDownloads;
use App\Filament\Admin\Resources\Downloads\Schemas\DownloadForm;
use App\Filament\Admin\Resources\Downloads\Tables\DownloadsTable;
use App\Models\Download;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static string|UnitEnum|null $navigationGroup = 'CONTENT MANAGEMENT';

    protected static ?int $navigationSort = 40;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return DownloadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DownloadsTable::configure($table);
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
            'index' => ListDownloads::route('/'),
            'create' => CreateDownload::route('/create'),
            'edit' => EditDownload::route('/{record}/edit'),
        ];
    }
}
