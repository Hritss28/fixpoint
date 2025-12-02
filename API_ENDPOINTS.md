# 🔌 API ENDPOINTS DOCUMENTATION

## 🛡️ AUTHENTICATION

Semua API endpoint memerlukan authentication menggunakan Sanctum token.

```bash
# Login
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}

# Response
{
    "token": "1|abc123...",
    "user": {...}
}

# Gunakan token untuk request selanjutnya
Authorization: Bearer {token}
```

---

## 📦 STOCK MANAGEMENT API

### 1. Record Stock In

```http
POST /api/stock/in
Authorization: Bearer {token}
Content-Type: application/json

{
    "product_id": 1,
    "quantity": 100,
    "unit": "sak",
    "reference_type": "purchase",
    "reference_id": 123,
    "price_per_unit": 60000,
    "notes": "Purchase from PT Semen Indonesia"
}

# Response 200
{
    "success": true,
    "message": "Stock in recorded successfully",
    "data": {
        "id": 1,
        "product_id": 1,
        "type": "in",
        "quantity": 100,
        "unit": "sak",
        "total_value": 6000000,
        "created_at": "2025-11-18T10:00:00.000000Z"
    }
}
```

### 2. Record Stock Out

```http
POST /api/stock/out
Authorization: Bearer {token}
Content-Type: application/json

{
    "product_id": 1,
    "quantity": 10,
    "unit": "sak",
    "reference_type": "order",
    "reference_id": 456,
    "notes": "Sold to customer"
}

# Response 200
{
    "success": true,
    "message": "Stock out recorded successfully",
    "data": {
        "id": 2,
        "product_id": 1,
        "type": "out",
        "quantity": 10,
        "unit": "sak",
        "created_at": "2025-11-18T11:00:00.000000Z"
    }
}
```

### 3. Stock Adjustment

```http
POST /api/stock/adjustment
Authorization: Bearer {token}
Content-Type: application/json

{
    "product_id": 1,
    "new_stock": 85,
    "unit": "sak",
    "notes": "Stock opname - found 5 damaged units"
}

# Response 200
{
    "success": true,
    "message": "Stock adjusted successfully",
    "data": {
        "id": 3,
        "product_id": 1,
        "type": "adjustment",
        "quantity": 5,
        "old_stock": 90,
        "new_stock": 85,
        "created_at": "2025-11-18T12:00:00.000000Z"
    }
}
```

### 4. Get Stock Movements

```http
GET /api/stock/movements?product_id=1&type=in&date_from=2025-11-01
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "id": 1,
            "product": {
                "id": 1,
                "name": "Semen Gresik 50kg",
                "sku": "SGR-50KG"
            },
            "type": "in",
            "quantity": 100,
            "unit": "sak",
            "total_value": 6000000,
            "user": {
                "name": "Admin"
            },
            "created_at": "2025-11-18T10:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 1
    }
}
```

### 5. Stock Report

```http
GET /api/stock/report?date_from=2025-11-01&date_to=2025-11-30
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "summary": {
            "total_products": 50,
            "total_stock_value": 150000000,
            "low_stock_count": 5
        },
        "products": [
            {
                "id": 1,
                "name": "Semen Gresik 50kg",
                "sku": "SGR-50KG",
                "stock": 85,
                "unit": "sak",
                "stock_value": 5100000,
                "stock_in": 100,
                "stock_out": 15,
                "is_low_stock": false
            }
        ]
    }
}
```

### 6. Low Stock Alert

```http
GET /api/stock/low-stock
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "id": 2,
            "name": "Besi Beton 10mm",
            "sku": "BB-10MM",
            "stock": 8,
            "reorder_level": 20,
            "unit": "batang",
            "supplier": {
                "id": 2,
                "name": "CV Besi Jaya"
            }
        }
    ]
}
```

---

## 🚚 DELIVERY NOTES API

### 1. List Delivery Notes

```http
GET /api/delivery-notes?status=pending&page=1
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "id": 1,
            "delivery_number": "SJ-20251118-0001",
            "order_id": 123,
            "order_number": "ORD-20251118-0001",
            "customer": {
                "id": 5,
                "name": "Budi Kontraktor"
            },
            "delivery_date": "2025-11-19",
            "status": "pending",
            "driver_name": null,
            "vehicle_number": null,
            "items_count": 3,
            "created_at": "2025-11-18T14:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 10
    }
}
```

### 2. Create Delivery Note

```http
POST /api/delivery-notes
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 123,
    "delivery_date": "2025-11-19",
    "driver_name": "Pak Budi",
    "vehicle_number": "B 1234 XYZ",
    "notes": "Harap hubungi sebelum kirim"
}

# Response 201
{
    "success": true,
    "message": "Delivery note created successfully",
    "data": {
        "id": 1,
        "delivery_number": "SJ-20251118-0001",
        "order_id": 123,
        "delivery_date": "2025-11-19",
        "status": "pending",
        "items": [...]
    }
}
```

### 3. Get Delivery Note Detail

```http
GET /api/delivery-notes/1
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "id": 1,
        "delivery_number": "SJ-20251118-0001",
        "order": {
            "id": 123,
            "order_number": "ORD-20251118-0001",
            "total_amount": 6500000
        },
        "customer": {
            "id": 5,
            "name": "Budi Kontraktor",
            "phone": "08123456789",
            "address": "Jl. Raya No. 123"
        },
        "delivery_date": "2025-11-19",
        "driver_name": "Pak Budi",
        "vehicle_number": "B 1234 XYZ",
        "status": "pending",
        "items": [
            {
                "id": 1,
                "product_id": 1,
                "product_name": "Semen Gresik 50kg",
                "quantity": 100,
                "unit": "sak"
            }
        ],
        "created_at": "2025-11-18T14:00:00.000000Z"
    }
}
```

### 4. Update Delivery Note

```http
PUT /api/delivery-notes/1
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "in_transit",
    "driver_name": "Pak Budi",
    "vehicle_number": "B 1234 XYZ"
}

# Response 200
{
    "success": true,
    "message": "Delivery note updated successfully",
    "data": {...}
}
```

### 5. Confirm Delivery

```http
POST /api/delivery-notes/1/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
    "recipient_name": "John Doe",
    "recipient_signature": "data:image/png;base64,..."
}

# Response 200
{
    "success": true,
    "message": "Delivery confirmed successfully",
    "data": {
        "id": 1,
        "status": "delivered",
        "delivered_at": "2025-11-19T15:30:00.000000Z",
        "recipient_name": "John Doe"
    }
}
```

### 6. Print Delivery Note

```http
GET /api/delivery-notes/1/print
Authorization: Bearer {token}

# Response 200 (PDF)
Content-Type: application/pdf
```

---

## 💳 PAYMENT TERMS API

### 1. List Payment Terms

```http
GET /api/payment-terms?status=pending&customer_id=5
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "id": 1,
            "order_id": 123,
            "order_number": "ORD-20251118-0001",
            "customer": {
                "id": 5,
                "name": "Budi Kontraktor"
            },
            "due_date": "2025-12-18",
            "amount": 6500000,
            "paid_amount": 0,
            "remaining_amount": 6500000,
            "status": "pending",
            "days_until_due": 30,
            "is_overdue": false
        }
    ]
}
```

### 2. Create Payment Term

```http
POST /api/payment-terms
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 123,
    "customer_id": 5,
    "amount": 6500000,
    "payment_term_days": 30,
    "notes": "Tempo 30 hari"
}

# Response 201
{
    "success": true,
    "message": "Payment term created successfully",
    "data": {
        "id": 1,
        "order_id": 123,
        "due_date": "2025-12-18",
        "amount": 6500000,
        "status": "pending"
    }
}
```

### 3. Get Payment Term Detail

```http
GET /api/payment-terms/1
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "id": 1,
        "order": {
            "id": 123,
            "order_number": "ORD-20251118-0001"
        },
        "customer": {
            "id": 5,
            "name": "Budi Kontraktor",
            "company_name": "CV Budi Konstruksi"
        },
        "due_date": "2025-12-18",
        "amount": 6500000,
        "paid_amount": 0,
        "remaining_amount": 6500000,
        "status": "pending",
        "payment_history": []
    }
}
```

### 4. Record Payment

```http
POST /api/payment-terms/1/pay
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 3000000,
    "payment_method": "transfer",
    "payment_reference": "TRF123456789",
    "notes": "Pembayaran sebagian"
}

# Response 200
{
    "success": true,
    "message": "Payment recorded successfully",
    "data": {
        "id": 1,
        "paid_amount": 3000000,
        "remaining_amount": 3500000,
        "status": "partial",
        "payment_date": "2025-11-25"
    }
}
```

### 5. Get Overdue Payments

```http
GET /api/payment-terms/overdue
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "id": 2,
            "order_number": "ORD-20251001-0001",
            "customer": {
                "id": 6,
                "name": "Toko ABC"
            },
            "due_date": "2025-11-01",
            "days_overdue": 17,
            "amount": 5000000,
            "remaining_amount": 5000000,
            "status": "overdue"
        }
    ],
    "summary": {
        "total_overdue": 2,
        "total_amount": 8500000
    }
}
```

---

## 👤 CUSTOMER CREDIT API

### 1. Get Customer Credit Info

```http
GET /api/customers/5/credit
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "customer_id": 5,
        "customer_name": "Budi Kontraktor",
        "credit_limit": 50000000,
        "current_debt": 6500000,
        "available_credit": 43500000,
        "credit_usage_percentage": 13,
        "is_active": true,
        "pending_payments": [
            {
                "order_number": "ORD-20251118-0001",
                "amount": 6500000,
                "due_date": "2025-12-18"
            }
        ]
    }
}
```

### 2. Adjust Credit Limit

```http
POST /api/customers/5/credit/adjust
Authorization: Bearer {token}
Content-Type: application/json

{
    "new_limit": 75000000,
    "notes": "Increased due to good payment history"
}

# Response 200
{
    "success": true,
    "message": "Credit limit adjusted successfully",
    "data": {
        "customer_id": 5,
        "old_limit": 50000000,
        "new_limit": 75000000,
        "available_credit": 68500000
    }
}
```

### 3. Get Customer Debts

```http
GET /api/customers/5/debts
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "customer": {
            "id": 5,
            "name": "Budi Kontraktor",
            "credit_limit": 50000000
        },
        "debts": [
            {
                "id": 1,
                "order_number": "ORD-20251118-0001",
                "order_date": "2025-11-18",
                "due_date": "2025-12-18",
                "amount": 6500000,
                "paid_amount": 0,
                "remaining": 6500000,
                "status": "pending",
                "days_until_due": 30
            }
        ],
        "summary": {
            "total_debt": 6500000,
            "total_overdue": 0,
            "pending_count": 1
        }
    }
}
```

### 4. Aging Report

```http
GET /api/reports/aging
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "customer_id": 5,
            "customer_name": "Budi Kontraktor",
            "current": 6500000,
            "days_1_30": 0,
            "days_31_60": 0,
            "days_61_90": 0,
            "over_90": 0,
            "total": 6500000
        }
    ],
    "summary": {
        "total_receivables": 6500000,
        "current_percentage": 100
    }
}
```

---

## 💰 PRICE MANAGEMENT API

### 1. Get Product Prices

```http
GET /api/products/1/prices
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "product": {
            "id": 1,
            "name": "Semen Gresik 50kg",
            "sku": "SGR-50KG"
        },
        "price_levels": [
            {
                "level_type": "retail",
                "min_quantity": 1,
                "price": 65000,
                "is_active": true
            },
            {
                "level_type": "wholesale",
                "min_quantity": 10,
                "price": 62000,
                "is_active": true
            },
            {
                "level_type": "contractor",
                "min_quantity": 50,
                "price": 60000,
                "is_active": true
            }
        ]
    }
}
```

### 2. Set Price Level

```http
POST /api/products/1/prices
Authorization: Bearer {token}
Content-Type: application/json

{
    "level_type": "distributor",
    "min_quantity": 100,
    "price": 58000,
    "is_active": true
}

# Response 201
{
    "success": true,
    "message": "Price level created successfully",
    "data": {
        "id": 4,
        "product_id": 1,
        "level_type": "distributor",
        "min_quantity": 100,
        "price": 58000
    }
}
```

### 3. Calculate Price

```http
POST /api/prices/calculate
Authorization: Bearer {token}
Content-Type: application/json

{
    "product_id": 1,
    "quantity": 25,
    "customer_type": "wholesale"
}

# Response 200
{
    "success": true,
    "data": {
        "product_id": 1,
        "product_name": "Semen Gresik 50kg",
        "quantity": 25,
        "customer_type": "wholesale",
        "price_per_unit": 62000,
        "subtotal": 1550000,
        "applied_price_level": "wholesale"
    }
}
```

---

## 🏢 SUPPLIERS API

### 1. List Suppliers

```http
GET /api/suppliers?is_active=true
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "PT Semen Indonesia",
            "company_name": "PT Semen Indonesia Tbk",
            "phone": "021-12345678",
            "city": "Jakarta",
            "payment_terms": 30,
            "is_active": true,
            "products_count": 5
        }
    ]
}
```

### 2. Get Supplier Products

```http
GET /api/suppliers/1/products
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "supplier": {
            "id": 1,
            "name": "PT Semen Indonesia"
        },
        "products": [
            {
                "id": 1,
                "name": "Semen Gresik 50kg",
                "sku": "SGR-50KG",
                "stock": 85,
                "price": 65000
            }
        ]
    }
}
```

---

## 📊 REPORTS API

### 1. Sales Report

```http
GET /api/reports/sales?date_from=2025-11-01&date_to=2025-11-30
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "period": {
            "from": "2025-11-01",
            "to": "2025-11-30"
        },
        "summary": {
            "total_orders": 150,
            "total_sales": 450000000,
            "total_cash": 300000000,
            "total_tempo": 150000000,
            "average_order_value": 3000000
        },
        "by_customer_type": [
            {
                "customer_type": "retail",
                "orders_count": 80,
                "total_sales": 150000000
            },
            {
                "customer_type": "wholesale",
                "orders_count": 50,
                "total_sales": 200000000
            },
            {
                "customer_type": "contractor",
                "orders_count": 20,
                "total_sales": 100000000
            }
        ],
        "daily_sales": [...]
    }
}
```

### 2. Inventory Report

```http
GET /api/reports/inventory
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "summary": {
            "total_products": 50,
            "total_stock_value": 150000000,
            "low_stock_items": 5,
            "out_of_stock_items": 2
        },
        "by_category": [
            {
                "category": "Semen",
                "products_count": 10,
                "total_stock_value": 50000000
            }
        ],
        "top_products": [...]
    }
}
```

### 3. Receivables Report

```http
GET /api/reports/receivables
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": {
        "summary": {
            "total_receivables": 250000000,
            "current": 150000000,
            "overdue": 100000000,
            "customers_with_debt": 25
        },
        "aging": [...],
        "top_debtors": [...]
    }
}
```

### 4. Top Products

```http
GET /api/reports/top-products?limit=10
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "product_id": 1,
            "product_name": "Semen Gresik 50kg",
            "total_quantity_sold": 1500,
            "total_sales": 97500000,
            "orders_count": 150
        }
    ]
}
```

### 5. Top Customers

```http
GET /api/reports/top-customers?limit=10
Authorization: Bearer {token}

# Response 200
{
    "success": true,
    "data": [
        {
            "customer_id": 5,
            "customer_name": "Budi Kontraktor",
            "customer_type": "contractor",
            "total_orders": 25,
            "total_purchases": 75000000,
            "average_order_value": 3000000
        }
    ]
}
```

---

## 🧪 TESTING API WITH CURL

### Example: Create Stock In

```bash
curl -X POST http://localhost:8000/api/stock/in \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 100,
    "unit": "sak",
    "price_per_unit": 60000,
    "notes": "Test stock in"
  }'
```

### Example: Get Payment Terms

```bash
curl -X GET "http://localhost:8000/api/payment-terms?status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📝 API ROUTES FILE

```php
<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\DeliveryNoteController;
use App\Http\Controllers\Api\PaymentTermController;
use App\Http\Controllers\Api\CustomerCreditController;
use App\Http\Controllers\Api\PriceController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\ReportController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Stock Management
    Route::prefix('stock')->group(function () {
        Route::post('/in', [StockController::class, 'stockIn']);
        Route::post('/out', [StockController::class, 'stockOut']);
        Route::post('/adjustment', [StockController::class, 'adjustment']);
        Route::get('/movements', [StockController::class, 'movements']);
        Route::get('/report', [StockController::class, 'report']);
        Route::get('/low-stock', [StockController::class, 'lowStock']);
    });
    
    // Delivery Notes
    Route::apiResource('delivery-notes', DeliveryNoteController::class);
    Route::post('/delivery-notes/{id}/confirm', [DeliveryNoteController::class, 'confirm']);
    Route::get('/delivery-notes/{id}/print', [DeliveryNoteController::class, 'print']);
    
    // Payment Terms
    Route::apiResource('payment-terms', PaymentTermController::class);
    Route::post('/payment-terms/{id}/pay', [PaymentTermController::class, 'recordPayment']);
    Route::get('/payment-terms/overdue', [PaymentTermController::class, 'overdue']);
    
    // Customer Credit
    Route::prefix('customers')->group(function () {
        Route::get('/{id}/credit', [CustomerCreditController::class, 'show']);
        Route::post('/{id}/credit/adjust', [CustomerCreditController::class, 'adjust']);
        Route::get('/{id}/debts', [CustomerCreditController::class, 'debts']);
    });
    
    // Price Management
    Route::get('/products/{id}/prices', [PriceController::class, 'index']);
    Route::post('/products/{id}/prices', [PriceController::class, 'store']);
    Route::post('/prices/calculate', [PriceController::class, 'calculate']);
    
    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('/suppliers/{id}/products', [SupplierController::class, 'products']);
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales']);
        Route::get('/inventory', [ReportController::class, 'inventory']);
        Route::get('/receivables', [ReportController::class, 'receivables']);
        Route::get('/aging', [ReportController::class, 'aging']);
        Route::get('/top-products', [ReportController::class, 'topProducts']);
        Route::get('/top-customers', [ReportController::class, 'topCustomers']);
    });
});
```

---

## ✅ API TESTING CHECKLIST

```
Authentication:
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Access protected route without token
- [ ] Access protected route with valid token

Stock Management:
- [ ] Record stock in
- [ ] Record stock out with sufficient stock
- [ ] Record stock out with insufficient stock (should fail)
- [ ] Stock adjustment
- [ ] Get stock movements with filters
- [ ] Get stock report
- [ ] Get low stock alerts

Delivery Notes:
- [ ] Create delivery note
- [ ] List delivery notes with filters
- [ ] Get delivery note detail
- [ ] Update delivery note status
- [ ] Confirm delivery with signature
- [ ] Print delivery note PDF

Payment Terms:
- [ ] List payment terms
- [ ] Get payment term detail
- [ ] Record full payment
- [ ] Record partial payment
- [ ] Get overdue payments

Customer Credit:
- [ ] Get customer credit info
- [ ] Adjust credit limit
- [ ] Get customer debts
- [ ] Check credit before purchase

Reports:
- [ ] Generate sales report
- [ ] Generate inventory report
- [ ] Generate receivables report
- [ ] Get aging report
- [ ] Get top products
- [ ] Get top customers
```

Done! Complete API documentation ready. 🚀
