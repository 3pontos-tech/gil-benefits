<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Companies\Pages\CreateCompany;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use TresPontosTech\Plans\Filament\Admin\Resources\Plans\Pages\CreatePlan;

class QuickActions extends Widget
{
    protected string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        $shortcuts = [
            [
                'title' => 'Criar Usuário',
                'description' => 'Adicionar novo usuário ao sistema',
                'icon' => Heroicon::UserPlus,
                'href' => CreateUser::getUrl(),
            ],
            [
                'title' => 'Criar Empresa',
                'description' => 'Cadastrar nova empresa na plataforma',
                'icon' => 'heroicon-o-building-office',
                'href' => CreateCompany::getUrl(),
            ],
            [
                'title' => 'Criar Plano',
                'description' => 'Configurar novo plano de assinatura',
                'icon' => 'heroicon-o-credit-card',
                'href' => CreatePlan::getUrl(),
            ],
            [
                'title' => 'Gerenciar Usuários',
                'description' => 'Visualizar e editar usuários existentes',
                'icon' => 'heroicon-o-users',
                'href' => ListUsers::getUrl(),
            ],
        ];

        return [
            'shortcuts' => $shortcuts,
        ];
    }
}
