<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\PanelApp\Http\Middleware\RedirectIfAnamneseNotCompleted;
use TresPontosTech\User\Database\Factories\UserAnamneseFactory;
use TresPontosTech\Vouchers\Database\Factories\VoucherRedemptionFactory;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->employee()->create();
    $this->company = Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
    $this->company->employees()->attach($this->user->getKey());

    filament()->setCurrentPanel(FilamentPanel::User->value);
    actingAs($this->user);
    filament()->setTenant($this->company);

    $this->middleware = resolve(RedirectIfAnamneseNotCompleted::class);
    $this->request = Request::create('/app/test');
    $this->next = fn (Request $request): Response => new Response('ok');
});

function voucherCreditFor(User $user, Company $company, ?string $expiresAt): UserCredit
{
    return UserCredit::factory()->create([
        'holder_id' => $user->getKey(),
        'owner_id' => $user->getKey(),
        'company_id' => $company->getKey(),
        'voucher_redemption_id' => VoucherRedemptionFactory::new()->create()->getKey(),
        'expires_at' => $expiresAt,
    ]);
}

it('lets a user with nothing to their name through', function (): void {
    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('sends a voucher holder to the anamnese', function (): void {
    voucherCreditFor($this->user, $this->company, now()->addMonth()->toDateTimeString());

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('anamnese');
});

it('stops asking once the voucher lapsed', function (): void {
    voucherCreditFor($this->user, $this->company, now()->subDay()->toDateTimeString());

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getContent())->toBe('ok');
});

it('sends a member of a contracting company to the anamnese', function (): void {
    $employer = Company::factory()->create();
    $employer->employees()->attach($this->user->getKey());
    CompanyPlan::factory()->active()->for($employer)->create();
    filament()->setTenant($employer);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('anamnese');
});

it('leaves alone whoever already answered it', function (): void {
    voucherCreditFor($this->user, $this->company, now()->addMonth()->toDateTimeString());
    UserAnamneseFactory::new()->create(['user_id' => $this->user->getKey()]);

    $response = $this->middleware->handle($this->request->duplicate(), $this->next);

    expect($response->getContent())->toBe('ok');
});

it('still asks during the grace after the consultancy', function (): void {
    config()->set('vouchers.access_grace_days', 10);

    UserCredit::factory()->used()->create([
        'holder_id' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
        'company_id' => $this->company->getKey(),
        'voucher_redemption_id' => VoucherRedemptionFactory::new()->create()->getKey(),
        'used_at' => now()->subDays(3),
    ]);

    $response = $this->middleware->handle($this->request, $this->next);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('anamnese');
});
