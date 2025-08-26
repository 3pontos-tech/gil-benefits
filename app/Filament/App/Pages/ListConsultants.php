<?php

namespace App\Filament\App\Pages;

use App\Models\Consultant;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use JetBrains\PhpStorm\NoReturn;

class ListConsultants extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $title = 'Consultants';

    protected string $view = 'filament.app.pages.list-consultants';

    public Collection|Consultant $consultants;

    public ?int $consultant;

    public function mount(): void
    {
        $this->consultants = Consultant::all();
    }

    #[NoReturn]
    public function updateConsultants($value): void
    {
        if (empty($value)) {
            $this->consultants = Consultant::all();
        }

        $this->consultants = Consultant::query()->when(
            $this->consultant, function ($query): void {
                $query->where('id', $this->consultant);
            }
        )->get();
    }

    public function consultantSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('consultant')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Consultant::query()->pluck('name', 'id'))
                    ->afterStateUpdated(fn ($state) => $this->updateConsultants($state)),
            ]);
    }

    public function save(): void
    {
        dd($this->consultant);
    }
}
