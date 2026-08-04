<?php

declare(strict_types=1);
use App\Filament\FilamentPanel;
use App\Filament\Guest\Pages\HelpCenterPage;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Mail::fake();
    filament()->setCurrentPanel(FilamentPanel::Guest->value);
});
it('shows both login hint lines after selecting login category', function (): void {
    livewire(HelpCenterPage::class)
        ->set('data.category', SupportTicketCategoryEnum::LoginAccess->value)
        ->assertSee('Esqueci minha senha')
        ->assertSee('cancelou o plano');
});
it('shows scheduling hint with the cancellation deadline', function (): void {
    livewire(HelpCenterPage::class)
        ->set('data.category', SupportTicketCategoryEnum::SchedulingIssue->value)
        ->assertSee(Appointment::CANCELLATION_WINDOW_HOURS . ' horas');
});
it('hides hint for categories without one', function (): void {
    livewire(HelpCenterPage::class)
        ->set('data.category', SupportTicketCategoryEnum::Bug->value)
        ->assertDontSee('Esqueci minha senha');
});
it('does not show profile link for guests', function (): void {
    livewire(HelpCenterPage::class)
        ->set('data.category', SupportTicketCategoryEnum::LoginAccess->value)
        ->assertDontSee('Alterar minha senha no perfil');
});
