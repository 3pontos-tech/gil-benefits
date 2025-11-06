<?php

namespace TresPontosTech\Tenant\Filament\Pages\Tenancy;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile as BaseEditTenantProfile;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Filament\Actions\CreateAndAttachAction;
use TresPontosTech\Tenant\Filament\Actions\TenantSeatsCounterAction;

class EditTenantProfile extends BaseEditTenantProfile implements HasTable
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
            ->heading('Lista de Membros ativos')
            ->headerActions([
                TenantSeatsCounterAction::make(),
                CreateAndAttachAction::make('Invite Member')
                    ->model(User::class),
            ])
            ->recordActions([
                Action::make('toggle-active')
                    ->label(fn ($record): string => $record->active ? 'Desativar' : 'Ativar')
                    ->action(function (User $record): void {
                        /** @var Company $company */
                        $company = filament()->getTenant();

                        $company->employees()->updateExistingPivot($record, [
                            'active' => ! $record->active,
                            'role' => $record->role,
                        ]);
                    }),
                DetachAction::make()

                    ->action(fn ($record) => filament()->getTenant()->employees()->detach($record)),
            ])
            ->columns([
                TextColumn::make('active')
                    ->badge()
                    ->label('Status')
                    ->formatStateUsing(fn ($state): string => $state ? 'Ativo' : 'Inativo')
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('role')
                    ->color(fn ($state) => CompanyRoleEnum::from($state)->getColor())
                    ->formatStateUsing(fn ($state) => CompanyRoleEnum::from($state)->getLabel())
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
