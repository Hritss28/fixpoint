# 🏗️ PANDUAN KONVERSI GPX STORE → FIXPOINT TOKO MATERIAL

## 📋 RINGKASAN EKSEKUTIF

**Project:** Konversi GPX Store menjadi Fixpoint - Sistem Manajemen Toko Material Bangunan  
**Stack:** Laravel 12 + Filament 3 + Livewire 3 + Tailwind CSS + Midtrans  
**Estimasi Waktu:** 4-6 Minggu  
**Kompleksitas:** Medium-High

---

## 🎯 ANALISIS STRUKTUR PROJECT EXISTING

### ✅ Komponen yang Dapat Digunakan Ulang
- ✓ Sistem autentikasi & user management
- ✓ Filament admin panel (kustomisasi UI)
- ✓ Integrasi Midtrans (untuk pembayaran tempo)
- ✓ Sistem email & notifikasi
- ✓ Livewire components (Cart, Transactions)
- ✓ Spatie Permission (role management)
- ✓ Dashboard & reporting framework

### ⚠️ Komponen yang Perlu Modifikasi Signifikan
- ⚠️ Model Product → Tambah fitur stok grosir, satuan, minimal order
- ⚠️ Model Order → Tambah fitur surat jalan, tempo pembayaran
- ⚠️ Database schema → Tambah tabel baru untuk stok in/out, delivery notes
- ⚠️ UI/UX → Rebrand dari e-commerce fashion ke toko bangunan
- ⚠️ Cart system → Support multiple price tiers (retail/grosir)

### ❌ Komponen yang Tidak Diperlukan
- ❌ Wishlist (tidak relevan untuk B2B)
- ❌ Product reviews public (diganti approval sistem)
- ❌ Newsletter marketing (optional)
- ❌ Social login (tidak prioritas B2B)

---

## 📁 DAFTAR FILE YANG PERLU DIUBAH

### 1. Models (app/Models/)
```
✏️ Product.php - Tambah harga grosir, minimal order, satuan
✏️ Order.php - Tambah tempo pembayaran, surat jalan
✏️ OrderItem.php - Tambah satuan per item
✏️ User.php - Tambah tipe pelanggan (retail/grosir/kontraktor)
🆕 StockMovement.php - Stok masuk/keluar
🆕 DeliveryNote.php - Surat jalan
🆕 PaymentTerm.php - Tempo pembayaran
🆕 CustomerCredit.php - Piutang pelanggan
🆕 Supplier.php - Data supplier
🆕 PriceLevel.php - Level harga (retail/grosir/kontraktor)
```

### 2. Migrations (database/migrations/)
```
🆕 create_stock_movements_table.php
🆕 create_delivery_notes_table.php
🆕 create_payment_terms_table.php
🆕 create_customer_credits_table.php
🆕 create_suppliers_table.php
🆕 create_price_levels_table.php
🆕 add_building_store_columns_to_products_table.php
🆕 add_customer_type_to_users_table.php
🆕 create_product_price_levels_table.php
```

### 3. Filament Resources (app/Filament/Resources/)
```
✏️ ProductResource.php - Form untuk harga grosir, stok
✏️ OrderResource.php - Tambah surat jalan, tempo
🆕 StockMovementResource.php
🆕 DeliveryNoteResource.php
🆕 CustomerCreditResource.php
🆕 SupplierResource.php
🆕 ReportResource.php (piutang, stok, penjualan)
```

### 4. Controllers & Livewire
```
✏️ Cart.php - Support harga bertingkat
🆕 PriceCalculator.php - Hitung harga based on customer type
🆕 StockChecker.php - Cek ketersediaan real-time
🆕 CreditLimit.php - Validasi limit kredit
```

### 5. Views (resources/views/)
```
✏️ layouts/app.blade.php - Rebrand header/footer
✏️ product/show.blade.php - Tampilan produk bangunan
✏️ cart/index.blade.php - Cart dengan harga grosir
🆕 delivery-note/print.blade.php - Print surat jalan
🆕 invoice/print.blade.php - Print invoice tempo
```

### 6. Config Files
```
✏️ config/app.php - Update app name
🆕 config/building-store.php - Konfigurasi toko bangunan
```

---

## 🗄️ PERUBAHAN DATABASE SCHEMA

### 🆕 Tabel Baru

#### 1. `stock_movements`
```sql
- id (bigint, PK)
- product_id (bigint, FK)
- type (enum: 'in', 'out', 'adjustment')
- quantity (decimal)
- unit (varchar) - 'pcs', 'box', 'm3', 'kg', etc
- reference_type (varchar) - 'order', 'purchase', 'adjustment'
- reference_id (bigint)
- notes (text)
- user_id (bigint, FK) - who made the transaction
- created_at, updated_at
```

#### 2. `delivery_notes`
```sql
- id (bigint, PK)
- delivery_number (varchar, unique)
- order_id (bigint, FK)
- customer_id (bigint, FK)
- delivery_date (date)
- driver_name (varchar)
- vehicle_number (varchar)
- status (enum: 'pending', 'in_transit', 'delivered', 'returned')
- recipient_name (varchar)
- recipient_signature (text) - base64 image
- notes (text)
- delivered_at (timestamp)
- created_at, updated_at
```

#### 3. `payment_terms`
```sql
- id (bigint, PK)
- order_id (bigint, FK)
- customer_id (bigint, FK)
- due_date (date)
- amount (decimal)
- paid_amount (decimal)
- status (enum: 'pending', 'partial', 'paid', 'overdue')
- payment_date (date)
- notes (text)
- created_at, updated_at
```

#### 4. `customer_credits`
```sql
- id (bigint, PK)
- customer_id (bigint, FK)
- credit_limit (decimal)
- current_debt (decimal)
- available_credit (decimal)
- is_active (boolean)
- notes (text)
- created_at, updated_at
```

#### 5. `suppliers`
```sql
- id (bigint, PK)
- name (varchar)
- company_name (varchar)
- email (varchar)
- phone (varchar)
- address (text)
- city (varchar)
- province (varchar)
- postal_code (varchar)
- tax_number (varchar) - NPWP
- payment_terms (integer) - days
- is_active (boolean)
- notes (text)
- created_at, updated_at
```

#### 6. `price_levels`
```sql
- id (bigint, PK)
- product_id (bigint, FK)
- level_type (enum: 'retail', 'wholesale', 'contractor', 'distributor')
- min_quantity (integer)
- price (decimal)
- is_active (boolean)
- created_at, updated_at
```

### ✏️ Modifikasi Tabel Existing

#### `products` - Tambah Kolom
```sql
ALTER TABLE products ADD COLUMN:
- unit (varchar) DEFAULT 'pcs' - satuan jual
- min_order_qty (integer) DEFAULT 1
- wholesale_price (decimal)
- contractor_price (decimal)
- supplier_id (bigint, FK)
- reorder_level (integer) - stok minimum
- location (varchar) - lokasi gudang/rak
- barcode (varchar)
- is_bulk_only (boolean) DEFAULT false
```

#### `orders` - Tambah Kolom
```sql
ALTER TABLE orders ADD COLUMN:
- customer_type (enum: 'retail', 'wholesale', 'contractor')
- payment_term_days (integer) DEFAULT 0
- due_date (date)
- delivery_note_id (bigint, FK)
- project_name (varchar) - untuk kontraktor
- tax_invoice_number (varchar) - nomor faktur pajak
```

#### `users` - Tambah Kolom
```sql
ALTER TABLE users ADD COLUMN:
- customer_type (enum: 'retail', 'wholesale', 'contractor', 'distributor')
- company_name (varchar)
- tax_number (varchar) - NPWP
- credit_limit (decimal) DEFAULT 0
- payment_term_days (integer) DEFAULT 0
- billing_address (text)
- shipping_address (text)
- is_verified (boolean) DEFAULT false
```

---

## 🔌 API ENDPOINTS BARU

### Stock Management
```
POST   /api/stock/in              - Stok masuk
POST   /api/stock/out             - Stok keluar
GET    /api/stock/movements       - History pergerakan stok
GET    /api/stock/report          - Laporan stok
POST   /api/stock/adjustment      - Penyesuaian stok
GET    /api/stock/low-stock       - Alert stok menipis
```

### Delivery Notes
```
GET    /api/delivery-notes        - List surat jalan
POST   /api/delivery-notes        - Buat surat jalan
GET    /api/delivery-notes/{id}   - Detail surat jalan
PUT    /api/delivery-notes/{id}   - Update surat jalan
POST   /api/delivery-notes/{id}/confirm - Konfirmasi terima
GET    /api/delivery-notes/{id}/print  - Print surat jalan
```

### Payment Terms
```
GET    /api/payment-terms         - List tempo pembayaran
POST   /api/payment-terms         - Buat tempo pembayaran
GET    /api/payment-terms/{id}    - Detail tempo
PUT    /api/payment-terms/{id}    - Update pembayaran
GET    /api/payment-terms/overdue - List jatuh tempo
POST   /api/payment-terms/{id}/pay - Bayar tempo
```

### Customer Credit
```
GET    /api/customers/{id}/credit - Info kredit pelanggan
POST   /api/customers/{id}/credit/adjust - Adjust limit kredit
GET    /api/customers/{id}/debts  - List piutang
GET    /api/reports/aging         - Aging report piutang
```

### Price Management
```
GET    /api/products/{id}/prices  - Harga bertingkat
POST   /api/products/{id}/prices  - Set harga tingkat
GET    /api/prices/calculate      - Kalkulasi harga (by qty & customer type)
```

### Suppliers
```
GET    /api/suppliers             - List supplier
POST   /api/suppliers             - Tambah supplier
PUT    /api/suppliers/{id}        - Update supplier
GET    /api/suppliers/{id}/products - Produk by supplier
```

### Reports
```
GET    /api/reports/sales         - Laporan penjualan
GET    /api/reports/inventory     - Laporan inventori
GET    /api/reports/receivables   - Laporan piutang
GET    /api/reports/profit        - Laporan laba rugi
GET    /api/reports/top-products  - Produk terlaris
GET    /api/reports/top-customers - Pelanggan terbaik
```

---

## 🎨 PENYESUAIAN FILAMENT ADMIN

### 1. Dashboard Widgets
```php
// app/Filament/Widgets/

✏️ StatsOverview.php - Total sales, pending payments, low stock
🆕 SalesChart.php - Grafik penjualan bulanan
🆕 TopProductsTable.php - Produk terlaris
🆕 PendingPaymentsWidget.php - Tempo jatuh tempo
🆕 LowStockAlert.php - Alert stok menipis
🆕 RecentOrdersWidget.php - Order terbaru
```

### 2. Product Management
```php
// app/Filament/Resources/ProductResource.php

Forms:
- Basic Info: nama, deskripsi, kategori
- Pricing: harga retail, grosir, kontraktor
- Stock: stok, minimal order, reorder level, lokasi
- Supplier: pilih supplier
- Units: satuan jual, konversi satuan
- Images: multiple images upload

Tables:
- Columns: SKU, nama, stok, harga retail, status
- Filters: kategori, supplier, low stock
- Actions: quick edit stock, view movements
```

### 3. Stock Movement Resource
```php
// app/Filament/Resources/StockMovementResource.php

Forms:
- Type: stok masuk/keluar/adjustment
- Product: pilih produk
- Quantity & Unit
- Reference: order/purchase/adjustment
- Notes

Tables:
- Date, product, type, quantity, reference
- Filters: type, date range, product
- Export to Excel
```

### 4. Order Management
```php
// app/Filament/Resources/OrderResource.php

Forms:
- Customer info + customer type
- Products dengan harga dinamis
- Payment method: cash/transfer/tempo
- Payment term: berapa hari tempo
- Delivery info
- Generate delivery note button

Tables:
- Order number, customer, total, status, payment status
- Filters: status, payment status, overdue
- Actions: print invoice, create delivery note, mark paid
```

### 5. Delivery Note Resource
```php
// app/Filament/Resources/DeliveryNoteResource.php

Forms:
- Delivery number (auto-generate)
- Order reference
- Driver & vehicle info
- Delivery date
- Status tracking

Actions:
- Print surat jalan
- Update status (in transit, delivered)
- Upload signature
```

### 6. Customer Credit Management
```php
// app/Filament/Resources/CustomerCreditResource.php

Forms:
- Customer selection
- Credit limit
- Current debt (auto-calculated)
- Available credit
- Payment history

Tables:
- Customer, limit, used, available, status
- Filters: customer type, overdue
- Actions: adjust limit, view history
```

### 7. Payment Term Resource
```php
// app/Filament/Resources/PaymentTermResource.php

Forms:
- Order reference
- Due date
- Amount
- Payment status

Tables:
- Order number, customer, due date, amount, status
- Filters: status, overdue, date range
- Bulk actions: send reminder, mark paid
```

### 8. Supplier Management
```php
// app/Filament/Resources/SupplierResource.php

Forms:
- Basic info: nama, company, kontak
- Address info
- Tax number (NPWP)
- Payment terms
- Products supplied

Tables:
- Supplier name, contact, payment terms
- Relations: products count
```

### 9. Reports
```php
// app/Filament/Pages/Reports.php

- Sales Report (daily/monthly/yearly)
- Inventory Report (stock value, movement)
- Receivables Report (aging analysis)
- Customer Report (top buyers, debt status)
- Profit & Loss Report
- Export all reports to PDF/Excel
```

---

## 🎨 MODIFIKASI UI/UX FRONTEND

### 1. Branding
```
✏️ Logo: Ganti dengan logo toko bangunan
✏️ Color scheme: 
   - Primary: #FF6B35 (Orange construction)
   - Secondary: #004E89 (Blue industrial)
   - Accent: #F7B801 (Safety yellow)
✏️ Typography: Font bold & tegas (Montserrat/Roboto)
✏️ Favicon: Icon toko bangunan
```

### 2. Homepage
```
Sections:
- Hero: Banner promo material bangunan
- Categories: Semen, Besi, Cat, Keramik, Pipa, Listrik
- Featured Products: Produk unggulan
- Why Choose Us: Harga grosir, tempo, pengiriman
- Testimonials: Review customer B2B
```

### 3. Product Catalog
```
- Grid view dengan info stok real-time
- Filter: kategori, harga, brand, ketersediaan
- Badge: "Harga Grosir", "Stok Terbatas", "Pre-Order"
- Quick view: modal dengan harga bertingkat
```

### 4. Product Detail Page
```
- Gallery: multiple images produk
- Info: nama, SKU, brand, kategori
- Price table:
  * Retail: Rp XXX (min 1 pcs)
  * Grosir: Rp XXX (min 10 pcs)
  * Kontraktor: Rp XXX (min 50 pcs)
- Stock indicator: "Stok: 150 pcs"
- Specifications table
- Related products
```

### 5. Shopping Cart
```
- Dynamic price based on quantity
- Show which price level applied
- Bulk discount calculator
- Delivery estimation
- Payment method selector:
  * Cash/Transfer (immediate)
  * Tempo 7/14/30 hari (require approval)
```

### 6. Checkout Process
```
Step 1: Shipping info
Step 2: Payment method & terms
Step 3: Review order
Step 4: Confirmation

For tempo payment:
- Show credit limit & available credit
- Require additional docs (optional)
- Admin approval notice
```

### 7. Customer Dashboard
```
Sections:
- Order history dengan status tracking
- Pending payments (tempo)
- Credit limit & debt status
- Download invoice & surat jalan
- Reorder quick button
```

### 8. Mobile Responsive
```
- Mobile-first approach
- Touch-friendly buttons
- Swipeable product gallery
- Bottom navigation
- WhatsApp quick order button
```

---

## 📅 RENCANA KERJA 6 MINGGU

### 🗓️ MINGGU 1: Setup & Database
**Target:** Database schema & models siap

**Hari 1-2: Setup Environment**
- [ ] Clone & setup project
- [ ] Install dependencies
- [ ] Database configuration
- [ ] Test existing features

**Hari 3-5: Database Migration**
- [ ] Create migration untuk tabel baru
- [ ] Modify existing tables
- [ ] Create seeders untuk data dummy
- [ ] Test migration up/down

**Hari 6-7: Models & Relationships**
- [ ] Buat semua model baru
- [ ] Setup relationships
- [ ] Implement fillable, casts, accessors
- [ ] Write model tests

**Deliverable:** Database & models ready

---

### 🗓️ MINGGU 2: Core Business Logic
**Target:** Business logic untuk stok & pricing

**Hari 1-3: Stock Management**
- [ ] StockMovement logic
- [ ] Stock in/out methods
- [ ] Real-time stock tracking
- [ ] Low stock alerts
- [ ] Stock adjustment

**Hari 4-5: Price Level System**
- [ ] PriceLevel model & logic
- [ ] Dynamic price calculator
- [ ] Quantity-based pricing
- [ ] Customer type pricing

**Hari 6-7: Order Enhancement**
- [ ] Modify Order model untuk tempo
- [ ] Payment term logic
- [ ] Credit limit validation
- [ ] Order approval workflow

**Deliverable:** Core business logic working

---

### 🗓️ MINGGU 3: Filament Admin Panel
**Target:** Admin panel lengkap & functional

**Hari 1-2: Product Management**
- [ ] Update ProductResource
- [ ] Price level forms
- [ ] Stock management integration
- [ ] Supplier assignment

**Hari 3-4: Order & Delivery**
- [ ] Update OrderResource
- [ ] DeliveryNoteResource
- [ ] PaymentTermResource
- [ ] Print templates

**Hari 5-6: Customer & Credit**
- [ ] Update UserResource (customer)
- [ ] CustomerCreditResource
- [ ] Credit limit management
- [ ] Debt tracking

**Hari 7: Suppliers & Reports**
- [ ] SupplierResource
- [ ] Basic reports
- [ ] Dashboard widgets

**Deliverable:** Full admin panel

---

### 🗓️ MINGGU 4: Frontend UI/UX
**Target:** User-facing website complete

**Hari 1-2: Rebranding**
- [ ] Update logo & colors
- [ ] Modify layouts
- [ ] New homepage design
- [ ] Category pages

**Hari 3-4: Product Pages**
- [ ] Product listing dengan filters
- [ ] Product detail dengan price levels
- [ ] Stock indicators
- [ ] Related products

**Hari 5-6: Cart & Checkout**
- [ ] Update Cart component
- [ ] Dynamic pricing display
- [ ] Payment term options
- [ ] Checkout flow

**Hari 7: Customer Dashboard**
- [ ] Order history
- [ ] Payment tracking
- [ ] Credit info
- [ ] Download documents

**Deliverable:** Frontend complete

---

### 🗓️ MINGGU 5: API & Integration
**Target:** API endpoints & integrations

**Hari 1-2: Stock APIs**
- [ ] Stock movement endpoints
- [ ] Stock reports
- [ ] Low stock alerts API

**Hari 3-4: Delivery & Payment APIs**
- [ ] Delivery note endpoints
- [ ] Payment term endpoints
- [ ] Print document APIs

**Hari 5-6: Reports & Analytics**
- [ ] Sales reports API
- [ ] Inventory reports API
- [ ] Receivables reports API
- [ ] Export to Excel/PDF

**Hari 7: Midtrans Integration**
- [ ] Configure Midtrans for tempo
- [ ] Payment webhook handling
- [ ] Payment notifications

**Deliverable:** All APIs working

---

### 🗓️ MINGGU 6: Testing & Deployment
**Target:** Production-ready application

**Hari 1-2: Unit Testing**
- [ ] Model tests
- [ ] Business logic tests
- [ ] API endpoint tests
- [ ] Fix bugs

**Hari 3-4: Integration Testing**
- [ ] End-to-end order flow
- [ ] Payment flow testing
- [ ] Stock movement testing
- [ ] Report generation

**Hari 5: User Acceptance Testing**
- [ ] Create test scenarios
- [ ] Test dengan real data
- [ ] Document bugs
- [ ] Fix critical issues

**Hari 6: Documentation**
- [ ] User manual
- [ ] Admin manual
- [ ] API documentation
- [ ] Deployment guide

**Hari 7: Deployment**
- [ ] Server setup
- [ ] Database migration
- [ ] Environment configuration
- [ ] Go live!

**Deliverable:** Production launch

---

## 🧪 INSTRUKSI TESTING

### 1. Unit Testing

#### Test Models
```bash
php artisan test --filter=StockMovementTest
php artisan test --filter=PriceLevelTest
php artisan test --filter=PaymentTermTest
php artisan test --filter=DeliveryNoteTest
```

**Test Cases:**
- [ ] StockMovement: in/out/adjustment calculations
- [ ] PriceLevel: correct price based on quantity & customer type
- [ ] PaymentTerm: due date calculation, overdue detection
- [ ] CustomerCredit: credit limit validation
- [ ] Order: tempo pembayaran logic

#### Test Business Logic
```php
// tests/Unit/PriceCalculatorTest.php
test('calculate correct price for retail customer')
test('calculate wholesale price for min quantity')
test('calculate contractor price')
test('apply customer-specific discount')

// tests/Unit/StockManagerTest.php
test('stock decreases after order')
test('stock increases on stock in')
test('alert on low stock')
test('prevent negative stock')
```

### 2. Feature Testing

#### Test Order Flow
```php
// tests/Feature/OrderFlowTest.php
test('create order with retail price')
test('create order with wholesale price')
test('create order with tempo payment')
test('validate credit limit')
test('generate delivery note')
test('track delivery status')
test('mark payment as paid')
```

#### Test Stock Management
```php
// tests/Feature/StockManagementTest.php
test('stock in transaction')
test('stock out transaction')
test('stock adjustment')
test('view stock movement history')
test('export stock report')
```

### 3. Integration Testing

#### Test Complete Purchase Flow
```
Scenario 1: Retail Purchase (Cash)
1. [ ] Browse products
2. [ ] Add to cart (retail price applied)
3. [ ] Checkout with cash payment
4. [ ] Order created
5. [ ] Stock decreased
6. [ ] Payment confirmed
7. [ ] Delivery note generated
8. [ ] Order completed

Scenario 2: Wholesale Purchase (Tempo 30 hari)
1. [ ] Login as grosir customer
2. [ ] Add 50 pcs (wholesale price applied)
3. [ ] Choose tempo 30 hari
4. [ ] Check credit limit
5. [ ] Order created (pending approval)
6. [ ] Admin approves order
7. [ ] Stock reserved
8. [ ] Delivery note created
9. [ ] Driver delivers
10. [ ] Customer signs delivery
11. [ ] Payment reminder at due date
12. [ ] Customer pays
13. [ ] Credit updated
14. [ ] Order completed
```

#### Test Admin Workflows
```
1. [ ] Create product dengan price levels
2. [ ] Stock in dari supplier
3. [ ] Approve order with tempo
4. [ ] Create delivery note
5. [ ] Track delivery
6. [ ] Generate invoice
7. [ ] Track payment
8. [ ] Generate reports
9. [ ] Export to Excel/PDF
10. [ ] Send payment reminder
```

### 4. Performance Testing

```bash
# Test dengan data besar
php artisan test:seed-large-data
php artisan test:performance

Test metrics:
- [ ] Product listing load time < 2s
- [ ] Cart calculation < 500ms
- [ ] Order creation < 1s
- [ ] Report generation < 5s
- [ ] API response < 1s
```

### 5. Security Testing

```
- [ ] Test unauthorized access
- [ ] Test credit limit bypass
- [ ] Test negative stock manipulation
- [ ] Test price manipulation
- [ ] Test SQL injection
- [ ] Test XSS vulnerabilities
- [ ] Test CSRF protection
```

### 6. Browser Testing

```
Browsers to test:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome (Android)
- [ ] Mobile Safari (iOS)

Test responsive design:
- [ ] Desktop (1920x1080)
- [ ] Laptop (1366x768)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x667)
```

### 7. User Acceptance Testing (UAT)

**Checklist untuk User:**

```
Sebagai Admin:
- [ ] Tambah produk baru dengan harga bertingkat
- [ ] Input stok masuk dari supplier
- [ ] Lihat alert stok menipis
- [ ] Approve order dengan tempo
- [ ] Buat surat jalan
- [ ] Track delivery
- [ ] Input pembayaran customer
- [ ] Generate laporan penjualan
- [ ] Generate laporan piutang
- [ ] Export laporan ke Excel

Sebagai Customer Retail:
- [ ] Browse produk
- [ ] Lihat harga retail
- [ ] Add to cart
- [ ] Checkout dengan pembayaran langsung
- [ ] Lihat status order
- [ ] Download invoice

Sebagai Customer Grosir:
- [ ] Login
- [ ] Lihat harga grosir
- [ ] Order dalam jumlah besar
- [ ] Pilih tempo 30 hari
- [ ] Lihat limit kredit
- [ ] Track order & delivery
- [ ] Lihat daftar piutang
- [ ] Download surat jalan
```

---

## 📊 CHECKLIST AKHIR SEBELUM LAUNCH

### Database & Backend
- [ ] All migrations run successfully
- [ ] Seeders create valid data
- [ ] All relationships working
- [ ] Indexes created for performance
- [ ] Backup strategy configured

### Features
- [ ] Stock management working
- [ ] Price levels calculating correctly
- [ ] Order flow complete
- [ ] Payment terms working
- [ ] Delivery notes generated
- [ ] Credit limit enforced
- [ ] Reports generating correctly

### Admin Panel
- [ ] All CRUD operations working
- [ ] Permissions configured
- [ ] Dashboard widgets showing data
- [ ] Exports working (Excel/PDF)
- [ ] Search & filters working

### Frontend
- [ ] All pages responsive
- [ ] Images optimized
- [ ] Forms validated
- [ ] Error messages clear
- [ ] Success notifications working
- [ ] Loading states implemented

### Security
- [ ] HTTPS enabled
- [ ] CSRF protection active
- [ ] XSS prevention
- [ ] SQL injection prevention
- [ ] Rate limiting configured
- [ ] Authentication secure
- [ ] Authorization working

### Performance
- [ ] Page load < 3s
- [ ] API response < 1s
- [ ] Images lazy loaded
- [ ] CSS/JS minified
- [ ] Cache configured
- [ ] Database queries optimized

### Documentation
- [ ] User manual complete
- [ ] Admin manual complete
- [ ] API documentation
- [ ] Installation guide
- [ ] Troubleshooting guide

### Deployment
- [ ] Production server configured
- [ ] Environment variables set
- [ ] Database migrated
- [ ] Storage linked
- [ ] Cron jobs configured
- [ ] Queue workers running
- [ ] Monitoring setup
- [ ] Backup automated

---

## 🎯 QUICK START COMMANDS

### Setup Project
```bash
# Clone & setup
cd /home/luffi/Documents/web/Gpx-Store
composer install
npm install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate:fresh --seed
php artisan storage:link

# Run development
composer run dev
# atau
npm run dev
```

### Create New Resources
```bash
# Models
php artisan make:model StockMovement -mfs
php artisan make:model DeliveryNote -mfs
php artisan make:model PaymentTerm -mfs
php artisan make:model CustomerCredit -mfs

# Filament Resources
php artisan make:filament-resource StockMovement --generate
php artisan make:filament-resource DeliveryNote --generate
php artisan make:filament-resource PaymentTerm --generate

# Controllers
php artisan make:controller Api/StockController --api
php artisan make:controller Api/DeliveryNoteController --api
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific tests
php artisan test --filter=OrderTest

# With coverage
php artisan test --coverage
```

---

## 📞 SUPPORT & RESOURCES

### Documentation
- Laravel 12: https://laravel.com/docs/12.x
- Filament 3: https://filamentphp.com/docs/3.x
- Livewire 3: https://livewire.laravel.com/docs/3.x
- Tailwind CSS: https://tailwindcss.com/docs

### Community
- Laravel Discord
- Filament Discord
- Stack Overflow

---

## ✅ KESIMPULAN

Project ini memiliki foundation yang solid dengan Laravel 12 dan Filament. Konversi ke toko bangunan fokus pada:

1. **Database enhancement** - Tambah tabel untuk stok, delivery, tempo
2. **Business logic** - Price levels, credit management, stock tracking
3. **Admin panel** - Filament resources untuk semua fitur
4. **Frontend rebrand** - UI/UX sesuai industri bangunan
5. **Testing menyeluruh** - Ensure reliability

Estimasi waktu 6 minggu dengan bekerja konsisten. Prioritaskan backend dulu, lalu frontend, terakhir polish & testing.

**Good luck! 🚀**
