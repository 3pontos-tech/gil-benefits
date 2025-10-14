<?php

use Illuminate\Support\Str;
use App\Enums\AvailableTagsEnum;
use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Pages\CreateConsultant;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->admin()->create());
});

it('should render', function (): void {
    livewire(CreateConsultant::class)
        ->assertOk();
});

it('should be able to register an consultant', function (): void {

    Storage::fake('public');
    $image = UploadedFile::fake()->image('image.jpg');

    livewire(CreateConsultant::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John Doe da silva',
            'phone' => '119999999999',
            'email' => 'joe@doe.com',
            'socials_urls' => [
                'linkedin' => 'https://www.linkedin.com/in/',
                'instagram' => 'https://www.instagram.com/',
                'facebook' => 'https://www.facebook.com/',
                'twitter' => 'https://www.twitter.com/',
                'youtube' => 'https://www.youtube.com/',
            ],
            'short_description' => 'fortuna tiger',
            'avatar' => $image,
            'tags' => [AvailableTagsEnum::Expertise->value],
            'biography' => 'my biography',
            'readme' => 'readme',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertOk();

    assertDatabaseHas(Consultant::class, [
        'name' => 'John Doe da silva',
        'phone' => '119999999999',
        'email' => 'joe@doe.com',
        'socials_urls' => json_encode([
            'linkedin' => 'https://www.linkedin.com/in/',
            'instagram' => 'https://www.instagram.com/',
            'facebook' => 'https://www.facebook.com/',
            'twitter' => 'https://www.twitter.com/',
            'youtube' => 'https://www.youtube.com/',
        ]),
        'short_description' => 'fortuna tiger',
        'biography' => 'my biography',
        'readme' => 'readme',
    ]);
});

it('sets the slug after name field is set', function (): void {
    livewire(CreateConsultant::class)
        ->assertOk()
        ->fillForm([
            'name' => 'joe doe',
        ])
        ->assertSchemaStateSet([
            'slug' => Str::slug('joe doe'),
        ]);
});

describe('validation tests', function (): void {

    test('name field', function ($value, $rule): void {
        livewire(CreateConsultant::class)
            ->assertOk()
            ->fillForm([
                'name' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('email field', function ($value, $rule): void {
        livewire(CreateConsultant::class)
            ->assertOk()
            ->fillForm([
                'email' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'email' => ['notemail', 'email'],
    ]);

    test('phone field', function ($value, $rule): void {
        livewire(CreateConsultant::class)
            ->assertOk()
            ->fillForm([
                'phone' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['phone' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('short_description field', function ($value, $rule): void {
        livewire(CreateConsultant::class)
            ->assertOk()
            ->fillForm([
                'short_description' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['short_description' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'max:255' => [str_repeat('a', 256), 'max:255'],
    ]);

    test('biography field', function ($value, $rule): void {
        livewire(CreateConsultant::class)
            ->assertOk()
            ->fillForm([
                'biography' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['biography' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);

    test('readme field', function ($value, $rule): void {
        livewire(CreateConsultant::class)
            ->assertOk()
            ->fillForm([
                'readme' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['readme' => $rule]);
    })->with([
        'required' => ['', 'required'],
    ]);
});
