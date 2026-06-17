<?php

namespace App\Filament\Admin\Resources\SustainabilityCategories;

use App\Filament\Admin\Resources\SustainabilityCategories\Pages\CreateSustainabilityCategory;
use App\Filament\Admin\Resources\SustainabilityCategories\Pages\EditSustainabilityCategory;
use App\Filament\Admin\Resources\SustainabilityCategories\Pages\ListSustainabilityCategories;
use App\Filament\Admin\Resources\SustainabilityCategories\Schemas\SustainabilityCategoryForm;
use App\Filament\Admin\Resources\SustainabilityCategories\Tables\SustainabilityCategoriesTable;
use App\Models\SustainabilityCategory;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SustainabilityCategoryResource extends Resource
{
    protected static ?string $model = SustainabilityCategory::class;

    protected static string|UnitEnum|null $navigationGroup = 'CONTENT MANAGEMENT';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SustainabilityCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SustainabilityCategoriesTable::configure($table);
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
            'index' => ListSustainabilityCategories::route('/'),
            'create' => CreateSustainabilityCategory::route('/create'),
            'edit' => EditSustainabilityCategory::route('/{record}/edit'),
        ];
    }
}
