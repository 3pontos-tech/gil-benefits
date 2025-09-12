<?php

namespace App\Filament\Company\Pages\Tenancy;

use App\Models\Users\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EditCompany extends EditTenantProfile implements HasTable
{
    use InteractsWithTable;

    public static function getLabel(): string
    {
        return 'Company Settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255)
                    ->live(onBlur: true, debounce: 500)
                    ->afterStateUpdated(function (Set $set, $state): void {
                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('tax_id')
                    ->mask('99.999.999/9999-99'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(filament()->getTenant()->employees()->getQuery())
            ->headerActions([
                CreateAction::make('Invite Member')
                    ->model(User::class)
                    ->after(function ($record) {
                        filament()->getTenant()->employees()->attach($record, ['role' => 'employee']);
                    })
                    ->schema([

                        TextInput::make('name'),
                        TextInput::make('email'),
                        TextInput::make('password')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->password(),
                        Grid::make(1)
                            ->relationship('detail')
                            ->schema([
                                TextInput::make('tax_id'),
                                TextInput::make('document_id'),
                                TextInput::make('phone_number'),
                            ]),
                    ]),
            ])
            ->recordActions([
                DetachAction::make()
                    ->action(fn ($record) => filament()->getTenant()->employees()->detach($record)),

            ])
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('role')
                    ->color(fn ($state): string => match ($state) {
                        'owner' => 'danger',
                        'manager' => 'warning',
                        'employee' => 'success',
                    })
                    ->badge(),
                TextColumn::make('vouchers_used_count')
                    ->state(fn ($record) => $record->appointments()->count()),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                EmbeddedTable::make(),
            ]);
    }
}
