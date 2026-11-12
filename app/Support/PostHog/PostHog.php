<?php

declare(strict_types=1);

namespace App\Support\PostHog;

use App\Models\Users\User;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * Eventos do Flamma enviados ao PostHog, no mesmo projeto do Flare. Nada sai sem
 * `services.posthog.key`. O momento e o uuid do evento são fixados aqui, então um job atrasado
 * ou repetido mantém os dois. `$geoip_disable` porque o IP do servidor não é o da pessoa.
 */
final class PostHog
{
    /**
     * @param  array<string, string|int|bool|array<string, string>>  $properties
     */
    public function capture(string $distinctId, string $event, array $properties = []): void
    {
        if (blank(config('services.posthog.key'))) {
            return;
        }

        dispatch(new CapturePostHogEvent(
            distinctId: $distinctId,
            event: $event,
            properties: [...$properties, 'source' => 'flamma', '$geoip_disable' => true],
            timestamp: now()->toIso8601String(),
            uuid: (string) Str::uuid(),
        ))->afterCommit();
    }

    /**
     * Junta os eventos de `$anonymousId` na pessoa de `$userId`. O id anônimo vai como `alias`
     * porque o PostHog só funde uma pessoa que nunca foi identificada.
     */
    public function alias(string $anonymousId, string $userId): void
    {
        $this->capture($userId, '$create_alias', ['alias' => $anonymousId]);
    }

    /**
     * Ficha da pessoa no PostHog: nome, e-mail e o telefone em E.164, quando há um válido.
     *
     * @return array{email: string, name: string, phone?: string}
     */
    public static function personProperties(User $user): array
    {
        $properties = ['email' => $user->email, 'name' => $user->name];
        $phone = new PhoneNumber((string) $user->detail?->phone_number, 'BR');

        if ($phone->isValid()) {
            $properties['phone'] = $phone->formatE164();
        }

        return $properties;
    }
}
