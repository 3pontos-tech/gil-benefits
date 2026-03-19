<?php

namespace TresPontosTech\Admin\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use TresPontosTech\Admin\Filament\Resources\Companies\Pages\CreateCompany;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\CreateUser;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\ListUsers;

class QuickActions extends Widget
{
    protected string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        $shortcuts = [
            [
                'title' => __('panel-admin::widgets.quick_actions.create_user'),
                'description' => __('panel-admin::widgets.quick_actions.create_user_description'),
                'icon' => Heroicon::UserPlus,
                'href' => CreateUser::getUrl(),
            ],
            [
                'title' => __('panel-admin::widgets.quick_actions.create_company'),
                'description' => __('panel-admin::widgets.quick_actions.create_company_description'),
                'icon' => 'heroicon-o-building-office',
                'href' => CreateCompany::getUrl(),
            ],
            [
                'title' => __('panel-admin::widgets.quick_actions.manage_users'),
                'description' => __('panel-admin::widgets.quick_actions.manage_users_description'),
                'icon' => 'heroicon-o-users',
                'href' => ListUsers::getUrl(),
            ],
        ];

        return [
            'shortcuts' => $shortcuts,
        ];
    }
}
