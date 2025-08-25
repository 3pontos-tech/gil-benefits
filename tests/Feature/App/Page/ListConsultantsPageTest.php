<?php

use App\Filament\App\Pages\ListConsultants;
use App\Models\Consultant;
use App\Models\Users\User;
use Livewire\Livewire;

it('should render', function () {
    filament()->setCurrentPanel('app');
    Consultant::factory()->count(10)->create();

    Livewire::actingAs(User::factory()->create())
        ->test(ListConsultants::class)
        ->assertOk();
});
it('should list all consultants', function () {
    filament()->setCurrentPanel('app');
    $consultants = Consultant::factory()->count(10)->create();

    $component = Livewire::actingAs(User::factory()->create())
        ->test(ListConsultants::class)
        ->assertOk();

    $consultants->each(function (Consultant $consultant) use ($component) {
        $component->assertSee($consultant->name);
        $component->assertSee($consultant->description);
        $component->assertSee($consultant->email);
        $component->assertSee($consultant->phone);
    });
});
it('should filter consultants', function () {
    filament()->setCurrentPanel('app');
    $consultants = Consultant::factory()->count(10)->create();
    $consultant = Consultant::factory()->createOne();

    $component = Livewire::actingAs(User::factory()->create())
        ->test(ListConsultants::class)
        ->set('consultant', $consultant->getKey())
        ->assertOk();

    $consultants->each(function (Consultant $consultant) use ($component) {
        $component->assertDontSeeText($consultant->name);
        $component->assertDontSeeText($consultant->description);
        $component->assertDontSeeText($consultant->email);
        $component->assertDontSeeText($consultant->phone);
    });

    $component->assertSeeText($consultant->name);
    $component->assertSeeText($consultant->description);
    $component->assertSeeText($consultant->email);
    $component->assertSeeText($consultant->phone);
});
