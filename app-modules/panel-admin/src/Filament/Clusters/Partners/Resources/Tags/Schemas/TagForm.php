<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Clusters\Partners\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('type'),
                TextInput::make('order_column')
                    ->numeric(),
            ]);
    }
}
