<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #FF6B35;
            padding-bottom: 20px;
        }
        .company-info h1 {
            color: #FF6B35;
            margin: 0;
            font-size: 28px;
        }
        .company-info p {
            margin: 5px 0;
            color: #666;
        }
        .invoice-details {
            text-align: right;
        }
        .invoice-details h2 {
            color: #004E89;
            margin: 0;
            font-size: 24px;
        }
        .invoice-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .bill-to, .invoice-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .bill-to h3, .invoice-info h3 {
            color: #004E89;
            margin-top: 0;
            border-bottom: 2px solid #FF6B35;
            padding-bottom: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #004E89;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .items-table tr:hover {
            background: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-left: auto;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .total-row.grand-total {
            border-top: 2px solid #FF6B35;
            font-weight: bold;
            font-size: 18px;
            color: #004E89;
            margin-top: 10px;
            padding-top: 15px;
        }
        .payment-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .payment-info h3 {
            color: #004E89;
            margin-top: 0;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1edff; color: #0c5460; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="company-info">
            <h1>{{ config('app.name', 'Building Materials Store') }}</h1>
            <p>Jl. Raya Bangunan No. 123</p>
            <p>Jakarta 12345, Indonesia</p>
            <p>Phone: +62 21 1234 5678</p>
            <p>Email: info@buildingstore.com</p>
            <p>NPWP: 12.345.678.9-012.000</p>
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> {{ $order->order_number }}</p>
            <p><strong>Date:</strong> {{ $order->order_date->format('d M Y') }}</p>
            @if($order->paymentTerm)
                <p><strong>Due Date:</strong> {{ $order->paymentTerm->due_date->format('d M Y') }}</p>
            @endif
        </div>
    </div>

    <div class="invoice-meta">
        <div class="bill-to">
            <h3>Bill To:</h3>
            <p><strong>{{ $order->customer->name }}</strong></p>
            @if($order->customer->company_name)
                <p>{{ $order->customer->company_name }}</p>
            @endif
            <p>{{ $order->customer->email }}</p>
            <p>{{ $order->customer->phone }}</p>
            @if($order->delivery_address)
                <p><strong>Delivery Address:</strong></p>
                <p>{{ $order->delivery_address }}</p>
            @endif
        </div>
        
        <div class="invoice-info">
            <h3>Invoice Information:</h3>
            <p><strong>Order Status:</strong> 
                <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
            </p>
            <p><strong>Payment Status:</strong> 
                <span class="status-badge status-{{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span>
            </p>
            @if($order->paymentTerm)
                <p><strong>Payment Terms:</strong> {{ $order->paymentTerm->term_days ?? 30 }} days</p>
            @endif
            @if($order->project_name)
                <p><strong>Project:</strong> {{ $order->project_name }}</p>
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th>Unit</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product->name }}</strong>
                        @if($item->product->sku)
                            <br><small>SKU: {{ $item->product->sku }}</small>
                        @endif
                        @if($item->notes)
                            <br><small>{{ $item->notes }}</small>
                        @endif
                    </td>
                    <td>{{ $item->unit ?? $item->product->unit }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        @php
            $subtotal = $order->orderItems->sum('total_price');
            $deliveryFee = $order->delivery_fee ?? 0;
            $taxAmount = $order->tax_amount ?? 0;
            $grandTotal = $subtotal + $deliveryFee + $taxAmount;
        @endphp
        
        <div class="total-row">
            <span>Subtotal:</span>
            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
        </div>
        
        @if($deliveryFee > 0)
            <div class="total-row">
                <span>Delivery Fee:</span>
                <span>Rp {{ number_format($deliveryFee, 0, ',', '.') }}</span>
            </div>
        @endif
        
        @if($taxAmount > 0)
            <div class="total-row">
                <span>Tax (11%):</span>
                <span>Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
            </div>
        @endif
        
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
        </div>
    </div>

    @if($order->paymentTerm)
        <div class="payment-info">
            <h3>Payment Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <p><strong>Payment Method:</strong> {{ $order->paymentTerm->payment_method ? ucwords(str_replace('_', ' ', $order->paymentTerm->payment_method)) : 'Bank Transfer' }}</p>
                    <p><strong>Bank Account:</strong></p>
                    <p>BCA: 1234567890<br>Bank Mandiri: 0987654321<br>a/n Building Materials Store</p>
                </div>
                <div>
                    <p><strong>Total Amount:</strong> Rp {{ number_format($grandTotal, 0, ',', '.') }}</p>
                    <p><strong>Paid Amount:</strong> Rp {{ number_format($order->paymentTerm->paid_amount, 0, ',', '.') }}</p>
                    <p><strong>Remaining:</strong> Rp {{ number_format($grandTotal - $order->paymentTerm->paid_amount, 0, ',', '.') }}</p>
                    @if($order->paymentTerm->due_date->isPast() && $order->paymentTerm->status !== 'paid')
                        <p style="color: #dc3545;"><strong>⚠️ OVERDUE</strong></p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($order->notes)
        <div class="payment-info">
            <h3>Notes</h3>
            <p>{{ $order->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This invoice was generated on {{ now()->format('d M Y H:i') }}</p>
        <div class="no-print" style="margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #FF6B35; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Invoice</button>
        </div>
    </div>
</body>
</html>
