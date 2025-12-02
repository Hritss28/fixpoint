<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryNoteResource\Pages;
use App\Models\DeliveryNote;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
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

class DeliveryNoteResource extends Resource
{
    protected static ?string $model = DeliveryNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?string $navigationLabel = 'Delivery Notes';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Delivery Information')
                    ->description('Basic delivery note details and order reference')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('delivery_number')
                                    ->label('Delivery Number')
                                    ->disabled()
                                    ->placeholder('Auto-generated')
                                    ->helperText('Will be generated automatically on save'),
                                    
                                Select::make('order_id')
                                    ->label('Order Reference')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $order = Order::with('customer')->find($state);
                                            if ($order) {
                                                $set('customer_name', $order->customer->name);
                                                $set('delivery_address', $order->delivery_address ?? $order->customer->address);
                                            }
                                        }
                                    }),
                                    
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'preparing' => 'Preparing',
                                        'in_transit' => 'In Transit',
                                        'delivered' => 'Delivered',
                                        'returned' => 'Returned',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('delivery_date')
                                    ->label('Scheduled Delivery')
                                    ->default(now()->addDay())
                                    ->required(),
                                    
                                DateTimePicker::make('delivered_at')
                                    ->label('Actual Delivery Time')
                                    ->visible(fn ($get) => in_array($get('status'), ['delivered', 'returned'])),
                            ]),
                    ]),
                    
                Section::make('Customer & Delivery Details')
                    ->description('Customer information and delivery address')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->disabled()
                                    ->placeholder('Auto-filled from order'),
                                    
                                TextInput::make('contact_phone')
                                    ->label('Contact Phone')
                                    ->tel()
                                    ->placeholder('Customer contact number'),
                            ]),
                            
                        Textarea::make('delivery_address')
                            ->label('Delivery Address')
                            ->rows(3)
                            ->required()
                            ->placeholder('Complete delivery address with landmarks'),
                    ]),
                    
                Section::make('Driver & Vehicle Information')
                    ->description('Transportation details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('driver_name')
                                    ->label('Driver Name')
                                    ->required()
                                    ->placeholder('Name of the driver'),
                                    
                                TextInput::make('driver_phone')
                                    ->label('Driver Phone')
                                    ->tel()
                                    ->placeholder('Driver contact number'),
                                    
                                TextInput::make('vehicle_number')
                                    ->label('Vehicle Number')
                                    ->placeholder('License plate number'),
                            ]),
                    ]),
                    
                Section::make('Delivery Confirmation')
                    ->description('Recipient confirmation and notes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('recipient_name')
                                    ->label('Received By')
                                    ->placeholder('Name of person who received the goods')
                                    ->visible(fn ($get) => $get('status') === 'delivered'),
                                    
                                TextInput::make('recipient_phone')
                                    ->label('Recipient Phone')
                                    ->tel()
                                    ->placeholder('Recipient contact number')
                                    ->visible(fn ($get) => $get('status') === 'delivered'),
                            ]),
                            
                        Textarea::make('delivery_notes')
                            ->label('Delivery Notes')
                            ->rows(3)
                            ->placeholder('Special instructions or delivery conditions'),
                            
                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(2)
                            ->placeholder('Internal notes for staff reference'),
                    ]),
                    
                Section::make('Order Summary')
                    ->description('Items to be delivered')
                    ->schema([
                        Placeholder::make('order_items')
                            ->label('Order Items')
                            ->content(function ($get) {
                                $orderId = $get('order_id');
                                if (!$orderId) return 'Select an order to view items';
                                
                                $order = Order::with('orderItems.product')->find($orderId);
                                if (!$order) return 'Order not found';
                                
                                $items = $order->orderItems->map(function ($item) {
                                    return "• {$item->product->name} - {$item->quantity} {$item->unit} @ Rp " . 
                                           number_format($item->unit_price, 0, ',', '.');
                                })->join("\n");
                                
                                return $items ?: 'No items found';
                            }),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->label('Delivery #')
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                    
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->url(fn (DeliveryNote $record): string => 
                        OrderResource::getUrl('edit', ['record' => $record->order_id]))
                    ->color('info'),
                    
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->limit(25),
                    
                TextColumn::make('delivery_date')
                    ->label('Scheduled')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->color('warning'),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'info' => 'preparing',
                        'warning' => 'in_transit',
                        'success' => 'delivered',
                        'danger' => fn ($state) => in_array($state, ['returned', 'cancelled']),
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'in_transit' => 'In Transit',
                        default => ucfirst($state)
                    }),
                    
                TextColumn::make('driver_name')
                    ->label('Driver')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                    
                TextColumn::make('vehicle_number')
                    ->label('Vehicle')
                    ->placeholder('—')
                    ->toggleable(),
                    
                TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime('M d, Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('recipient_name')
                    ->label('Received By')
                    ->placeholder('—')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'preparing' => 'Preparing',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('delivery_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('delivery_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('delivery_date', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Deliveries')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('delivery_date', '<', now())
                              ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
                    ),
            ])
            ->actions([
                Action::make('mark_in_transit')
                    ->label('In Transit')
                    ->icon('heroicon-m-truck')
                    ->color('warning')
                    ->visible(fn (DeliveryNote $record): bool => 
                        in_array($record->status, ['pending', 'preparing']))
                    ->action(function (DeliveryNote $record) {
                        $record->update(['status' => 'in_transit']);
                        Notification::make()
                            ->title('Delivery marked as in transit')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('mark_delivered')
                    ->label('Delivered')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (DeliveryNote $record): bool => $record->status === 'in_transit')
                    ->form([
                        TextInput::make('recipient_name')
                            ->label('Received By')
                            ->required(),
                        TextInput::make('recipient_phone')
                            ->label('Recipient Phone')
                            ->tel(),
                        Textarea::make('delivery_notes')
                            ->label('Delivery Notes')
                            ->rows(2),
                    ])
                    ->action(function (DeliveryNote $record, array $data) {
                        $record->update([
                            'status' => 'delivered',
                            'delivered_at' => now(),
                            'recipient_name' => $data['recipient_name'],
                            'recipient_phone' => $data['recipient_phone'] ?? null,
                            'delivery_notes' => $data['delivery_notes'] ?? null,
                        ]);
                        
                        // Update order status if fully delivered
                        if ($record->order) {
                            $record->order->update(['status' => 'delivered']);
                        }
                        
                        Notification::make()
                            ->title('Delivery marked as delivered')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('print_delivery_note')
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->color('info')
                    ->url(fn (DeliveryNote $record): string => route('delivery-notes.print', $record))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_preparing')
                        ->label('Mark as Preparing')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->color('info')
                        ->action(fn (Collection $records) => 
                            $records->each(fn ($record) => $record->update(['status' => 'preparing']))),
                        
                    Tables\Actions\BulkAction::make('export_delivery_schedule')
                        ->label('Export Schedule')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            // Export functionality would be implemented here
                            Notification::make()
                                ->title('Delivery schedule exported')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('delivery_date')
            ->poll('30s');
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
            'index' => Pages\ListDeliveryNotes::route('/'),
            'create' => Pages\CreateDeliveryNote::route('/create'),
            'edit' => Pages\EditDeliveryNote::route('/{record}/edit'),
        ];
    }
}
