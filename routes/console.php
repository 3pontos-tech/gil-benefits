<?php

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Models\InboundWebhook;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Sleep;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;
use TresPontosTech\IntegrationVirtu\DTO\CheckoutIdentityDTO;
use TresPontosTech\IntegrationVirtu\DTO\CreatePaymentLinkDTO;
use TresPontosTech\IntegrationVirtu\Enums\VirtuIntervalEnum;
use TresPontosTech\IntegrationVirtu\VirtuClient;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new MarkAppointmentsAsCompleted)
    ->dailyAt('08:00')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Sonda de correlação da Virtu — temporário
|--------------------------------------------------------------------------
|
| Existe para responder UMA pergunta: o `data.checkoutId` que a Virtu manda no
| webhook é o mesmo id que a gente guarda ao criar o link? Hoje o adapter aposta
| que sim, e se estiver errado nenhuma assinatura ativa depois de paga.
|
| Apagar assim que a resposta estiver confirmada.
|
*/

Artisan::command('virtu:probe {--cents=100 : Valor do link, em centavos} {--trial= : Dias de trial}', function (VirtuClient $client): int {
    foreach (['VIRTU_API_KEY' => 'virtu.api_key', 'VIRTU_COMPANY_ID' => 'virtu.company_id'] as $env => $key) {
        if (blank(config($key))) {
            $this->error(sprintf('Falta %s no .env — o client não sobe sem isso.', $env));

            return Command::FAILURE;
        }
    }

    if (blank(config('virtu.webhook_secret'))) {
        $this->warn('VIRTU_WEBHOOK_SECRET vazio: o middleware devolve 401 e o payload não chega a ser gravado.');
    }

    $trial = $this->option('trial');

    // TEMPORÁRIO — dump() e não dd(), senão o link nunca chega a ser impresso.
    $this->line('<comment>Corpo enviado:</comment>');
    dump(CreatePaymentLinkDTO::subscription(
        title: 'Sonda de correlacao',
        amountCents: (int) $this->option('cents'),
        interval: VirtuIntervalEnum::Monthly,
        trialDays: $trial === null ? null : (int) $trial,
    )->toArray());

    $link = $client->createPaymentLink(CreatePaymentLinkDTO::subscription(
        title: 'Sonda de correlacao',
        amountCents: (int) $this->option('cents'),
        interval: VirtuIntervalEnum::Monthly,
        trialDays: $trial === null ? null : (int) $trial,
    ));

    // Comprador fake pré-preenchido no checkout. Além de poupar digitação, é o
    // que faz o `customer.cpf` do webhook virar um fallback utilizável caso o id
    // não bata — CPF válido no dígito verificador, senão o gateway descarta.
    $buyer = new CheckoutIdentityDTO(
        name: 'Sonda Correlacao',
        email: 'sonda+virtu@3pontos.com',
        taxId: '11144477735',
    );

    $this->line('<comment>Response já parseada:</comment>');
    dump($link);

    $url = $link->url . (str_contains($link->url, '?') ? '&' : '?') . http_build_query($buyer->toQueryParams());

    Cache::forever('virtu:probe:last', [
        'id' => $link->id,
        'checkout_id' => $link->checkoutId,
        'url' => $link->url,
        'created_at' => now()->toIso8601String(),
    ]);

    $this->newLine();
    $this->table(['o que a API devolveu', 'valor'], [
        ['id', $link->id],
        ['checkoutId (último segmento da url)', $link->checkoutId ?? '—'],
        ['status', $link->status],
        ['amountCents', (string) $link->amountCents],
    ]);

    $this->newLine();
    $this->info('Pague este link com um cartão de teste do sandbox:');
    $this->line($url);

    $this->newLine();
    $this->comment('Depois: php artisan virtu:probe:compare --wait=300');

    return Command::SUCCESS;
})->purpose('Cria um link de assinatura na Virtu com dados fake e mostra os ids');

Artisan::command('virtu:probe:compare {--wait=0 : Segundos aguardando o webhook chegar}', function (): int {
    // Tudo que sai do cache e do payload é mixed: normaliza para string|null uma
    // vez, aqui, em vez de espalhar cast por toda comparação abaixo.
    $text = static fn (mixed $value): ?string => is_scalar($value) ? (string) $value : null;

    $probe = Cache::get('virtu:probe:last');
    $probe = is_array($probe) ? $probe : [];

    $probeId = $text($probe['id'] ?? null);
    $probeCheckoutId = $text($probe['checkout_id'] ?? null);
    $probeCreatedAt = $text($probe['created_at'] ?? null);

    if ($probeId === null || $probeCreatedAt === null) {
        $this->error('Nenhum link registrado. Rode `php artisan virtu:probe` primeiro.');

        return Command::FAILURE;
    }

    $deadline = now()->addSeconds((int) $this->option('wait'));
    $webhook = null;

    // Só webhooks posteriores ao link, senão uma rodada anterior responde no
    // lugar da atual.
    while (true) {
        $webhook = InboundWebhook::query()
            ->where('source', InboundWebhookSourceEnum::Virtu)
            ->where('created_at', '>=', $probeCreatedAt)
            ->latest()
            ->first();

        if ($webhook instanceof InboundWebhook || now()->greaterThanOrEqualTo($deadline)) {
            break;
        }

        Sleep::sleep(3);
    }

    if (! $webhook instanceof InboundWebhook) {
        $this->error('Nenhum webhook da Virtu chegou depois da criação do link.');
        $this->line('Checar, nesta ordem: a URL registrada é pública e alcançável; o secret do painel');
        $this->line('bate com VIRTU_WEBHOOK_SECRET (401 não grava nada); o evento TRANSACTION está marcado.');

        return Command::FAILURE;
    }

    // StoreInboundWebhook json_encode o array e o cast `payload => 'array'` do
    // model codifica de novo, então a coluna guarda string dentro de string. O
    // cast desfaz uma camada e entrega texto; a segunda é por nossa conta.
    $payload = $webhook->getAttribute('payload');

    if (is_string($payload)) {
        $payload = json_decode($payload, true);
    }

    $payload = is_array($payload) ? $payload : [];

    $data = $payload['data'] ?? null;
    $data = is_array($data) ? $data : [];

    $customer = $data['customer'] ?? null;
    $customer = is_array($customer) ? $customer : [];

    $received = $text($data['checkoutId'] ?? null);

    $this->newLine();
    $this->line('<comment>Payload cru:</comment>');
    $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $this->newLine();
    $this->table(['id', 'valor', 'bate com o webhook?'], [
        ['link->id (API)', $probeId, $received !== null && $received === $probeId ? 'SIM' : 'não'],
        ['link->checkoutId (url)', $probeCheckoutId ?? '—', $received !== null && $received === $probeCheckoutId ? 'SIM' : 'não'],
        ['webhook data.checkoutId', $received ?? '—', '—'],
    ]);

    $match = match (true) {
        $received !== null && $received === $probeCheckoutId => 'checkoutId extraído da url — que é exatamente o que createCheckout grava hoje',
        $received !== null && $received === $probeId => 'id da API (pl_…) — trocar o que createCheckout persiste',
        default => null,
    };

    $this->newLine();

    if ($match !== null) {
        $this->info('Correlação FUNCIONA: bateu com o ' . $match . '.');
    } else {
        $this->error('Correlação QUEBRADA: nenhum dos ids guardados aparece no webhook.');
        $this->line('Fallbacks presentes no payload:');
        $this->line('  customer.cpf = ' . ($text($customer['cpf'] ?? null) ?? '—'));
        $this->line('  saleId       = ' . ($text($data['saleId'] ?? null) ?? '—'));
    }

    $subscriptions = $data['subscriptions'] ?? null;
    $subscriptions = is_array($subscriptions) ? $subscriptions : [];

    $this->newLine();
    $this->line('<comment>data.subscriptions[]:</comment> ' . ($subscriptions === []
        ? 'VAZIO — não veio marcado como cobrança de assinatura'
        : (string) json_encode($subscriptions, JSON_UNESCAPED_SLASHES)));

    return $match === null ? Command::FAILURE : Command::SUCCESS;
})->purpose('Compara o checkoutId do webhook recebido com os ids do link criado');
