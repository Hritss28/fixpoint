<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Essential supplier details and contact information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter supplier name'),
                                    
                                TextInput::make('code')
                                    ->label('Supplier Code')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('SUP-001')
                                    ->helperText('Unique identifier for the supplier'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('contact_person')
                                    ->maxLength(255)
                                    ->placeholder('Contact person name'),
                                    
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('+1 234 567 8900'),
                            ]),
                            
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('supplier@company.com'),
                    ]),
                    
                Section::make('Address Information')
                    ->description('Physical and billing address details')
                    ->schema([
                        Textarea::make('address')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Complete address with street, city, state, and postal code'),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('city')
                                    ->maxLength(100)
                                    ->placeholder('City'),
                                    
                                TextInput::make('state')
                                    ->maxLength(100)
                                    ->placeholder('State/Province'),
                                    
                                TextInput::make('postal_code')
                                    ->maxLength(20)
                                    ->placeholder('Postal/ZIP code'),
                            ]),
                            
                        TextInput::make('country')
                            ->default('Indonesia')
                            ->maxLength(100)
                            ->placeholder('Country'),
                    ]),
                    
                Section::make('Business Details')
                    ->description('Tax information and business status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('tax_number')
                                    ->label('Tax ID/NPWP')
                                    ->maxLength(50)
                                    ->placeholder('Tax identification number'),
                                    
                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'pending' => 'Pending Approval',
                                        'suspended' => 'Suspended',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                            
                        Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Additional notes about this supplier'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                    
                TextColumn::make('name')
                    ->label('Supplier Name')
                    ->sortable()
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),
                    
                TextColumn::make('contact_person')
                    ->label('Contact')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->limit(20),
                    
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable()
                    ->limit(25),
                    
                TextColumn::make('city')
                    ->label('City')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'pending',
                        'secondary' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable()
                    ->color('info'),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'pending' => 'Pending Approval',
                        'suspended' => 'Suspended',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('city')
                    ->relationship('city', 'city')
                    ->searchable()
                    ->multiple(),
                    
                Tables\Filters\Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query): Builder => $query->has('products')),
            ])
            ->actions([
                Action::make('view_products')
                    ->label('Products')
                    ->icon('heroicon-m-cube')
                    ->url(fn (Supplier $record): string => ProductResource::getUrl('index', [
                        'tableFilters' => [
                            'supplier' => ['values' => [$record->id]]
                        ]
                    ]))
                    ->color('info'),
                    
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->before(function (Supplier $record) {
                        if ($record->products()->count() > 0) {
                            throw new \Exception('Cannot delete supplier with associated products.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Mark as Active')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'active'])),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Mark as Inactive')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'inactive'])),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
