<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Users\User;
use App\Support\PostHog\PostHog;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use TresPontosTech\Permissions\Roles;

/**
 * Preenche a ficha no PostHog de quem criou a conta antes de o Flamma mandar nome, e-mail e
 * telefone. A equipe interna fica de fora, como no snippet.
 */
final class IdentifyPostHogUsers extends Command
{
    protected $signature = 'posthog:identify-users';

    protected $description = 'Envia nome, e-mail e telefone de cada usuário para a pessoa no PostHog.';

    public function handle(PostHog $postHog): int
    {
        if (blank(config('services.posthog.key'))) {
            $this->warn('PostHog sem chave configurada: nada foi enviado.');

            return self::SUCCESS;
        }

        $sent = 0;

        User::query()
            ->whereDoesntHave('roles', fn (Builder $query): Builder => $query->whereIn('name', [
                Roles::SuperAdmin->value,
                Roles::Admin->value,
                Roles::Consultant->value,
                Roles::Financial->value,
                Roles::CustomerSuccess->value,
            ]))
            ->with('detail')
            ->chunkById(100, function (Collection $users) use ($postHog, &$sent): void {
                $users->each(fn (User $user) => $postHog->capture((string) $user->getKey(), '$set', ['$set' => PostHog::personProperties($user)]));
                $sent += $users->count();
            });

        $this->info(sprintf('%d usuário(s) enviado(s) ao PostHog.', $sent));

        return self::SUCCESS;
    }
}
