# Presentation Layer

The `panel-*` modules (`panel-admin`, `panel-app`, `panel-company`,
`panel-consultant`) are the **presentation layer**. They own UI concerns: Filament
Resources, Pages, Widgets, Clusters, Livewire components, and Blade views.

## Rule

Domain logic (Actions, Models, DTOs, business rules) belongs in domain modules
(`appointments`, `billing`, `company`, `consultants`, `user`, …), never in
presentation modules.

Presentation modules import from domain modules.
Domain modules never import from presentation modules.

## Filament (v5)

Research Filament 5.x before implementing, using the `search-docs` MCP tool (or
`context7` if available). Always import domain classes into Pages, Resources, Widgets,
and Livewire components with `use` statements — keep the presentation layer decoupled
from domain internals.

When a Filament Action triggers domain behaviour, wrap a domain Action in a Filament
Action class. The Filament Action stays focused on UI; the domain Action does the work.

@verbatim
<code-snippet name="Filament Action wrapping a Domain Action" lang="php">
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Company\Models\Company;

class RegisterSubscriptionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-company::subscription.actions.register.label'))
            ->icon(Heroicon::PlusCircle)
            ->color(Color::Sky)
            ->action(function (array $data): void {
                /** @var Company|null $company */
                $company = filament()->getTenant();

                resolve(RegisterSubscription::class)
                    ->execute(RegisterSubscriptionData::fromArray($data));

                Notification::make()->success()->send();
            });
    }
}
</code-snippet>
@endverbatim

## Livewire (v4)

When working in the presentation layer, activate any available Livewire skill before
building components, and follow the existing component conventions in the panel you are
editing.
