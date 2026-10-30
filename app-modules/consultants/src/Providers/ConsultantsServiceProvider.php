<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

class ConsultantsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Relation::morphMap([
            'consultants' => Consultant::class,
            'documents' => Document::class,
            'document_share' => DocumentShare::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'consultants');
    }
}
