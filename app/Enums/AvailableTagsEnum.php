<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum AvailableTagsEnum: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Language = 'language';

    case Specialization = 'specialization';

    case Expertise = 'expertise';

    case Education = 'education';

    public function getColor(): array
    {
        return match ($this) {
            self::Language => Color::Blue,
            self::Specialization => Color::Red,
            self::Expertise => Color::Green,
            self::Education => Color::Yellow,
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return 'Campo para descrição';
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Language => Heroicon::Language,
            self::Specialization => Heroicon::Tag,
            self::Expertise => Heroicon::Briefcase,
            self::Education => Heroicon::BookOpen,
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Language => 'Idiomas',
            self::Specialization => 'Especializações',
            self::Expertise => 'Áreas de Atuação',
            self::Education => 'Formações Acadêmicas',
        };
    }

    public function getDefault(): array
    {
        return match ($this) {
            self::Language => ['Português', 'Inglês', 'Espanhol'],
            self::Specialization => ['Laravel', 'Back-end', 'Fullstack'],
            self::Expertise => ['Desenvolvimento Web', 'Desenvolvimento Mobile', 'Data Science'],
            self::Education => ['Bacharelado em Google', 'MBA em Pesquisas no StackOverflow']
        };
    }
}
