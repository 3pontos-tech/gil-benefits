<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void {}

    public function register(): void
    {
        Relation::morphMap([
            'users' => User::class,
            'details' => Detail::class,
        ]);
    }
}
