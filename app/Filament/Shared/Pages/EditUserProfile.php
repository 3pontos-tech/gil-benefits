<?php

namespace App\Filament\Shared\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class EditUserProfile extends EditProfile
{
    public static function getLabel(): string
    {
        return 'Meu Perfil';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPhoneFormComponent(),
            ...$this->getExtraDetailFormComponents(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    protected function getPhoneFormComponent(): Component
    {
        return PhoneInput::make('phone_number')
            ->label('Telefone')
            ->defaultCountry('BR')
            ->initialCountry('BR')
            ->disableLookup()
            ->strictMode();
    }

    /**
     * @return array<Component>
     */
    protected function getExtraDetailFormComponents(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getDetailFields(): array
    {
        return ['phone_number'];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $detail = $this->getUser()->detail;

        foreach ($this->getDetailFields() as $field) {
            $data[$field] = $detail?->$field;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $detailData = Arr::only($data, $this->getDetailFields());
        $userData = Arr::except($data, $this->getDetailFields());

        parent::handleRecordUpdate($record, $userData);

        $record->detail()->updateOrCreate(
            ['user_id' => $record->getKey()],
            $detailData,
        );

        return $record;
    }
}
