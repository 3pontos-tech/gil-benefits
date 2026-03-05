<?php

use App\Filament\Admin\Clusters\Partners\Resources\Tags\Pages\ListTags;
use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Spatie\Tags\Tag;

use function Pest\Laravel\actingAs;
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
    livewire(ListTags::class)
        ->assertOk();
});
