<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\StockManager;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 1;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Tabs::make('Product Information')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Section::make('Product Details')
                                    ->description('Basic product information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Product Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                
                                                Select::make('brand_id')
                                                    ->label('Brand')
                                                    ->options(Brand::all()->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->required(),
                                                    
                                                Select::make('category_id')
                                                    ->label('Category')
                                                    ->options(Category::all()->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->required(),
                                                    
                                                Select::make('supplier_id')
                                                    ->label('Supplier')
                                                    ->options(Supplier::all()->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->nullable(),
                                                    
                                                TextInput::make('barcode')
                                                    ->label('Barcode/SKU')
                                                    ->unique(ignoreRecord: true)
                                                    ->nullable(),
                                                    
                                                TextInput::make('unit')
                                                    ->label('Unit')
                                                    ->default('pcs')
                                                    ->required()
                                                    ->datalist(['pcs', 'box', 'kg', 'm', 'm2', 'm3', 'liter', 'roll']),
                                                    
                                                TextInput::make('location')
                                                    ->label('Warehouse Location')
                                                    ->placeholder('e.g., A1-B2')
                                                    ->nullable(),
                                            ]),
                                            
                                        Textarea::make('description')
                                            ->label('Description')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                            
                                        Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true),
                                            
                                        Toggle::make('is_bulk_only')
                                            ->label('Bulk Orders Only')
                                            ->helperText('Only available for wholesale/contractor customers')
                                            ->default(false),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('Pricing')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Section::make('Base Pricing')
                                    ->description('Set base retail price and minimum order quantity')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('price')
                                                    ->label('Retail Price')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->required()
                                                    ->helperText('Base price for retail customers'),
                                                    
                                                TextInput::make('min_order_qty')
                                                    ->label('Minimum Order Qty')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required(),
                                                    
                                                TextInput::make('wholesale_price')
                                                    ->label('Wholesale Price')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->nullable()
                                                    ->helperText('Leave empty to use price levels'),
                                            ]),
                                    ]),
                                    
                                Section::make('Price Levels')
                                    ->description('Configure tiered pricing for different customer types')
                                    ->schema([
                                        Repeater::make('priceLevels')
                                            ->relationship()
                                            ->schema([
                                                Grid::make(4)
                                                    ->schema([
                                                        Select::make('level_type')
                                                            ->label('Customer Type')
                                                            ->options([
                                                                'wholesale' => 'Wholesale',
                                                                'contractor' => 'Contractor', 
                                                                'distributor' => 'Distributor',
                                                            ])
                                                            ->required(),
                                                            
                                                        TextInput::make('min_quantity')
                                                            ->label('Min Quantity')
                                                            ->numeric()
                                                            ->required()
                                                            ->default(1),
                                                            
                                                        TextInput::make('price')
                                                            ->label('Price')
                                                            ->numeric()
                                                            ->prefix('Rp')
                                                            ->required(),
                                                            
                                                        Toggle::make('is_active')
                                                            ->label('Active')
                                                            ->default(true),
                                                    ]),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Price Level')
                                            ->collapsible(),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('Inventory')
                            ->icon('heroicon-m-cube')
                            ->schema([
                                Section::make('Stock Information')
                                    ->description('Current stock levels and reorder settings')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('current_stock')
                                                    ->label('Current Stock')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $component) {
                                                        $reorderLevel = $component->getContainer()->getComponent('reorder_level')->getState();
                                                        if ($state <= $reorderLevel) {
                                                            Notification::make()
                                                                ->warning()
                                                                ->title('Low Stock Warning')
                                                                ->body('Current stock is at or below reorder level.')
                                                                ->send();
                                                        }
                                                    }),
                                                    
                                                TextInput::make('reorder_level')
                                                    ->label('Reorder Level')
                                                    ->numeric()
                                                    ->default(10)
                                                    ->required()
                                                    ->helperText('Alert when stock reaches this level'),
                                                    
                                                TextInput::make('stock')
                                                    ->label('Legacy Stock')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->helperText('Old stock field for reference'),
                                            ]),
                                    ]),
                                    
                                Section::make('Stock Actions')
                                    ->description('Quick stock management actions')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Actions::make([
                                                    FormAction::make('stock_in')
                                                        ->label('Stock In')
                                                        ->icon('heroicon-m-plus-circle')
                                                        ->color('success')
                                                        ->form([
                                                            TextInput::make('quantity')
                                                                ->label('Quantity to Add')
                                                                ->numeric()
                                                                ->required(),
                                                            Select::make('reference_type')
                                                                ->label('Reference')
                                                                ->options([
                                                                    'purchase' => 'Purchase',
                                                                    'adjustment' => 'Adjustment',
                                                                    'return' => 'Return',
                                                                ])
                                                                ->required(),
                                                            Textarea::make('notes')
                                                                ->label('Notes')
                                                                ->nullable(),
                                                        ])
                                                        ->action(function (array $data, $record) {
                                                            $stockManager = app(StockManager::class);
                                                            $stockManager->stockIn(
                                                                $record->id,
                                                                $data['quantity'],
                                                                $data['reference_type'],
                                                                null,
                                                                $data['notes']
                                                            );
                                                            
                                                            Notification::make()
                                                                ->success()
                                                                ->title('Stock Updated')
                                                                ->body("Added {$data['quantity']} units to stock")
                                                                ->send();
                                                        }),
                                                        
                                                    FormAction::make('stock_adjustment')
                                                        ->label('Stock Adjustment')
                                                        ->icon('heroicon-m-adjustments-horizontal')
                                                        ->color('warning')
                                                        ->form([
                                                            TextInput::make('actual_stock')
                                                                ->label('Actual Stock Count')
                                                                ->numeric()
                                                                ->required(),
                                                            TextInput::make('reason')
                                                                ->label('Reason')
                                                                ->required()
                                                                ->placeholder('e.g., Physical count discrepancy'),
                                                            Textarea::make('notes')
                                                                ->label('Additional Notes')
                                                                ->nullable(),
                                                        ])
                                                        ->action(function (array $data, $record) {
                                                            $stockManager = app(StockManager::class);
                                                            $stockManager->stockAdjustment(
                                                                $record->id,
                                                                $data['actual_stock'],
                                                                $data['reason'],
                                                                $data['notes']
                                                            );
                                                            
                                                            Notification::make()
                                                                ->success()
                                                                ->title('Stock Adjusted')
                                                                ->body("Stock updated to {$data['actual_stock']} units")
                                                                ->send();
                                                        }),
                                                ]),
                                            ]),
                                    ])
                                    ->hiddenOn('create'),
                            ]),
                            
                        Tabs\Tab::make('Images')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                Section::make('Product Images')
                                    ->description('Upload product images and gallery')
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label('Main Product Image')
                                            ->image()
                                            ->directory('products')
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->required(),
                                            
                                        FileUpload::make('images')
                                            ->label('Additional Images')
                                            ->image()
                                            ->directory('products/gallery')
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->imageEditor()
                                            ->helperText('Upload up to 5 additional product images'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->size(50)
                    ->circular(),
                    
                TextColumn::make('name')
                    ->label('Product Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                    
                TextColumn::make('barcode')
                    ->label('SKU/Barcode')
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->placeholder('No supplier'),
                    
                TextColumn::make('price')
                    ->label('Retail Price')
                    ->money('IDR')
                    ->sortable(),
                    
                TextColumn::make('current_stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $record): string => match(true) {
                        $record->current_stock <= 0 => 'danger',
                        $record->current_stock <= ($record->reorder_level ?? 10) => 'warning',
                        default => 'success'
                    })
                    ->formatStateUsing(fn (Product $record): string => 
                        $record->current_stock . ' ' . ($record->unit ?? 'pcs')
                    ),
                    
                BadgeColumn::make('stock_status')
                    ->label('Status')
                    ->getStateUsing(function (Product $record): string {
                        if ($record->current_stock <= 0) return 'out_of_stock';
                        if ($record->current_stock <= ($record->reorder_level ?? 10)) return 'low_stock';
                        return 'in_stock';
                    })
                    ->colors([
                        'danger' => 'out_of_stock',
                        'warning' => 'low_stock', 
                        'success' => 'in_stock',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        'in_stock' => 'In Stock',
                        default => 'Unknown'
                    }),
                    
                BadgeColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ])
                    ->query(function ($query, $data) {
                        return $query->when($data['value'], function ($query) use ($data) {
                            match ($data['value']) {
                                'out_of_stock' => $query->where('current_stock', '<=', 0),
                                'low_stock' => $query->whereRaw('current_stock > 0 AND current_stock <= reorder_level'),
                                'in_stock' => $query->whereRaw('current_stock > reorder_level'),
                                default => $query
                            };
                        });
                    }),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active products only')
                    ->falseLabel('Inactive products only')
                    ->native(false),
            ])
            ->actions([
                Action::make('quick_stock')
                    ->label('Stock In')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantity to Add')
                            ->numeric()
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->nullable(),
                    ])
                    ->action(function (array $data, Product $record): void {
                        $stockManager = app(StockManager::class);
                        $stockManager->stockIn(
                            $record->id,
                            $data['quantity'],
                            'adjustment',
                            null,
                            $data['notes']
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('Stock Updated')
                            ->body("Added {$data['quantity']} units to {$record->name}")
                            ->send();
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    
}
