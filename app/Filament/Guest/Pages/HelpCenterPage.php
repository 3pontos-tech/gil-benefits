<?php

declare(strict_types=1);

namespace App\Filament\Guest\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

/**
 * @property-read Schema $form
 */
class HelpCenterPage extends Page
{
    protected static ?string $slug = 'help-center';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.guest.pages.help-center';

    /**
     * Per-IP throttle for the public ticket form: at most MAX_ATTEMPTS
     * submissions every DECAY_SECONDS. Guards an anonymous endpoint against spam.
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function getTitle(): string
    {
        return __('support::pages.help_center.title');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')
            ->schema([
                Section::make(__('support::pages.help_center.section_visitor'))
                    ->schema([
                        TextInput::make('visitor_name')
                            ->label(__('support::pages.help_center.fields.visitor_name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('visitor_email')
                            ->label(__('support::pages.help_center.fields.visitor_email'))
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('visitor_company_name')
                            ->label(__('support::pages.help_center.fields.visitor_company_name'))
                            ->maxLength(255),
                    ]),

                Section::make(__('support::pages.help_center.section_category'))
                    ->schema([
                        Select::make('category')
                            ->label(__('support::pages.help_center.fields.category'))
                            ->options(SupportTicketCategoryEnum::class)
                            ->required()
                            ->searchable()
                            ->native(false),
                    ]),

                Section::make(__('support::pages.help_center.section_details'))
                    ->schema([
                        TextInput::make('subject')
                            ->label(__('support::pages.help_center.fields.subject'))
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('support::pages.help_center.fields.description'))
                            ->required()
                            ->rows(5),

                        FileUpload::make('attachment')
                            ->label(__('support::pages.help_center.fields.attachment'))
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(5120)
                            ->storeFiles(false)
                            ->nullable(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('submit')
                ->footer([
                    Actions::make($this->getFormActions())->key('form-actions'),
                ]),
        ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('support::pages.help_center.actions.submit'))
                ->icon(Heroicon::PaperAirplane)
                ->submit('submit'),
        ];
    }

    public function submit(): void
    {
        $key = 'help-center:' . Request::ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            Notification::make()
                ->title(__('support::pages.help_center.notifications.rate_limited', [
                    'seconds' => RateLimiter::availableIn($key),
                ]))
                ->danger()
                ->send();

            return;
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        $dto = CreateSupportTicketDTO::fromFormState(
            $this->form->getState(),
            url: Request::header('referer'),
            userAgent: Request::userAgent(),
            environment: app()->environment(),
        );

        $ticket = resolve(CreateSupportTicketAction::class)->execute($dto);

        Notification::make()
            ->title(__('support::pages.help_center.notifications.created', ['protocol' => $ticket->protocol]))
            ->success()
            ->send();

        $this->form->fill();
    }
}
