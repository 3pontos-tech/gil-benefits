<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Appointments\Actions\BuildUserJourneyAction;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Appointments\Support\AiCircuitBreaker;

class AppointmentsServiceProvider extends ServiceProvider
{
    /**
     * A jornada é memoizada por requisição (`scoped`): os widgets da Home do painel a leem duas
     * vezes no mesmo carregamento e compartilham o cálculo.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/appointments.php', 'appointments');

        Relation::morphMap([
            'appointments' => Appointment::class,
            'appointment_records' => AppointmentRecord::class,
            'appointment_feedbacks' => AppointmentFeedback::class,
        ]);

        $this->app->scoped(BuildUserJourneyAction::class);

        $this->app->bind(AiCircuitBreaker::class, fn (): AiCircuitBreaker => new AiCircuitBreaker(
            cooldownMinutes: (int) config('appointments.ai.circuit_cooldown_minutes', 3),
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'appointments');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'appointments');

        RateLimiter::for('appointment-record-ai', function () {
            return Limit::perMinute((int) config('appointments.ai.rate_limit_per_minute', 10));
        });

    }
}
