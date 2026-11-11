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
    case XLSX = 'xlsx';
    case Link = 'link';

    public function getColor(): array
    {
        return match ($this) {
            self::Docx => Color::Blue,
            self::JPG => Color::Purple,
            self::PDF => Color::Red,
            self::PNG => Color::Green,
            self::SVG => Color::Orange,
            self::XLSX => Color::Amber,
            self::Link => Color::Gray,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::JPG, self::PNG, self::SVG => Heroicon::Bookmark,
            self::Docx, self::PDF, self::XLSX => Heroicon::Document,
            self::Link => Heroicon::Link,
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    /**
     * O tipo de um arquivo: pelo mime type, que vem do conteúdo, e pela extensão em minúsculas
     * como reserva (`jpeg` é JPG, `PDF` é PDF). Null para o que não é um tipo aceito.
     */
    public static function fromFile(?string $mimeType, string $extension): ?self
    {
        foreach (self::cases() as $case) {
            if ($case !== self::Link && $case->getMimeType() === $mimeType) {
                return $case;
            }
        }

        $extension = mb_strtolower($extension);

        return self::tryFrom($extension === 'jpeg' ? 'jpg' : $extension);
    }

    public function getMimeType(): string
    {
        return match ($this) {
            self::JPG => 'image/jpeg',
            self::PNG => 'image/png',
            self::SVG => 'image/svg+xml',
            self::PDF => 'application/pdf',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::XLSX => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Link => 'application/link',
        };
    }
}
