<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Customer personal and contact information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Customer full name'),
                                    
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('customer@email.com'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('+62 812 3456 7890'),
                                    
                                TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => !empty($state) ? bcrypt($state) : null)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->maxLength(255)
                                    ->placeholder('Leave blank to keep current password'),
                            ]),
                            
                        FileUpload::make('avatar')
                            ->label('Profile Picture')
                            ->image()
                            ->directory('user-avatars')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Customer Type & Business Info')
                    ->description('Customer classification and business details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('customer_type')
                                    ->label('Customer Type')
                                    ->options([
                                        'retail' => 'Retail Customer',
                                        'wholesale' => 'Wholesale Customer',
                                        'contractor' => 'Contractor',
                                        'distributor' => 'Distributor',
                                    ])
                                    ->default('retail')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        // Set default payment terms based on customer type
                                        match($state) {
                                            'retail' => $set('payment_term_days', 0),
                                            'wholesale' => $set('payment_term_days', 30),
                                            'contractor' => $set('payment_term_days', 45),
                                            'distributor' => $set('payment_term_days', 60),
                                            default => $set('payment_term_days', 0)
                                        };
                                        
                                        // Set default credit limit
                                        match($state) {
                                            'retail' => $set('credit_limit', 0),
                                            'wholesale' => $set('credit_limit', 50000000),
                                            'contractor' => $set('credit_limit', 100000000),
                                            'distributor' => $set('credit_limit', 200000000),
                                            default => $set('credit_limit', 0)
                                        };
                                    }),
                                    
                                TextInput::make('payment_term_days')
                                    ->label('Payment Terms (Days)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(120)
                                    ->helperText('0 = Cash only'),
                                    
                                Toggle::make('is_verified')
                                    ->label('Verified Customer')
                                    ->default(false)
                                    ->helperText('Approved for credit transactions'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->maxLength(255)
                                    ->placeholder('Company or business name')
                                    ->visible(fn ($get) => in_array($get('customer_type'), ['wholesale', 'contractor', 'distributor'])),
                                    
                                TextInput::make('tax_number')
                                    ->label('Tax Number (NPWP)')
                                    ->maxLength(50)
                                    ->placeholder('12.345.678.9-012.000')
                                    ->visible(fn ($get) => in_array($get('customer_type'), ['wholesale', 'contractor', 'distributor'])),
                            ]),
                    ]),
                    
                Section::make('Address Information')
                    ->description('Billing and shipping addresses')
                    ->schema([
                        Textarea::make('billing_address')
                            ->label('Billing Address')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Complete billing address'),
                            
                        Textarea::make('shipping_address')
                            ->label('Shipping Address')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Complete shipping address (leave blank if same as billing)'),
                    ]),
                    
                Section::make('Credit Management')
                    ->description('Credit limit and payment terms')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('credit_limit')
                                    ->label('Credit Limit')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live()
                                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', '.')),
                                    
                                Placeholder::make('current_debt')
                                    ->label('Current Debt')
                                    ->content(function ($get, $record): string {
                                        if (!$record) return 'Rp 0';
                                        
                                        // Calculate current debt from payment terms
                                        $debt = $record->paymentTerms()
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('amount') - $record->paymentTerms()
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('paid_amount');
                                            
                                        return 'Rp ' . number_format($debt, 0, ',', '.');
                                    }),
                                    
                                Placeholder::make('available_credit')
                                    ->label('Available Credit')
                                    ->content(function ($get, $record): string {
                                        if (!$record) return 'Rp 0';
                                        
                                        $creditLimit = $get('credit_limit') ?? $record->credit_limit ?? 0;
                                        $debt = $record->paymentTerms()
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('amount') - $record->paymentTerms()
                                            ->whereNotIn('status', ['paid', 'cancelled'])
                                            ->sum('paid_amount');
                                            
                                        $available = $creditLimit - $debt;
                                        $color = $available < 0 ? 'danger' : 'success';
                                        
                                        return 'Rp ' . number_format($available, 0, ',', '.');
                                    }),
                            ]),
                    ]),
                    
                Section::make('Role & Permissions')
                    ->description('System access and permissions')
                    ->schema([
                        Select::make('role')
                            ->options([
                                'customer' => 'Customer',
                                'admin' => 'Admin',
                            ])
                            ->default('customer')
                            ->required(),
                            
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->placeholder('Not verified'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->size(40),
                    
                TextColumn::make('name')
                    ->label('Customer Name')
                    ->sortable()
                    ->searchable()
                    ->limit(25),
                    
                TextColumn::make('company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->limit(20)
                    ->toggleable(),
                    
                BadgeColumn::make('customer_type')
                    ->label('Type')
                    ->colors([
                        'info' => 'retail',
                        'success' => 'wholesale',
                        'warning' => 'contractor',
                        'primary' => 'distributor',
                    ])
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'retail')),
                    
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->limit(25)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),
                    
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                    
                TextColumn::make('credit_limit')
                    ->label('Credit Limit')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->color('info')
                    ->toggleable(),
                    
                TextColumn::make('current_debt')
                    ->label('Current Debt')
                    ->getStateUsing(function (User $record): float {
                        return $record->paymentTerms()
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('amount') - $record->paymentTerms()
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('paid_amount');
                    })
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'success')
                    ->sortable()
                    ->toggleable(),
                    
                BadgeColumn::make('is_verified')
                    ->label('Status')
                    ->getStateUsing(fn (User $record): string => $record->is_verified ? 'verified' : 'pending')
                    ->colors([
                        'success' => 'verified',
                        'warning' => 'pending',
                    ]),
                    
                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable()
                    ->color('info'),
                    
                TextColumn::make('payment_term_days')
                    ->label('Payment Terms')
                    ->formatStateUsing(fn ($state): string => $state > 0 ? "{$state} days" : 'Cash only')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->label('Customer Type')
                    ->options([
                        'retail' => 'Retail',
                        'wholesale' => 'Wholesale',
                        'contractor' => 'Contractor',
                        'distributor' => 'Distributor',
                    ]),
                    
                SelectFilter::make('is_verified')
                    ->label('Verification Status')
                    ->options([
                        '1' => 'Verified',
                        '0' => 'Pending Verification',
                    ]),
                    
                Tables\Filters\Filter::make('has_credit')
                    ->label('Has Credit Limit')
                    ->query(fn (Builder $query): Builder => $query->where('credit_limit', '>', 0)),
                    
                Tables\Filters\Filter::make('has_debt')
                    ->label('Has Outstanding Debt')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('paymentTerms', function (Builder $q) {
                            $q->whereNotIn('status', ['paid', 'cancelled']);
                        })),
            ])
            ->actions([
                Action::make('view_credit')
                    ->label('Credit Info')
                    ->icon('heroicon-m-credit-card')
                    ->color('info')
                    ->visible(fn (User $record): bool => $record->credit_limit > 0)
                    ->modalHeading(fn (User $record): string => "Credit Information - {$record->name}")
                    ->modalContent(function (User $record): string {
                        $debt = $record->paymentTerms()
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('amount') - $record->paymentTerms()
                            ->whereNotIn('status', ['paid', 'cancelled'])
                            ->sum('paid_amount');
                        $available = $record->credit_limit - $debt;
                        
                        return view('filament.modal.credit-info', [
                            'customer' => $record,
                            'debt' => $debt,
                            'available' => $available,
                        ])->render();
                    }),
                    
                Action::make('verify_customer')
                    ->label('Verify')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => !$record->is_verified)
                    ->action(function (User $record) {
                        $record->update(['is_verified' => true]);
                        Notification::make()
                            ->title('Customer verified successfully')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('verify_customers')
                        ->label('Verify Selected')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(fn (Collection $records) => 
                            $records->each(fn ($record) => $record->update(['is_verified' => true]))),
                            
                    Tables\Actions\BulkAction::make('export_customers')
                        ->label('Export Customers')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->action(function (Collection $records) {
                            Notification::make()
                                ->title('Customer list exported')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }    
}
