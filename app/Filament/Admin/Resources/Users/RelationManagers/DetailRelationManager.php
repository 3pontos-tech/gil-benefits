<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailRelationManager extends RelationManager
{
    protected static string $relationship = 'detail';

    protected static ?string $recordTitleAttribute = 'tax_id';

    protected static ?string $title = 'Details';

    protected static ?string $relatedResource = UserResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tax_id')
                    ->mask('###.###.###-##')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('document_id')
                    ->mask('##.###.###-#')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('company_id'),
            ])

            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
