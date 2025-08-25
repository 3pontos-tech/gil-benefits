<?php

namespace App\Filament\App\Pages;

use App\Models\Consultant;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

class ListConsultants extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.app.pages.list-consultants';
    public $consultant;

    #[Computed]
    public function consultants(): Collection
    {
        return Consultant::query()->get();
    }

    public function consultantSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('consultant')
                    ->searchable()
                    ->preload()
                    ->options(fn()=> $this->consultants()->pluck('name', 'id'))
//                    ->afterStateUpdated(),
            ]);
    }
}
