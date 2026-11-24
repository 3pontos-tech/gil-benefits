<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportAreaEnum: string
{
    case Home = 'home';
    case Companies = 'empresas';
    case Collaborators = 'colaboradores';
    case Privacy = 'privacidade';
    case EmployeePanel = 'painel_colaborador';
    case CompanyPanel = 'painel_empresa';

    public function whatsappMessage(): string
    {
        $origin = match ($this) {
            self::Home => 'Estou no site do Flamma, na página inicial.',
            self::Companies => 'Estou no site do Flamma, na página Para Empresas.',
            self::Collaborators => 'Estou no site do Flamma, na página Para Você.',
            self::Privacy => 'Estou no site do Flamma, na página de Privacidade.',
            self::EmployeePanel => 'Estou no painel do colaborador do Flamma.',
            self::CompanyPanel => 'Estou no painel da empresa do Flamma.',
        };

        return 'Olá! Gostaria de falar com o suporte. ' . $origin;
    }
}
