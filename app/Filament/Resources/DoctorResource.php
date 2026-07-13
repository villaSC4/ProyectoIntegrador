<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorResource\Pages;
use App\Models\Doctor;
use App\Models\Especialidad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\CheckboxList; 
use Filament\Forms\Components\Card;
use Filament\Forms\Components\FileUpload;

class DoctorResource extends Resource
{
    protected static ?string $model = Doctor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Doctores';
    protected static ?string $pluralModelLabel = 'Doctores';
    protected static ?string $modelLabel = 'Doctor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('nombre')
                    ->required(),

                Forms\Components\Hidden::make('dni'),
                Forms\Components\Hidden::make('celular'),

                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('especialidad_id')
                        ->label('Especialidad Médica')
                        ->relationship('especialidad', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('cupos_disponibles')
                        ->label('Cupos de Citas Disponibles')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Forms\Components\Toggle::make('esta_activo')
                        ->label('Médico Disponible para Consultas')
                        ->default(true),

                    Forms\Components\FileUpload::make('ruta_imagen')
                        ->label('Fotografía de Perfil Profesional')
                        ->image()
                        ->avatar() 
                        ->directory('doctores-perfiles')
                        ->disk('public')
                        ->visibility('public')
                        ->columnSpanFull()
                        ->extraAttributes([
                            'class' => 'mx-auto flex flex-col items-center justify-center text-center pt-4'
                        ])
                        ->formatStateUsing(function ($state) {
                            if (!$state) return null;
                            
                            if (is_string($state)) {
                                if (str_contains($state, 'imagenes/doctores/')) {
                                    $archivoLimpio = str_replace('imagenes/doctores/', '', $state);
                                    return [$archivoLimpio => $archivoLimpio];
                                }
                                return [$state => $state];
                            }
                            return $state;
                        }),
                ])->columns(3),

                Forms\Components\Card::make()->schema([
                    Forms\Components\CheckboxList::make('horarios')
                        ->label('Asignar Horarios de Atención')
                        ->relationship('horarios', 'id') 
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->dia_semana} | {$record->turno} ({$record->hora_inicio} - {$record->hora_fin})")
                        ->columns(2) 
                        ->bulkToggleable() 
                        ->required(),
                ])->label('Horarios del Médico'),

                Forms\Components\Hidden::make('face_vector')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->validationAttribute('validacion biometrica'),

                ViewField::make('formulario_biometrico_completo')
                    ->view('filament.components.camara-escaneo')
                    ->columnSpanFull()
                    ->hiddenOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['especialidad', 'horarios']))
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre del Doctor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('especialidad.nombre')
                    ->label('Especialidad')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('dias_atencion')
                    ->label('Días de Atención')
                    ->state(fn (Doctor $record): string => $record->horarios
                        ->pluck('dia_semana')
                        ->unique()
                        ->values()
                        ->implode(', ') ?: 'Sin horario')
                    ->badge()
                    ->color('success'),

                TextColumn::make('cupos_disponibles')
                    ->label('Cupos Restantes')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('esta_activo')
                    ->label('Estado')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('especialidad_id')
                    ->label('Filtrar por Especialidad')
                    ->relationship('especialidad', 'nombre')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctors::route('/'),
            'create' => Pages\CreateDoctor::route('/create'),
            'edit' => Pages\EditDoctor::route('/{record}/edit'),
        ];
    }
}
