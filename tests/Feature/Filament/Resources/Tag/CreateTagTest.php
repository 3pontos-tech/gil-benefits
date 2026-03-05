<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Spatie\Tags\Tag;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Tags\Pages\CreateTag;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(User::factory()->admin()->create());
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
});

it('should render', function (): void {
    livewire(CreateTag::class)
        ->assertOk();
});

it('should be able to create tags', function (): void {
    livewire(CreateTag::class)
        ->assertOk()
        ->fillForm([
            'name' => 'tag name',
            'slug' => 'tag slug',
            'type' => 'tag type',
            'order_column' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Tag::class, [
        'name' => json_encode(['pt_BR' => 'tag name']),
        'slug' => json_encode(['pt_BR' => 'tag-name']),
        'type' => 'tag type',
    ]);
});

describe('validation tests', function (): void {

    test('name field', function ($value, $rule) {
        livewire(CreateTag::class)
            ->assertOk()
            ->fillForm([
                'name' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('slug field', function ($value, $rule) {
        livewire(CreateTag::class)
            ->assertOk()
            ->fillForm([
                'slug' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('order_column field', function ($value, $rule) {
        livewire(CreateTag::class)
            ->assertOk()
            ->fillForm([
                'order_column' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['order_column' => $rule]);
    })->with([
        'numeric' => ['NAN', 'numeric'],
    ]);
});
