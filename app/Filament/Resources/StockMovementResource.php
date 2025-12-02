<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Models\Product;
use App\Services\StockManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Stock Movements';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Stock Movement Details')
                    ->description('Create or edit stock movement transaction')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            $set('unit', $product?->unit ?? 'pcs');
                                            $set('previous_stock', $product?->current_stock ?? 0);
                                        }
                                    }),
                                    
                                Select::make('type')
                                    ->label('Movement Type')
                                    ->options([
                                        'in' => 'Stock In',
                                        'out' => 'Stock Out', 
                                        'adjustment' => 'Stock Adjustment',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('reference_type', match($state) {
                                            'in' => 'purchase',
                                            'out' => 'order',
                                            'adjustment' => 'adjustment',
                                            default => null
                                        });
                                    }),
                                    
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $type = $get('type');
                                        $previousStock = (int) ($get('previous_stock') ?? 0);
                                        $quantity = (int) ($state ?? 0);
                                        
                                        $newStock = match($type) {
                                            'in' => $previousStock + $quantity,
                                            'out' => $previousStock - $quantity,
                                            'adjustment' => $quantity, // For adjustment, quantity IS the new stock
                                            default => $previousStock
                                        };
                                        
                                        $set('new_stock', $newStock);
                                        
                                        if ($type === 'adjustment') {
                                            $difference = $newStock - $previousStock;
                                            $set('adjustment_type', $difference >= 0 ? 'increase' : 'decrease');
                                        }
                                    }),
                                    
                                TextInput::make('unit')
                                    ->label('Unit')
                                    ->required()
                                    ->default('pcs'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                Select::make('reference_type')
                                    ->label('Reference Type')
                                    ->options([
                                        'purchase' => 'Purchase Order',
                                        'order' => 'Sales Order',
                                        'adjustment' => 'Stock Adjustment',
                                        'return' => 'Product Return',
                                        'transfer' => 'Stock Transfer',
                                        'damaged' => 'Damaged/Loss',
                                    ])
                                    ->required(),
                                    
                                TextInput::make('reference_id')
                                    ->label('Reference ID')
                                    ->numeric()
                                    ->nullable()
                                    ->helperText('Order number, purchase ID, etc.'),
                            ]),
                            
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Additional information about this stock movement...'),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('previous_stock')
                                    ->label('Previous Stock')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true),
                                    
                                TextInput::make('new_stock')
                                    ->label('New Stock')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true),
                                    
                                Select::make('adjustment_type')
                                    ->label('Adjustment Type')
                                    ->options([
                                        'increase' => 'Increase',
                                        'decrease' => 'Decrease',
                                    ])
                                    ->visible(fn (callable $get) => $get('type') === 'adjustment')
                                    ->disabled()
                                    ->dehydrated(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                    
                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                    
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                        'warning' => 'adjustment',
                        'info' => 'reserved',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                        'adjustment' => 'Adjustment',
                        'reserved' => 'Reserved',
                        default => ucfirst($state)
                    }),
                    
                TextColumn::make('quantity_display')
                    ->label('Quantity')
                    ->getStateUsing(function (StockMovement $record): string {
                        $sign = match($record->type) {
                            'in' => '+',
                            'out' => '-',
                            'adjustment' => ($record->adjustment_type === 'increase' ? '+' : '-'),
                            default => ''
                        };
                        return $sign . number_format($record->quantity) . ' ' . $record->unit;
                    })
                    ->color(fn (StockMovement $record): string => match($record->type) {
                        'in' => 'success',
                        'out' => 'danger', 
                        'adjustment' => 'warning',
                        default => 'primary'
                    }),
                    
                TextColumn::make('stock_change')
                    ->label('Stock Change')
                    ->getStateUsing(function (StockMovement $record): string {
                        return $record->previous_stock . ' → ' . $record->new_stock;
                    })
                    ->color('info'),
                    
                BadgeColumn::make('reference_type')
                    ->label('Reference')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'purchase' => 'Purchase',
                        'order' => 'Order',
                        'adjustment' => 'Adjustment',
                        'return' => 'Return',
                        'transfer' => 'Transfer',
                        'damaged' => 'Damaged',
                        default => ucfirst($state)
                    })
                    ->colors([
                        'primary' => 'purchase',
                        'success' => 'order',
                        'warning' => 'adjustment',
                        'info' => 'return',
                        'danger' => 'damaged',
                    ]),
                    
                TextColumn::make('reference_id')
                    ->label('Ref ID')
                    ->placeholder('—')
                    ->toggleable(),
                    
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('System'),
                    
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Movement Type')
                    ->options([
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                        'adjustment' => 'Adjustment', 
                        'reserved' => 'Reserved',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('reference_type')
                    ->label('Reference Type')
                    ->options([
                        'purchase' => 'Purchase Order',
                        'order' => 'Sales Order',
                        'adjustment' => 'Stock Adjustment',
                        'return' => 'Product Return',
                        'transfer' => 'Stock Transfer',
                        'damaged' => 'Damaged/Loss',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (StockMovement $record): bool => !in_array($record->type, ['reserved'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            Notification::make()
                                ->warning()
                                ->title('Cannot Delete Stock Movements')
                                ->body('Stock movements should not be deleted for audit purposes.')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto refresh every 30 seconds
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'edit' => Pages\EditStockMovement::route('/{record}/edit'),
        ];
    }
}
