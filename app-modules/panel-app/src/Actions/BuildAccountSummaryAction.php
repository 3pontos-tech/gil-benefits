<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Actions;

use App\Models\Users\User;
use TresPontosTech\PanelApp\DTOs\AccountSummaryRow;

class BuildAccountSummaryAction
{
    /**
     * Monta as linhas do card "Sua conta".
     *
     * Cada linha reflete um dado que a aplicação realmente registra. O layout
     * original previa "Telefone: Verificado" e "Segurança: Boa", mas não existe
     * verificação de telefone nem histórico de senha no banco — a última linha
     * mostra a data da última atualização do perfil no lugar.
     *
     * @return list<AccountSummaryRow>
     */
    public function handle(User $user): array
    {
        $hasPhone = filled($user->detail?->phone_number);
        $isEmailVerified = $user->email_verified_at !== null;
        $isComplete = $hasPhone && filled($user->name) && filled($user->email);

        return [
            new AccountSummaryRow(
                icon: 'heroicon-o-user',
                label: (string) __('panel-app::profile.summary.rows.information'),
                status: (string) __($isComplete
                    ? 'panel-app::profile.summary.status.complete'
                    : 'panel-app::profile.summary.status.incomplete'),
                isPositive: $isComplete,
            ),
            new AccountSummaryRow(
                icon: 'heroicon-o-envelope',
                label: (string) __('panel-app::profile.summary.rows.email'),
                status: (string) __($isEmailVerified
                    ? 'panel-app::profile.summary.status.verified'
                    : 'panel-app::profile.summary.status.unverified'),
                isPositive: $isEmailVerified,
            ),
            new AccountSummaryRow(
                icon: 'heroicon-o-phone',
                label: (string) __('panel-app::profile.summary.rows.phone'),
                status: (string) __($hasPhone
                    ? 'panel-app::profile.summary.status.filled'
                    : 'panel-app::profile.summary.status.empty'),
                isPositive: $hasPhone,
            ),
            new AccountSummaryRow(
                icon: 'heroicon-o-clock',
                label: (string) __('panel-app::profile.summary.rows.last_updated'),
                status: $user->updated_at?->isoFormat('L')
                    ?? (string) __('panel-app::profile.fields.never_updated'),
            ),
        ];
    }
}
