<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DashboardSettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class DashboardSettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Dashboard Settings';
    protected static ?string $modelLabel = 'Setting';
    protected static ?string $pluralModelLabel = 'Settings';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Settings')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Setting Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., app_name, default_currency')
                            ->helperText('Unique identifier for this setting'),
                            
                        Forms\Components\TextInput::make('label')
                            ->label('Display Label')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Application Name')
                            ->helperText('User-friendly name for this setting'),
                            
                        Forms\Components\Select::make('type')
                            ->label('Value Type')
                            ->required()
                            ->options([
                                'string' => 'Text',
                                'number' => 'Number',
                                'boolean' => 'True/False',
                                'json' => 'JSON Data',
                                'file' => 'File Upload',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('value', null)),
                            
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('value')
                                ->label('Text Value')
                                ->maxLength(1000)
                                ->visible(fn (Forms\Get $get): bool => $get('type') === 'string'),
                                
                            Forms\Components\TextInput::make('value')
                                ->label('Number Value')
                                ->numeric()
                                ->visible(fn (Forms\Get $get): bool => $get('type') === 'number'),
                                
                            Forms\Components\Toggle::make('value')
                                ->label('Boolean Value')
                                ->visible(fn (Forms\Get $get): bool => $get('type') === 'boolean'),
                                
                            Forms\Components\Textarea::make('value')
                                ->label('JSON Value')
                                ->rows(4)
                                ->placeholder('{"key": "value"}')
                                ->helperText('Enter valid JSON format')
                                ->visible(fn (Forms\Get $get): bool => $get('type') === 'json'),
                                
                            Forms\Components\FileUpload::make('value')
                                ->label('File Upload')
                                ->disk('public')
                                ->directory('settings')
                                ->visibility('public')
                                ->visible(fn (Forms\Get $get): bool => $get('type') === 'file'),
                        ]),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Describe what this setting controls')
                            ->helperText('Explain the purpose and usage of this setting'),
                            
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'general' => 'General',
                                'appearance' => 'Appearance',
                                'notifications' => 'Notifications',
                                'security' => 'Security',
                                'integrations' => 'Integrations',
                                'reports' => 'Reports',
                                'advanced' => 'Advanced',
                            ])
                            ->default('general'),
                            
                        Forms\Components\TextInput::make('default_value')
                            ->label('Default Value')
                            ->maxLength(1000)
                            ->helperText('Default value if setting is reset'),
                            
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Setting')
                            ->helperText('Can be accessed without authentication')
                            ->default(false),
                            
                        Forms\Components\Toggle::make('is_system')
                            ->label('System Setting')
                            ->helperText('Cannot be deleted by users')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'appearance' => 'info',
                        'notifications' => 'warning',
                        'security' => 'danger',
                        'integrations' => 'success',
                        'reports' => 'primary',
                        'advanced' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Setting key copied')
                    ->fontFamily('mono'),
                    
                Tables\Columns\TextColumn::make('label')
                    ->label('Display Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string' => 'gray',
                        'number' => 'info',
                        'boolean' => 'success',
                        'json' => 'warning',
                        'file' => 'primary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Current Value')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        return match ($record->type) {
                            'boolean' => $state ? 'Yes' : 'No',
                            'json' => 'JSON Data',
                            'file' => $state ? 'File Uploaded' : 'No File',
                            default => $state,
                        };
                    }),
                    
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state->format('d/m/Y H:i:s')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'general' => 'General',
                        'appearance' => 'Appearance',
                        'notifications' => 'Notifications',
                        'security' => 'Security',
                        'integrations' => 'Integrations',
                        'reports' => 'Reports',
                        'advanced' => 'Advanced',
                    ]),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('Value Type')
                    ->options([
                        'string' => 'Text',
                        'number' => 'Number',
                        'boolean' => 'True/False',
                        'json' => 'JSON Data',
                        'file' => 'File Upload',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Settings')
                    ->placeholder('All settings')
                    ->trueLabel('Public only')
                    ->falseLabel('Private only'),
                    
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('System Settings')
                    ->placeholder('All settings')
                    ->trueLabel('System only')
                    ->falseLabel('Custom only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('primary'),
                    
                Tables\Actions\Action::make('reset')
                    ->label('Reset to Default')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => !empty($record->default_value))
                    ->requiresConfirmation()
                    ->modalHeading('Reset Setting to Default')
                    ->modalDescription('Are you sure you want to reset this setting to its default value?')
                    ->action(function ($record) {
                        $record->update(['value' => $record->default_value]);
                        
                        Notification::make()
                            ->title('Setting Reset')
                            ->body("Setting '{$record->label}' has been reset to default value.")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $systemRecords = $records->where('is_system', true);
                            if ($systemRecords->isNotEmpty()) {
                                Notification::make()
                                    ->title('Cannot Delete System Settings')
                                    ->body('Some selected settings are system settings and cannot be deleted.')
                                    ->warning()
                                    ->send();
                                    
                                $records = $records->where('is_system', false);
                            }
                            
                            if ($records->isNotEmpty()) {
                                $records->each->delete();
                                
                                Notification::make()
                                    ->title('Settings Deleted')
                                    ->body(count($records) . ' settings have been deleted.')
                                    ->success()
                                    ->send();
                            }
                        }),
                        
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Settings')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $settings = $records->map(function ($record) {
                                return [
                                    'key' => $record->key,
                                    'label' => $record->label,
                                    'type' => $record->type,
                                    'value' => $record->value,
                                    'category' => $record->category,
                                    'description' => $record->description,
                                    'default_value' => $record->default_value,
                                    'is_public' => $record->is_public,
                                    'is_system' => $record->is_system,
                                ];
                            });
                            
                            $fileName = 'settings_export_' . now()->format('Y-m-d_H-i-s') . '.json';
                            $filePath = storage_path('app/public/exports/' . $fileName);
                            
                            if (!file_exists(dirname($filePath))) {
                                mkdir(dirname($filePath), 0755, true);
                            }
                            
                            file_put_contents($filePath, json_encode($settings, JSON_PRETTY_PRINT));
                            
                            Notification::make()
                                ->title('Settings Exported')
                                ->body("Settings exported to {$fileName}")
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download')
                                        ->label('Download')
                                        ->url(asset('storage/exports/' . $fileName))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        }),
                ])
            ])
            ->defaultSort('category')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDashboardSettings::route('/'),
            'create' => Pages\CreateDashboardSetting::route('/create'),
            'edit' => Pages\EditDashboardSetting::route('/{record}/edit'),
        ];
    }
}
