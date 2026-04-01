<?php

namespace TresPontosTech\Consultants\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum DocumentExtensionTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case JPG = 'jpg';
    case PDF = 'pdf';
    case Docx = 'docx';
    case SVG = 'svg';
    case PNG = 'png';

    public function getColor(): array
    {
        return match ($this) {
            self::Docx => Color::Blue,
            self::JPG => Color::Purple,
            self::PDF => Color::Red,
            self::PNG => Color::Green,
            self::SVG => Color::Orange,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::JPG, self::PNG, self::SVG => Heroicon::Bookmark,
            self::Docx, self::PDF => Heroicon::Document,
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getMimeType(): string
    {
        return match ($this) {
            self::JPG => 'image/jpeg',
            self::PNG => 'image/png',
            self::SVG => 'image/svg+xml',
            self::PDF => 'application/pdf',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }
}
