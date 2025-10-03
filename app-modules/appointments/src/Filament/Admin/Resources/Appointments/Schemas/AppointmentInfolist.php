<?php

namespace TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('consultant.name'),
                TextEntry::make('user.name'),
                TextEntry::make('voucher.id'),
                TextEntry::make('date')
                    ->dateTime(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
