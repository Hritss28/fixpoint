<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PaymentTerm;
use App\Services\PriceCalculator;
use App\Services\StockManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationLabel = 'Orders';
    
    protected static ?string $navigationGroup = 'Sales Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Information')
                    ->description('Basic order details and customer information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('order_number')
                                    ->label('Order Number')
                                    ->disabled()
                                    ->placeholder('Auto-generated'),
                                    
                                Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('email')->email(),
                                        TextInput::make('phone'),
                                        Textarea::make('address'),
                                    ]),
                                    
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'processing' => 'Processing',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('order_date')
                                    ->label('Order Date')
                                    ->default(now())
                                    ->required(),
                                    
                                DatePicker::make('delivery_date')
                                    ->label('Expected Delivery')
                                    ->after('order_date'),
                            ]),
                    ]),
                    
                Section::make('Order Items')
                    ->description('Products and quantities for this order')
                    ->schema([
                        Repeater::make('orderItems')
                            ->relationship()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Product')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $set) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('unit_price', $product->price);
                                                        $set('unit', $product->unit);
                                                    }
                                                }
                                            }),
                                            
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $unitPrice = (float) $get('unit_price');
                                                $set('total_price', $state * $unitPrice);
                                            }),
                                            
                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->required()
                                            ->prefix('Rp')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $quantity = (float) $get('quantity');
                                                $set('total_price', $quantity * $state);
                                            }),
                                            
                                        TextInput::make('total_price')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->prefix('Rp'),
                                    ]),
                                    
                                Textarea::make('notes')
                                    ->label('Item Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->minItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['product_id']) 
                                    ? Product::find($state['product_id'])?->name 
                                    : 'New Item'
                            ),
                    ]),
                    
                Section::make('Payment & Delivery')
                    ->description('Payment terms and delivery information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('payment_term_id')
                                    ->label('Payment Terms')
                                    ->relationship('paymentTerm', 'name')
                                    ->preload(),
                                    
                                Select::make('payment_status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'partial' => 'Partial Payment',
                                        'paid' => 'Fully Paid',
                                        'overdue' => 'Overdue',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                    
                                TextInput::make('delivery_fee')
                                    ->label('Delivery Fee')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ]),
                            
                        Textarea::make('delivery_address')
                            ->label('Delivery Address')
                            ->rows(3)
                            ->placeholder('Complete delivery address'),
                            
                        Textarea::make('notes')
                            ->label('Order Notes')
                            ->rows(3)
                            ->placeholder('Special instructions or notes'),
                    ]),
                    
                Section::make('Order Summary')
                    ->description('Calculated totals and final amounts')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->content(function ($get) {
                                        $items = $get('orderItems') ?? [];
                                        $subtotal = collect($items)->sum('total_price');
                                        return 'Rp ' . number_format($subtotal, 0, ',', '.');
                                    }),
                                    
                                Placeholder::make('delivery_fee_display')
                                    ->label('Delivery Fee')
                                    ->content(function ($get) {
                                        $fee = $get('delivery_fee') ?? 0;
                                        return 'Rp ' . number_format($fee, 0, ',', '.');
                                    }),
                                    
                                TextInput::make('tax_amount')
                                    ->label('Tax Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                                    
                                Placeholder::make('total_display')
                                    ->label('Total Amount')
                                    ->content(function ($get) {
                                        $items = $get('orderItems') ?? [];
                                        $subtotal = collect($items)->sum('total_price');
                                        $delivery = $get('delivery_fee') ?? 0;
                                        $tax = $get('tax_amount') ?? 0;
                                        $total = $subtotal + $delivery + $tax;
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    }),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                    
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->limit(25)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),
                    
                TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date('M d, Y')
                    ->sortable(),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'processing',
                        'success' => fn ($state) => in_array($state, ['shipped', 'delivered', 'completed']),
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    
                BadgeColumn::make('payment_status')
                    ->label('Payment')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => fn ($state) => in_array($state, ['overdue', 'cancelled']),
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    
                TextColumn::make('orderItems_sum_total_price')
                    ->label('Order Total')
                    ->sum('orderItems', 'total_price')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state + ($state * 0.1), 0, ',', '.'))
                    ->sortable()
                    ->color('success'),
                    
                TextColumn::make('orderItems_count')
                    ->label('Items')
                    ->counts('orderItems')
                    ->sortable()
                    ->color('info'),
                    
                TextColumn::make('delivery_date')
                    ->label('Delivery')
                    ->date('M d, Y')
                    ->placeholder('Not set')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('paymentTerm.name')
                    ->label('Payment Terms')
                    ->placeholder('Cash')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial Payment',
                        'paid' => 'Fully Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('order_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('mark_confirmed')
                    ->label('Confirm')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'pending')
                    ->action(function (Order $record) {
                        $record->update(['status' => 'confirmed']);
                        Notification::make()
                            ->title('Order confirmed successfully')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('generate_invoice')
                    ->label('Invoice')
                    ->icon('heroicon-m-document-text')
                    ->color('info')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['confirmed', 'processing']))
                    ->url(fn (Order $record): string => route('orders.invoice', $record))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Order $record): bool => $record->status === 'draft')
                    ->before(function (Order $record) {
                        // Check if order has confirmed status or payments
                        if ($record->status !== 'draft') {
                            throw new \Exception('Cannot delete order that is not in draft status.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_processing')
                        ->label('Mark as Processing')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->color('primary')
                        ->action(fn (Collection $records) => 
                            $records->each(fn ($record) => $record->update(['status' => 'processing']))),
                        
                    Tables\Actions\BulkAction::make('export_orders')
                        ->label('Export Selected')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->action(function (Collection $records) {
                            // Export functionality would be implemented here
                            Notification::make()
                                ->title('Export initiated')
                                ->body('Selected orders are being exported.')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('60s'); // Auto refresh every minute
    }
    
    public static function getRelations(): array
    {
        return [
            OrderResource\RelationManagers\ItemsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }    
}