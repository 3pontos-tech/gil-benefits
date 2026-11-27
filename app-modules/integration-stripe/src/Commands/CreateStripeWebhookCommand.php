<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Commands;

use Illuminate\Console\Command;
use TresPontosTech\IntegrationStripe\Enums\StripeWebhookEventEnum;
use TresPontosTech\IntegrationStripe\StripeApi;

/**
 * Cadastra o endpoint de webhook na conta do Stripe, uma vez por ambiente, com os
 * eventos que o handler trata e a versão de API do SDK.
 */
class CreateStripeWebhookCommand extends Command
{
    protected $signature = 'stripe:webhook:create
        {url? : URL pública do endpoint; padrão: a rota webhooks.stripe deste ambiente}
        {--list : Só imprime os eventos tratados, separados por vírgula}';

    protected $description = 'Cadastra no Stripe o endpoint de webhook com os eventos tratados';

    public function handle(StripeApi $api): int
    {
        if ($this->option('list')) {
            $this->line(implode(',', StripeWebhookEventEnum::values()));

            return self::SUCCESS;
        }

        $url = (string) ($this->argument('url') ?? route('webhooks.stripe'));

        if (! str_starts_with($url, 'https://')) {
            $this->error(sprintf('O Stripe só entrega webhook em https: %s', $url));

            return self::FAILURE;
        }

        $endpoint = $api->createWebhookEndpoint($url, StripeWebhookEventEnum::values());

        $this->info(sprintf('Endpoint %s cadastrado para %s.', $endpoint['id'], $url));
        $this->line('Coloque no .env deste ambiente:');
        $this->line('STRIPE_WEBHOOK_SECRET=' . $endpoint['secret']);

        return self::SUCCESS;
    }
}
