<?php

namespace TresPontosTech\Billing\Core\Pages;

use Filament\Pages\Page;

class SubscriptionPage extends Page
{
    protected static ?string $slug = 'available-subscriptions';

    protected static string $layout = 'filament-panels::components.layout.simple';


    protected string $view = 'available-subscriptions';
}
