<?php

namespace App\Filament\Resources\PopulationRecordResource\Pages;

use App\Filament\Resources\PopulationRecordResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPopulationRecord extends EditRecord
{
    protected static string $resource = PopulationRecordResource::class;
    protected function getRedirectUrl():string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
