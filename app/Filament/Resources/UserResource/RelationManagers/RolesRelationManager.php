<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'User Roles';

    protected static ?string $icon = 'heroicon-o-shield-check';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('name')
                    ->label('Role')
                    ->required()
                    ->options(Role::all()->pluck('name', 'name'))
                    ->searchable()
                    ->native(false)
                    ->helperText('Select a role to assign to this user')
                    ->disabled(fn (?Model $record) => $record !== null)
                    ->dehydrated(fn (?Model $record) => $record === null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger',
                        'panel_user' => 'warning',
                        default => str_contains($state, 'admin') ? 'success' :
                                  (str_contains($state, 'manager') ? 'info' :
                                  (str_contains($state, 'operator') ? 'primary' : 'gray')),
                    })
                    ->icon('heroicon-o-shield-check'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-key'),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Assign Role')
                    ->color('success')
                    ->icon('heroicon-o-plus-circle')
                    ->preloadRecordSelect()
                    ->modalHeading('Assign Role to User')
                    ->modalDescription('Select one or more roles to assign to this user')
                    ->modalSubmitActionLabel('Assign')
                    ->successNotificationTitle('Role assigned successfully')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Select Role')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->optionsLimit(50)
                            ->getOptionLabelFromRecordUsing(fn (Role $record) => ucwords(str_replace('_', ' ', $record->name))),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Role')
                    ->modalDescription('Are you sure you want to remove this role from the user?')
                    ->successNotificationTitle('Role removed successfully')
                    ->hidden(fn (Model $record) => $record->name === 'super_admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Remove Selected Roles')
                        ->modalDescription('Are you sure you want to remove selected roles from this user?')
                        ->successNotificationTitle('Roles removed successfully'),
                ]),
            ])
            ->emptyStateHeading('No roles assigned')
            ->emptyStateDescription('This user has no roles assigned yet. Assign a role to grant permissions.')
            ->emptyStateIcon('heroicon-o-shield-exclamation')
            ->emptyStateActions([
                Tables\Actions\AttachAction::make()
                    ->label('Assign First Role')
                    ->color('primary'),
            ]);
    }
}
