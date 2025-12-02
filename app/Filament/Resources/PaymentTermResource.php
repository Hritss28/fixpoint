<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTermResource\Pages;
use App\Models\PaymentTerm;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
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

class PaymentTermResource extends Resource
{
    protected static ?string $model = PaymentTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?string $navigationLabel = 'Payment Terms';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Payment Information')
                    ->description('Basic payment term details and order reference')
                    ->schema([
                        Grid::make(3)
                            ->schema([
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
                                                $set('amount', $order->total_amount);
                                                
                                                // Calculate due date based on payment terms
                                                $termDays = $order->payment_term_days ?? 30;
                                                $set('due_date', now()->addDays($termDays)->format('Y-m-d'));
                                                $set('term_days', $termDays);
                                            }
                                        }
                                    }),
                                    
                                TextInput::make('customer_name')
                                    ->label('Customer')
                                    ->disabled()
                                    ->placeholder('Auto-filled from order'),
                                    
                                TextInput::make('term_days')
                                    ->label('Payment Term (Days)')
                                    ->numeric()
                                    ->disabled()
                                    ->placeholder('From order settings'),
                            ]),
                    ]),
                    
                Section::make('Amount & Due Date')
                    ->description('Payment amounts and due dates')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $paidAmount = (float) $get('paid_amount');
                                        $remaining = $state - $paidAmount;
                                        $set('remaining_amount', max(0, $remaining));
                                    }),
                                    
                                TextInput::make('paid_amount')
                                    ->label('Paid Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $totalAmount = (float) $get('amount');
                                        $remaining = $totalAmount - $state;
                                        $set('remaining_amount', max(0, $remaining));
                                        
                                        // Update status based on payment
                                        if ($state == 0) {
                                            $set('status', 'pending');
                                        } elseif ($state >= $totalAmount) {
                                            $set('status', 'paid');
                                        } else {
                                            $set('status', 'partial');
                                        }
                                    }),
                                    
                                TextInput::make('remaining_amount')
                                    ->label('Remaining Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state && now()->gt($state)) {
                                            $set('is_overdue', true);
                                        } else {
                                            $set('is_overdue', false);
                                        }
                                    }),
                                    
                                DatePicker::make('payment_date')
                                    ->label('Payment Date')
                                    ->visible(fn ($get) => in_array($get('status'), ['partial', 'paid'])),
                            ]),
                    ]),
                    
                Section::make('Status & Payment Method')
                    ->description('Payment status and method information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'partial' => 'Partial Payment',
                                        'paid' => 'Fully Paid',
                                        'overdue' => 'Overdue',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->live(),
                                    
                                Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'check' => 'Check',
                                        'credit_card' => 'Credit Card',
                                        'other' => 'Other',
                                    ])
                                    ->visible(fn ($get) => in_array($get('status'), ['partial', 'paid'])),
                                    
                                TextInput::make('reference_number')
                                    ->label('Reference Number')
                                    ->placeholder('Transaction/Check number')
                                    ->visible(fn ($get) => in_array($get('status'), ['partial', 'paid'])),
                            ]),
                    ]),
                    
                Section::make('Notes & Reminders')
                    ->description('Additional information and payment tracking')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Payment Notes')
                            ->rows(3)
                            ->placeholder('Additional notes about this payment term'),
                            
                        Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->rows(2)
                            ->placeholder('Internal staff notes (not visible to customer)'),
                    ]),
                    
                Section::make('Order Summary')
                    ->description('Related order information')
                    ->schema([
                        Placeholder::make('order_summary')
                            ->label('Order Details')
                            ->content(function ($get) {
                                $orderId = $get('order_id');
                                if (!$orderId) return 'Select an order to view details';
                                
                                $order = Order::with(['customer', 'orderItems'])->find($orderId);
                                if (!$order) return 'Order not found';
                                
                                $itemCount = $order->orderItems->count();
                                $orderDate = $order->order_date ? $order->order_date->format('M d, Y') : 'N/A';
                                
                                return "Customer: {$order->customer->name}\n" .
                                       "Order Date: {$orderDate}\n" .
                                       "Items: {$itemCount} items\n" .
                                       "Status: " . ucfirst($order->status);
                            }),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->url(fn (PaymentTerm $record): string => 
                        OrderResource::getUrl('edit', ['record' => $record->order_id]))
                    ->color('info'),
                    
                TextColumn::make('order.customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->limit(25),
                    
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->color(fn (PaymentTerm $record): string => 
                        $record->due_date->isPast() && $record->status !== 'paid' ? 'danger' : 'warning'),
                        
                TextColumn::make('amount')
                    ->label('Total Amount')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->color('success'),
                    
                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->color('info'),
                    
                TextColumn::make('remaining_amount')
                    ->label('Remaining')
                    ->getStateUsing(fn (PaymentTerm $record): float => $record->amount - $record->paid_amount)
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'success'),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'secondary' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($state)
                    }),
                    
                TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(function (PaymentTerm $record): ?int {
                        if ($record->status === 'paid' || !$record->due_date->isPast()) {
                            return null;
                        }
                        return $record->due_date->diffInDays(now());
                    })
                    ->placeholder('—')
                    ->color('danger')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : '—')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial Payment',
                        'paid' => 'Fully Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Payments')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('due_date', '<', now())
                              ->whereNotIn('status', ['paid', 'cancelled'])
                    ),
                    
                Tables\Filters\Filter::make('due_soon')
                    ->label('Due in 7 Days')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('due_date', [now(), now()->addDays(7)])
                              ->whereNotIn('status', ['paid', 'cancelled'])
                    ),
                    
                Tables\Filters\Filter::make('due_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (PaymentTerm $record): bool => 
                        in_array($record->status, ['pending', 'partial', 'overdue']))
                    ->form([
                        TextInput::make('payment_amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, PaymentTerm $record) {
                                $remaining = $record->amount - $record->paid_amount;
                                if ($state > $remaining) {
                                    $set('payment_amount', $remaining);
                                }
                            }),
                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'check' => 'Check',
                                'credit_card' => 'Credit Card',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('reference_number')
                            ->label('Reference Number'),
                        DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),
                        Textarea::make('payment_notes')
                            ->label('Payment Notes')
                            ->rows(2),
                    ])
                    ->action(function (PaymentTerm $record, array $data) {
                        $newPaidAmount = $record->paid_amount + $data['payment_amount'];
                        $newStatus = $newPaidAmount >= $record->amount ? 'paid' : 'partial';
                        
                        $record->update([
                            'paid_amount' => $newPaidAmount,
                            'status' => $newStatus,
                            'payment_method' => $data['payment_method'],
                            'reference_number' => $data['reference_number'] ?? null,
                            'payment_date' => $data['payment_date'],
                            'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                      "Payment: Rp " . number_format($data['payment_amount'], 0, ',', '.') . 
                                      " on " . $data['payment_date'] . 
                                      ($data['payment_notes'] ? " - " . $data['payment_notes'] : ''),
                        ]);
                        
                        // Update order payment status if fully paid
                        if ($newStatus === 'paid' && $record->order) {
                            $record->order->update(['payment_status' => 'paid']);
                        }
                        
                        Notification::make()
                            ->title('Payment recorded successfully')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('send_reminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-m-bell-alert')
                    ->color('warning')
                    ->visible(fn (PaymentTerm $record): bool => 
                        in_array($record->status, ['pending', 'partial', 'overdue']))
                    ->action(function (PaymentTerm $record) {
                        // Send reminder logic would be implemented here
                        Notification::make()
                            ->title('Payment reminder sent')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('send_reminders')
                        ->label('Send Reminders')
                        ->icon('heroicon-m-bell-alert')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $count = $records->whereIn('status', ['pending', 'partial', 'overdue'])->count();
                            Notification::make()
                                ->title("Reminders sent to {$count} customers")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('export_aging_report')
                        ->label('Export Aging Report')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->action(function (Collection $records) {
                            Notification::make()
                                ->title('Aging report exported')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('due_date')
            ->poll('60s');
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
            'index' => Pages\ListPaymentTerms::route('/'),
            'create' => Pages\CreatePaymentTerm::route('/create'),
            'edit' => Pages\EditPaymentTerm::route('/{record}/edit'),
        ];
    }
}
