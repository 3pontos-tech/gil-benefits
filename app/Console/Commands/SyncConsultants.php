<?php

namespace App\Console\Commands;

use App\Models\Users\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

class SyncConsultants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-consultants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Consultant::query()
            ->whereDoesntHave('user')
            ->lazy()
            ->each(function (Consultant $consultant): void {
                $user = User::query()->create([
                    'name' => $consultant->name,
                    'email' => $consultant->email,
                    'password' => Hash::make($consultant->email),
                ]);

                $consultant->user()->associate($user);
                $consultant->save();

                event(new UserRegistered($user, Roles::Consultant));
            });
    }
}
