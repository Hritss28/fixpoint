<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerCreditResource\Pages;
use App\Models\CustomerCredit;
use App\Models\User;
use App\Models\PaymentTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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

class CustomerCreditResource extends Resource
{
    protected static ?string $model = CustomerCredit::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Customer Credits';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Customer Information')
                    ->description('Select customer and view basic information')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name', function (Builder $query) {
                                return $query->where('customer_type', '!=', 'retail')
                                    ->orWhere('credit_limit', '>', 0);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $customer = User::find($state);
                                    if ($customer) {
                                        $set('credit_limit', $customer->credit_limit);
                                        $set('customer_type', $customer->customer_type);
                                        $set('payment_term_days', $customer->payment_term_days);
                                    }
                                }
                            }),
                            
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('customer_type')
                                    ->label('Customer Type')
                                    ->content(fn ($get) => ucfirst($get('customer_type') ?? 'N/A')),
                                    
                                Placeholder::make('payment_term_days')
                                    ->label('Payment Terms')
                                    ->content(fn ($get) => ($get('payment_term_days') ?? 0) . ' days'),
                                    
                                Placeholder::make('customer_since')
                                    ->label('Customer Since')
                                    ->content(function ($get) {
                                        $customerId = $get('customer_id');
                                        if (!$customerId) return 'N/A';
                                        
                                        $customer = User::find($customerId);
                                        return $customer?->created_at?->format('M d, Y') ?? 'N/A';
                                    }),
                            ]),
                    ]),
                    
                Section::make('Credit Limit Management')
                    ->description('Set and manage customer credit limits')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('credit_limit')
                                    ->label('Credit Limit')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 0, ',', '.')),
                                    
                                Placeholder::make('current_debt_display')
                                    ->label('Current Debt')
                                    ->content(function ($get, $record): string {
                                        $customerId = $get('customer_id') ?? $record?->customer_id;
                                        if (!$customerId) return 'Rp 0';
                                        
                                        $debt = PaymentTerm::where('customer_id', $customerId)
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('amount') - PaymentTerm::where('customer_id', $customerId)
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('paid_amount');
                                            
                                        return 'Rp ' . number_format($debt, 0, ',', '.');
                                    }),
                                    
                                Placeholder::make('available_credit_display')
                                    ->label('Available Credit')
                                    ->content(function ($get, $record): string {
                                        $customerId = $get('customer_id') ?? $record?->customer_id;
                                        $creditLimit = $get('credit_limit') ?? $record?->credit_limit ?? 0;
                                        
                                        if (!$customerId) return 'Rp 0';
                                        
                                        $debt = PaymentTerm::where('customer_id', $customerId)
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('amount') - PaymentTerm::where('customer_id', $customerId)
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('paid_amount');
                                            
                                        $available = $creditLimit - $debt;
                                        return 'Rp ' . number_format($available, 0, ',', '.');
                                    }),
                            ]),
                    ]),
                    
                Section::make('Status & Settings')
                    ->description('Credit status and additional settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Credit Active')
                                    ->default(true)
                                    ->helperText('Enable/disable credit for this customer'),
                                    
                                Select::make('risk_level')
                                    ->label('Risk Level')
                                    ->options([
                                        'low' => 'Low Risk',
                                        'medium' => 'Medium Risk',
                                        'high' => 'High Risk',
                                    ])
                                    ->default('medium'),
                            ]),
                            
                        Textarea::make('notes')
                            ->label('Credit Notes')
                            ->rows(3)
                            ->placeholder('Notes about this customer\'s credit history or special conditions'),
                    ]),
                    
                Section::make('Payment History Summary')
                    ->description('Recent payment activity and statistics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('total_orders')
                                    ->label('Total Orders')
                                    ->content(function ($get, $record): string {
                                        $customerId = $get('customer_id') ?? $record?->customer_id;
                                        if (!$customerId) return '0';
                                        
                                        $count = PaymentTerm::where('customer_id', $customerId)->count();
                                        return $count;
                                    }),
                                    
                                Placeholder::make('paid_orders')
                                    ->label('Paid Orders')
                                    ->content(function ($get, $record): string {
                                        $customerId = $get('customer_id') ?? $record?->customer_id;
                                        if (!$customerId) return '0';
                                        
                                        $count = PaymentTerm::where('customer_id', $customerId)
                                            ->where('status', 'paid')->count();
                                        return $count;
                                    }),
                                    
                                Placeholder::make('overdue_orders')
                                    ->label('Overdue Orders')
                                    ->content(function ($get, $record): string {
                                        $customerId = $get('customer_id') ?? $record?->customer_id;
                                        if (!$customerId) return '0';
                                        
                                        $count = PaymentTerm::where('customer_id', $customerId)
                                            ->where('due_date', '<', now())
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->count();
                                        return $count;
                                    }),
                                    
                                Placeholder::make('payment_reliability')
                                    ->label('Payment Rate')
                                    ->content(function ($get, $record): string {
                                        $customerId = $get('customer_id') ?? $record?->customer_id;
                                        if (!$customerId) return '0%';
                                        
                                        $total = PaymentTerm::where('customer_id', $customerId)->count();
                                        if ($total === 0) return '0%';
                                        
                                        $paid = PaymentTerm::where('customer_id', $customerId)
                                            ->where('status', 'paid')->count();
                                            
                                        $rate = ($paid / $total) * 100;
                                        return number_format($rate, 1) . '%';
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
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->limit(25),
                    
                TextColumn::make('customer.company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->limit(20)
                    ->toggleable(),
                    
                BadgeColumn::make('customer.customer_type')
                    ->label('Type')
                    ->colors([
                        'info' => 'retail',
                        'success' => 'wholesale',
                        'warning' => 'contractor',
                        'primary' => 'distributor',
                    ])
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'retail')),
                    
                TextColumn::make('credit_limit')
                    ->label('Credit Limit')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->color('info'),
                    
                TextColumn::make('current_debt')
                    ->label('Current Debt')
                    ->getStateUsing(function (CustomerCredit $record): float {
                        return PaymentTerm::where('customer_id', $record->customer_id)
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('amount') - PaymentTerm::where('customer_id', $record->customer_id)
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('paid_amount');
                    })
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'success')
                    ->sortable(),
                    
                TextColumn::make('available_credit')
                    ->label('Available Credit')
                    ->getStateUsing(function (CustomerCredit $record): float {
                        $debt = PaymentTerm::where('customer_id', $record->customer_id)
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('amount') - PaymentTerm::where('customer_id', $record->customer_id)
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('paid_amount');
                        return $record->credit_limit - $debt;
                    })
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color(fn ($state): string => $state < 0 ? 'danger' : 'success')
                    ->sortable(),
                    
                BadgeColumn::make('risk_level')
                    ->label('Risk Level')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ])
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'medium') . ' Risk'),
                    
                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->getStateUsing(fn (CustomerCredit $record): string => $record->is_active ? 'active' : 'inactive')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                    
                TextColumn::make('utilization_rate')
                    ->label('Utilization')
                    ->getStateUsing(function (CustomerCredit $record): string {
                        if ($record->credit_limit == 0) return '0%';
                        
                        $debt = PaymentTerm::where('customer_id', $record->customer_id)
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('amount') - PaymentTerm::where('customer_id', $record->customer_id)
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('paid_amount');
                            
                        $rate = ($debt / $record->credit_limit) * 100;
                        return number_format($rate, 1) . '%';
                    })
                    ->color(function ($state): string {
                        $rate = (float) str_replace('%', '', $state);
                        if ($rate > 80) return 'danger';
                        if ($rate > 60) return 'warning';
                        return 'success';
                    })
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer.customer_type')
                    ->label('Customer Type')
                    ->relationship('customer', 'customer_type')
                    ->options([
                        'wholesale' => 'Wholesale',
                        'contractor' => 'Contractor',
                        'distributor' => 'Distributor',
                    ]),
                    
                SelectFilter::make('risk_level')
                    ->options([
                        'low' => 'Low Risk',
                        'medium' => 'Medium Risk',
                        'high' => 'High Risk',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                    
                Tables\Filters\Filter::make('over_utilized')
                    ->label('Over 80% Utilized')
                    ->query(function (Builder $query): Builder {
                        return $query->whereRaw('
                            (SELECT COALESCE(SUM(pt.amount - pt.paid_amount), 0) 
                             FROM payment_terms pt 
                             WHERE pt.customer_id = customer_credits.customer_id 
                             AND pt.status NOT IN ("paid", "cancelled")) / credit_limit > 0.8
                        ');
                    }),
                    
                Tables\Filters\Filter::make('has_overdue')
                    ->label('Has Overdue Payments')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('customer.paymentTerms', function (Builder $q) {
                            $q->where('due_date', '<', now())
                              ->whereNotIn('status', ['paid', 'cancelled']);
                        });
                    }),
            ])
            ->actions([
                Action::make('adjust_limit')
                    ->label('Adjust Limit')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        TextInput::make('new_credit_limit')
                            ->label('New Credit Limit')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        Textarea::make('adjustment_reason')
                            ->label('Reason for Adjustment')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (CustomerCredit $record, array $data) {
                        $oldLimit = $record->credit_limit;
                        $record->update([
                            'credit_limit' => $data['new_credit_limit'],
                            'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                      now()->format('Y-m-d H:i') . " - Credit limit adjusted from Rp " . 
                                      number_format($oldLimit, 0, ',', '.') . " to Rp " . 
                                      number_format($data['new_credit_limit'], 0, ',', '.') . 
                                      ". Reason: " . $data['adjustment_reason'],
                        ]);
                        
                        // Also update the customer record
                        $record->customer->update(['credit_limit' => $data['new_credit_limit']]);
                        
                        Notification::make()
                            ->title('Credit limit adjusted successfully')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('view_payments')
                    ->label('Payment History')
                    ->icon('heroicon-m-list-bullet')
                    ->color('info')
                    ->url(fn (CustomerCredit $record): string => 
                        PaymentTermResource::getUrl('index', [
                            'tableFilters' => [
                                'customer_id' => ['value' => $record->customer_id]
                            ]
                        ])),
                        
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate_credits')
                        ->label('Activate Credits')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => 
                            $records->each(fn ($record) => $record->update(['is_active' => true]))),
                            
                    Tables\Actions\BulkAction::make('deactivate_credits')
                        ->label('Deactivate Credits')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => 
                            $records->each(fn ($record) => $record->update(['is_active' => false]))),
                            
                    Tables\Actions\BulkAction::make('export_credit_report')
                        ->label('Export Credit Report')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->action(function (Collection $records) {
                            Notification::make()
                                ->title('Credit report exported')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
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
            'index' => Pages\ListCustomerCredits::route('/'),
            'create' => Pages\CreateCustomerCredit::route('/create'),
            'edit' => Pages\EditCustomerCredit::route('/{record}/edit'),
        ];
    }
}
