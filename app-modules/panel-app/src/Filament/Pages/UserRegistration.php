<?php

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Closure;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Field;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

final class UserRegistration extends Register
{
    #[Override]
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            TaxIdInput::make()
                ->rule(fn (Field $component): Closure => $this->uniqueDetailRule('tax_id', $component->getLabel(), withTrashed: true)),
            DocumentIdInput::make()
                ->rule(fn (Field $component): Closure => $this->uniqueDetailRule('document_id', $component->getLabel())),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    /**
     * Validate uniqueness against the dehydrated (unmasked) value, since the
     * form state is masked but the column stores only digits.
     *
     * The database constraint on (document_id, deleted_at) does not prevent
     * duplicates among active rows (Postgres treats NULL deleted_at as
     * distinct), so this application rule is what actually enforces it.
     * tax_id passes withTrashed to mirror its plain column-level constraint.
     */
    private function uniqueDetailRule(string $column, ?string $attributeLabel, bool $withTrashed = false): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($column, $attributeLabel, $withTrashed): void {
            $normalized = preg_replace('/\D/', '', (string) $value);

            if ($normalized === null || $normalized === '') {
                return;
            }

            $query = Detail::query()->where($column, $normalized);

            if ($withTrashed) {
                $query->withTrashed();
            }

            if ($query->exists()) {
                $fail(__('validation.unique', ['attribute' => $attributeLabel ?? $attribute]));
            }
        };
    }

    #[Override]
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = parent::handleRegistration($data);
        event(new UserRegistered($user, Roles::Employee));
        $user->detail()->create([
            'tax_id' => $data['tax_id'],
            'document_id' => $data['document_id'],
        ]);

        return $user;
    }
}
