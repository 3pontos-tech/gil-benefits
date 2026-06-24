<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Filament\Schemas\Components\CategoryHint;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('support::pages.help_center.section_category'))
                ->columnSpanFull()
                ->schema([
                    Select::make('category')
                        ->label(__('support::resources.support_tickets.fields.category'))
                        ->options(SupportTicketCategoryEnum::class)
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->live(),

                    CategoryHint::make(),

                    Section::make(__('support::pages.help_center.section_details'))
                        ->schema([
                            TextInput::make('subject')
                                ->label(__('support::resources.support_tickets.fields.subject'))
                                ->required()
                                ->maxLength(255),

                            Textarea::make('description')
                                ->label(__('support::resources.support_tickets.fields.description'))
                                ->required()
                                ->rows(5),

                            FileUpload::make('attachments')
                                ->label(__('support::resources.support_tickets.fields.attachment'))
                                ->multiple()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                ->maxSize(5120)
                                ->storeFiles(false)
                                ->nullable(),
                        ]),
                ]),
        ]);
    }
}
