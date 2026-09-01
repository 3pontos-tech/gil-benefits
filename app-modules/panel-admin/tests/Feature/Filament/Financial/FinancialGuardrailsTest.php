<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use TresPontosTech\PanelAdmin\Filament\Pages\Dashboard;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\Billing;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\CompaniesAndContracts;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\ConsultingUsage;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\RevenueDashboard;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\UsersAndUsage;
use TresPontosTech\PanelAdmin\Support\RevenueReconstructor;

/**
 * Gates do épico FLM-41.
 *
 * As duas decisões estruturais do cockpit — não conhecer gateway e não exibir
 * previsão — estavam apoiadas só em disciplina. Estes testes as tornam
 * verificáveis: quebrar qualquer uma delas quebra a suíte.
 */
arch('o cockpit financeiro não conhece gateway')
    ->expect([
        'TresPontosTech\PanelAdmin\Actions\Financial',
        'TresPontosTech\PanelAdmin\DTOs\Financial',
        'TresPontosTech\PanelAdmin\Filament\Pages\Financial',
        'TresPontosTech\PanelAdmin\Filament\Widgets\Financial',
        RevenueReconstructor::class,
    ])
    ->not->toUse([
        'TresPontosTech\IntegrationVirtu',
        'TresPontosTech\Billing\Barte',
        'TresPontosTech\Billing\Stripe',
    ]);

it('não expõe nenhum campo de previsão de receita na tela', function (): void {
    // D-18: o cockpit não exibe dinheiro que ainda não aconteceu. O teste olha o
    // texto que chega ao usuário, e não o código, porque é o rótulo na tela que
    // faz alguém ler um palpite como fato.
    $forbidden = '/proje[çc]|previs[ãa]o|estimativa de receita/i';

    foreach (['pt_BR', 'en'] as $locale) {
        /** @var array<string, mixed> $widgets */
        $widgets = require base_path(sprintf('app-modules/panel-admin/lang/%s/widgets.php', $locale));

        /** @var array<string, mixed> $financial */
        $financial = $widgets['financial'] ?? [];

        $offenders = collect(data_get($financial, '*.*'))
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match($forbidden, $value) === 1)
            ->all();

        expect($offenders)->toBe([], 'Texto de previsão encontrado em ' . $locale);
    }
});

it('não chama o repasse de custo nem de preço na tela', function (): void {
    // Repasse é o que a Flamma paga por consultoria; preço é quanto a consultoria
    // vale, e a plataforma não sabe esse segundo número. Chamar um de outro faz
    // quem preenche o cadastro digitar a coisa errada — foi o que aconteceu na
    // primeira versão do rótulo.
    $forbidden = '/\bcusto|\bpre[çc]o|\bcost\b|\bprice\b/i';

    foreach (['pt_BR', 'en'] as $locale) {
        /** @var array<string, mixed> $widgets */
        $widgets = require base_path(sprintf('app-modules/panel-admin/lang/%s/widgets.php', $locale));

        /** @var array<string, mixed> $payout */
        $payout = data_get($widgets, 'financial.payout', []);

        /** @var array<string, mixed> $consultants */
        $consultants = data_get(
            require base_path(sprintf('app-modules/panel-admin/lang/%s/resources.php', $locale)),
            'consultants',
            [],
        );

        $offenders = collect([...array_values($payout), ...array_values($consultants)])
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match($forbidden, $value) === 1)
            // O texto de ajuda do cadastro pode dizer "preço" justamente para
            // negar que seja um: é a única forma de desfazer a confusão.
            ->reject(fn (string $value): bool => str_contains($value, 'não é o preço')
                || str_contains($value, 'not the price'))
            ->all();

        expect($offenders)->toBe([], 'Repasse tratado como custo ou preço em ' . $locale);
    }
});

it('mantém o dashboard padrão do Admin sem widget financeiro', function (): void {
    // O guarda-rail do épico é não deixar o Admin mais lento. A garantia é
    // estrutural: quem não abre o financeiro não paga por ele.
    $widgets = (new Dashboard)->getWidgets();

    $financial = array_filter(
        $widgets,
        fn (mixed $widget): bool => is_string($widget) && str_contains($widget, '\\Financial\\'),
    );

    expect($financial)->toBe([]);
});

describe('custo de carregamento das páginas financeiras', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        actingAsFinancial();
        seedFinancialBaseline();
    });

    it('mantém o número de consultas independente do tamanho da base', function (string $page): void {
        Cache::flush();
        DB::enableQueryLog();
        Livewire::test($page)->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Teto generoso de propósito: o que importa é não crescer por linha da
        // base. A base semeada tem 12 empresas — um N+1 estouraria fácil.
        expect($queries)->toBeLessThan(60);
    })->with([
        'receita' => RevenueDashboard::class,
        'empresas e contratos' => CompaniesAndContracts::class,
        'cobranças' => Billing::class,
        'consultorias' => ConsultingUsage::class,
        'usuários e utilização' => UsersAndUsage::class,
    ]);
});
