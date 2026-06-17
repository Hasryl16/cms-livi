<?php

namespace App\Filament\Admin\Resources\SustainabilityContents;

use App\Filament\Admin\Resources\SustainabilityContents\Pages\CreateSustainabilityContent;
use App\Filament\Admin\Resources\SustainabilityContents\Pages\EditSustainabilityContent;
use App\Filament\Admin\Resources\SustainabilityContents\Pages\ListSustainabilityContents;
use App\Filament\Admin\Resources\SustainabilityContents\Schemas\SustainabilityContentForm;
use App\Filament\Admin\Resources\SustainabilityContents\Tables\SustainabilityContentsTable;
use App\Models\SustainabilityContent;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SustainabilityContentResource extends Resource
{
    protected static ?string $model = SustainabilityContent::class;

    protected static string|UnitEnum|null $navigationGroup = 'CONTENT MANAGEMENT';

    protected static ?int $navigationSort = 80;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SustainabilityContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SustainabilityContentsTable::configure($table);
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
            'index' => ListSustainabilityContents::route('/'),
            'create' => CreateSustainabilityContent::route('/create'),
            'edit' => EditSustainabilityContent::route('/{record}/edit'),
        ];
    }
}
