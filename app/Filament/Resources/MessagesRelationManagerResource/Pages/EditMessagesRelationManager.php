<?php

namespace App\Filament\Resources\MessagesRelationManagerResource\Pages;

use App\Filament\Resources\MessagesRelationManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMessagesRelationManager extends EditRecord
{
    protected static string $resource = MessagesRelationManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
