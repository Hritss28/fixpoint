<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Reports';
    protected static ?string $modelLabel = 'Report';
    protected static ?string $pluralModelLabel = 'Reports';
    protected static ?string $navigationGroup = 'Reports & Analytics';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Report Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Monthly Sales Report'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Describe what this report shows'),
                            
                        Forms\Components\Select::make('type')
                            ->label('Report Type')
                            ->required()
                            ->options([
                                'sales' => 'Sales Report',
                                'inventory' => 'Inventory Report',
                                'receivables' => 'Receivables Report',
                                'supplier' => 'Supplier Report',
                                'customer' => 'Customer Report',
                                'financial' => 'Financial Report',
                                'custom' => 'Custom Report',
                            ])
                            ->reactive(),
                            
                        Forms\Components\Select::make('format')
                            ->label('Output Format')
                            ->required()
                            ->options([
                                'pdf' => 'PDF Document',
                                'excel' => 'Excel Spreadsheet',
                                'csv' => 'CSV File',
                                'html' => 'HTML Report',
                            ])
                            ->default('pdf'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Date Range')
                    ->schema([
                        Forms\Components\Select::make('date_range_type')
                            ->label('Date Range Type')
                            ->options([
                                'custom' => 'Custom Range',
                                'today' => 'Today',
                                'yesterday' => 'Yesterday',
                                'this_week' => 'This Week',
                                'last_week' => 'Last Week',
                                'this_month' => 'This Month',
                                'last_month' => 'Last Month',
                                'this_quarter' => 'This Quarter',
                                'last_quarter' => 'Last Quarter',
                                'this_year' => 'This Year',
                                'last_year' => 'Last Year',
                            ])
                            ->default('this_month')
                            ->reactive(),
                            
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->visible(fn (Forms\Get $get): bool => $get('date_range_type') === 'custom'),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->visible(fn (Forms\Get $get): bool => $get('date_range_type') === 'custom'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Filters & Options')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Specific Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['sales', 'receivables', 'customer'])),
                            
                        Forms\Components\Select::make('supplier_id')
                            ->label('Specific Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['inventory', 'supplier'])),
                            
                        Forms\Components\Select::make('product_category')
                            ->label('Product Category')
                            ->options([
                                'cement' => 'Cement',
                                'steel' => 'Steel',
                                'wood' => 'Wood',
                                'paint' => 'Paint',
                                'tools' => 'Tools',
                                'electrical' => 'Electrical',
                                'plumbing' => 'Plumbing',
                            ])
                            ->multiple()
                            ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['sales', 'inventory'])),
                            
                        Forms\Components\Toggle::make('include_charts')
                            ->label('Include Charts')
                            ->default(true)
                            ->helperText('Add visual charts to the report'),
                            
                        Forms\Components\Toggle::make('group_by_category')
                            ->label('Group by Category')
                            ->default(false)
                            ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['sales', 'inventory']))
                            ->helperText('Group results by product category'),
                            
                        Forms\Components\Toggle::make('show_totals')
                            ->label('Show Totals')
                            ->default(true)
                            ->helperText('Include summary totals in the report'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Automation')
                    ->schema([
                        Forms\Components\Toggle::make('is_scheduled')
                            ->label('Schedule Report')
                            ->reactive()
                            ->helperText('Automatically generate this report on a schedule'),
                            
                        Forms\Components\Select::make('schedule_frequency')
                            ->label('Frequency')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('is_scheduled')),
                            
                        Forms\Components\TimePicker::make('schedule_time')
                            ->label('Schedule Time')
                            ->default('09:00')
                            ->visible(fn (Forms\Get $get): bool => $get('is_scheduled')),
                            
                        Forms\Components\TagsInput::make('email_recipients')
                            ->label('Email Recipients')
                            ->placeholder('Enter email addresses')
                            ->visible(fn (Forms\Get $get): bool => $get('is_scheduled'))
                            ->helperText('Email addresses to send the report to'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Report Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sales' => 'success',
                        'inventory' => 'info',
                        'receivables' => 'warning',
                        'supplier' => 'primary',
                        'customer' => 'gray',
                        'financial' => 'danger',
                        'custom' => 'purple',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('format')
                    ->label('Format')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pdf' => 'danger',
                        'excel' => 'success',
                        'csv' => 'info',
                        'html' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('date_range_type')
                    ->label('Date Range')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'custom' => 'Custom Range',
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'last_week' => 'Last Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_quarter' => 'This Quarter',
                        'last_quarter' => 'Last Quarter',
                        'this_year' => 'This Year',
                        'last_year' => 'Last Year',
                        default => $state,
                    }),
                    
                Tables\Columns\IconColumn::make('is_scheduled')
                    ->label('Scheduled')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('schedule_frequency')
                    ->label('Frequency')
                    ->visible(fn ($record) => $record->is_scheduled)
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('last_generated_at')
                    ->label('Last Generated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Never')
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('d/m/Y H:i:s')),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Report Type')
                    ->options([
                        'sales' => 'Sales Report',
                        'inventory' => 'Inventory Report',
                        'receivables' => 'Receivables Report',
                        'supplier' => 'Supplier Report',
                        'customer' => 'Customer Report',
                        'financial' => 'Financial Report',
                        'custom' => 'Custom Report',
                    ]),
                    
                Tables\Filters\SelectFilter::make('format')
                    ->label('Output Format')
                    ->options([
                        'pdf' => 'PDF Document',
                        'excel' => 'Excel Spreadsheet',
                        'csv' => 'CSV File',
                        'html' => 'HTML Report',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_scheduled')
                    ->label('Scheduled Reports')
                    ->placeholder('All reports')
                    ->trueLabel('Scheduled only')
                    ->falseLabel('Manual only'),
                    
                Tables\Filters\Filter::make('recently_generated')
                    ->query(fn (Builder $query): Builder => $query->where('last_generated_at', '>', now()->subDays(7)))
                    ->label('Generated in last 7 days'),
            ])
            ->actions([
                Tables\Actions\Action::make('generate')
                    ->label('Generate Now')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function ($record) {
                        // This would trigger report generation
                        $record->update(['last_generated_at' => now()]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Report Generated')
                            ->body("Report '{$record->name}' has been generated successfully.")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\EditAction::make()
                    ->color('primary'),
                    
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $newRecord = $record->replicate();
                        $newRecord->name = $record->name . ' (Copy)';
                        $newRecord->last_generated_at = null;
                        $newRecord->save();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Report Duplicated')
                            ->body("Report duplicated as '{$newRecord->name}'.")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('generate_all')
                        ->label('Generate All')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['last_generated_at' => now()]);
                            });
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Reports Generated')
                                ->body(count($records) . ' reports have been generated.')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ])
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
