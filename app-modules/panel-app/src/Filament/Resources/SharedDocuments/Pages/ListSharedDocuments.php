<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Resources\SharedDocuments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\App\Filament\Resources\SharedDocuments\SharedDocumentResource;

class ListSharedDocuments extends ListRecords
{
    protected static string $resource = SharedDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('panel-app::resources.documents.form.heading'))
                ->visible(fn (): bool => $this->activeTab === 'mine'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'shared' => Tab::make(__('panel-app::resources.documents.tabs.shared'))
                ->modifyQueryUsing(fn ($query) => $query?->where('active', 1)
                    ->whereHas('shares', fn ($subquery) => $subquery?->where('employee_id', auth()->user()->getKey())
                        ->where('active', 1)
                    )),

            'mine' => Tab::make(__('panel-app::resources.documents.tabs.mine'))
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('documentable_type', auth()->user()->getMorphClass())
                    ->where('documentable_id', auth()->user()->getKey())
                ),
        ];
    }
}
