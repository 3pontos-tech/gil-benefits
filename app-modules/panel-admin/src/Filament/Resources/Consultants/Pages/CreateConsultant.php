<?php

namespace TresPontosTech\Admin\Filament\Resources\Consultants\Pages;

use App\Models\Users\User;
use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Admin\Filament\Resources\Consultants\ConsultantResource;
use TresPontosTech\Consultants\Models\Consultant;

class CreateConsultant extends CreateRecord
{
    protected static string $resource = ConsultantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['short_description'] ??= '';

        $data['readme'] ??= '';

        $data['biography'] ??= '';

        return $data;
    }

    protected function afterCreate()
    {

        $user = User::query()->create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'password' => $this->data['email'],
        ]);

        /** @var Consultant $record */
        $record = $this->record;
        $record->user()->associate($user)->save();
    }
}
