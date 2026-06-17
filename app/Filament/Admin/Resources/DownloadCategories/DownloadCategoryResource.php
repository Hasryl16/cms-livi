<?php

namespace App\Filament\Admin\Resources\DownloadCategories;

use App\Filament\Admin\Resources\DownloadCategories\Pages\CreateDownloadCategory;
use App\Filament\Admin\Resources\DownloadCategories\Pages\EditDownloadCategory;
use App\Filament\Admin\Resources\DownloadCategories\Pages\ListDownloadCategories;
use App\Filament\Admin\Resources\DownloadCategories\Schemas\DownloadCategoryForm;
use App\Filament\Admin\Resources\DownloadCategories\Tables\DownloadCategoriesTable;
use App\Models\DownloadCategory;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DownloadCategoryResource extends Resource
{
    protected static ?string $model = DownloadCategory::class;

    protected static string|UnitEnum|null $navigationGroup = 'CONTENT MANAGEMENT';

    protected static ?int $navigationSort = 50;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DownloadCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DownloadCategoriesTable::configure($table);
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
            'index' => ListDownloadCategories::route('/'),
            'create' => CreateDownloadCategory::route('/create'),
            'edit' => EditDownloadCategory::route('/{record}/edit'),
        ];
    }
}
