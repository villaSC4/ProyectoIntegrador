<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use App\Models\ReconocimientoFacial;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    protected function afterCreate(): void
    {
        $estadoFormulario = $this->form->getRawState();
        $vectorFacial = $estadoFormulario['face_vector'] ?? null;

        if ($vectorFacial) {
            $this->record->reconocimientosFaciales()->create([
                'vector_facial' => $vectorFacial,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}