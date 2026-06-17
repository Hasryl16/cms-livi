<?php

namespace App\Filament\Admin\Resources\Industries;

use App\Filament\Admin\Resources\Industries\Pages\CreateIndustry;
use App\Filament\Admin\Resources\Industries\Pages\EditIndustry;
use App\Filament\Admin\Resources\Industries\Pages\ListIndustries;
use App\Filament\Admin\Resources\Industries\Schemas\IndustryForm;
use App\Filament\Admin\Resources\Industries\Tables\IndustriesTable;
use App\Models\Industry;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;

    protected static string|UnitEnum|null $navigationGroup = 'PRODUCT MANAGEMENT';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return IndustryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndustriesTable::configure($table);
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
            'index' => ListIndustries::route('/'),
            'create' => CreateIndustry::route('/create'),
            'edit' => EditIndustry::route('/{record}/edit'),
        ];
    }
}
