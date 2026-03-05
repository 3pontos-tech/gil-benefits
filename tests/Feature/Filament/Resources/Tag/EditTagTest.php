<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Spatie\Tags\Tag;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Tags\Pages\EditTag;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(User::factory()->admin()->create());
    Filament::setCurrentPanel(FilamentPanel::Admin->value);

    $this->tag = Tag::query()->create([
        'name' => 'tag name',
        'slug' => 'tag-name',
        'type' => 'tag type',
    ]);
});

it('should render', function (): void {
    livewire(EditTag::class, ['record' => $this->tag->getKey()])
        ->assertOk();
});

it('should be able to update a tag', function (): void {
    livewire(EditTag::class, ['record' => $this->tag->getKey()])
        ->assertOk()
        ->fillForm([
            'name' => 'updated tag name',
            'slug' => 'updated-tag-name',
            'type' => 'updated tag type',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Tag::class, [
        'name' => json_encode(['pt_BR' => 'updated tag name']),
        'slug' => json_encode(['pt_BR' => 'updated-tag-name']),
        'type' => 'updated tag type',
    ]);

    assertDatabaseMissing(Tag::class, [
        'name' => json_encode(['pt_BR' => 'tag name']),
        'slug' => json_encode(['pt_BR' => 'tag-name']),
        'type' => 'tag type',
    ]);
});
