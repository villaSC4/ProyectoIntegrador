<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorResource\Pages;
use App\Models\Doctor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;

class DoctorResource extends Resource
{
    protected static ?string $model = Doctor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group'; 

    protected static ?string $navigationLabel = 'Doctores';

    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Doctor')
                    ->description('Complete los datos del personal médico para el sistema MediSign-ID.')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('especialidad')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('horario')
                            ->placeholder('Ej: 10am - 2pm')
                            ->required(),
                        
                        Forms\Components\Select::make('turno')
                            ->options([
                                'mañana' => 'Turno Mañana', 
                                'tarde' => 'Turno Tarde',   
                                'noche' => 'Turno Noche',    
                            ])
                            ->required()
                            ->native(false), 

                        Forms\Components\TextInput::make('cupos_disponibles')
                            ->label('Cupos de atención')
                            ->numeric()
                            ->default(4)
                            ->required(),

                        Forms\Components\FileUpload::make('ruta_imagen')
                            ->label('Fotografía')
                            ->image()
                            ->directory('doctores')
                            ->visibility('public'),

                        Forms\Components\Toggle::make('esta_activo')
                            ->label('¿Está activo para citas?')
                            ->default(true),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('ruta_imagen')
                    ->label('Foto')
                    ->circular(), 

                TextColumn::make('nombre')
                    ->label('Nombre del Doctor')
                    ->searchable() 
                    ->sortable(),   

                TextColumn::make('especialidad')
                    ->label('Especialidad')
                    ->badge() 
                    ->color('success'),

                TextColumn::make('turno')
                    ->label('Turno')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('cupos_disponibles')
                    ->label('Cupos')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('esta_activo')
                    ->label('Estado')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true), 
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('turno')
                    ->options([
                        'mañana' => 'Mañana',
                        'tarde' => 'Tarde',
                        'noche' => 'Noche',
                    ]),
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

    public static function getRelations(): array
    {
        return [];
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