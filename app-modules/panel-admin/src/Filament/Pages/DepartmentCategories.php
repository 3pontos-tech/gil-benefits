<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use TresPontosTech\Company\Enums\DepartmentCategory;
use TresPontosTech\Company\Models\Department;

class DepartmentCategories extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.pages.department_categories.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel-admin::resources.pages.department_categories.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->getCategoryData())
            ->columns([
                TextColumn::make('case')
                    ->label(__('panel-admin::resources.pages.department_categories.table.category'))
                    ->badge(),
                TextColumn::make('total')
                    ->label(__('panel-admin::resources.pages.department_categories.table.departments_count'))
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false);
    }

    private function getCategoryData(): array
    {
        $counts = Department::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return collect(DepartmentCategory::cases())
            ->map(fn (DepartmentCategory $case): array => [
                'case' => $case,
                'total' => $counts->get($case->value, 0),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
