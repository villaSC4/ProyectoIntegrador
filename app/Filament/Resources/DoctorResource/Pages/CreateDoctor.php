<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use App\Models\ReconocimientoFacial;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    /**
     * 🚀 HOOK POST-CREACIÓN DE DOCTOR
     * Guarda el vector de la IA en la tabla polimórfica referenciando al Doctor
     */
    protected function afterCreate(): void
    {
        // El registro del doctor que se acaba de guardar en MySQL
        $doctor = $this->record; 
        
        // Extraemos el vector capturado por tu script de Face-API
        $vectorFacial = $this->form->getState()['face_vector'] ?? null;

        if ($vectorFacial) {
            ReconocimientoFacial::create([
                'vector_facial' => $vectorFacial, // El modelo Doctor lo casteará a JSON automáticamente
                'biometria_id' => $doctor->id,
                'biometria_type' => get_class($doctor), // Almacena: "App\Models\Doctor"
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}