<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Note #{{ $deliveryNote->delivery_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .delivery-header {
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
        .delivery-details {
            text-align: right;
        }
        .delivery-details h2 {
            color: #004E89;
            margin: 0;
            font-size: 24px;
        }
        .delivery-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .deliver-to, .delivery-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .deliver-to h3, .delivery-info h3 {
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
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-in_transit { background: #fff3cd; color: #856404; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-returned { background: #f8d7da; color: #721c24; }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #ddd;
        }
        .signature-box {
            text-align: center;
            padding: 20px;
            border: 2px dashed #ccc;
            min-height: 100px;
        }
        .signature-box h4 {
            margin: 0 0 10px 0;
            color: #004E89;
        }
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
    <div class="delivery-header">
        <div class="company-info">
            <h1>{{ config('app.name', 'Building Materials Store') }}</h1>
            <p>Jl. Raya Bangunan No. 123</p>
            <p>Jakarta 12345, Indonesia</p>
            <p>Phone: +62 21 1234 5678</p>
            <p>Email: info@buildingstore.com</p>
        </div>
        <div class="delivery-details">
            <h2>DELIVERY NOTE</h2>
            <p><strong>Delivery #:</strong> {{ $deliveryNote->delivery_number }}</p>
            <p><strong>Order #:</strong> {{ $deliveryNote->order->order_number }}</p>
            <p><strong>Date:</strong> {{ $deliveryNote->delivery_date->format('d M Y') }}</p>
            <p><strong>Status:</strong> 
                <span class="status-badge status-{{ $deliveryNote->status }}">
                    {{ ucfirst(str_replace('_', ' ', $deliveryNote->status)) }}
                </span>
            </p>
        </div>
    </div>

    <div class="delivery-meta">
        <div class="deliver-to">
            <h3>Deliver To:</h3>
            <p><strong>{{ $deliveryNote->customer_name }}</strong></p>
            @if($deliveryNote->contact_phone)
                <p>Phone: {{ $deliveryNote->contact_phone }}</p>
            @endif
            <p><strong>Delivery Address:</strong></p>
            <p>{{ $deliveryNote->delivery_address }}</p>
        </div>
        
        <div class="delivery-info">
            <h3>Delivery Information:</h3>
            <p><strong>Scheduled:</strong> {{ $deliveryNote->delivery_date->format('d M Y H:i') }}</p>
            @if($deliveryNote->delivered_at)
                <p><strong>Delivered:</strong> {{ $deliveryNote->delivered_at->format('d M Y H:i') }}</p>
            @endif
            <p><strong>Driver:</strong> {{ $deliveryNote->driver_name }}</p>
            @if($deliveryNote->driver_phone)
                <p><strong>Driver Phone:</strong> {{ $deliveryNote->driver_phone }}</p>
            @endif
            @if($deliveryNote->vehicle_number)
                <p><strong>Vehicle:</strong> {{ $deliveryNote->vehicle_number }}</p>
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th>Unit</th>
                <th class="text-right">Quantity</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryNote->order->orderItems as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product->name }}</strong>
                        @if($item->product->sku)
                            <br><small>SKU: {{ $item->product->sku }}</small>
                        @endif
                    </td>
                    <td>{{ $item->unit ?? $item->product->unit }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td>{{ $item->notes ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($deliveryNote->delivery_notes)
        <div class="deliver-to" style="margin-bottom: 20px;">
            <h3>Delivery Instructions:</h3>
            <p>{{ $deliveryNote->delivery_notes }}</p>
        </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <h4>Driver Signature</h4>
            <p style="margin-top: 60px;">{{ $deliveryNote->driver_name }}</p>
            <p>Date: ___________</p>
        </div>
        <div class="signature-box">
            <h4>Recipient Signature</h4>
            @if($deliveryNote->recipient_name)
                <p style="margin-top: 60px;">{{ $deliveryNote->recipient_name }}</p>
                @if($deliveryNote->delivered_at)
                    <p>Date: {{ $deliveryNote->delivered_at->format('d M Y') }}</p>
                @endif
            @else
                <p style="margin-top: 60px;">_____________________</p>
                <p>Name: _______________</p>
                <p>Date: ___________</p>
            @endif
        </div>
    </div>

    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <h4 style="color: #004E89; margin: 0 0 10px 0;">Important Notes:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Please check all items upon delivery</li>
            <li>Report any damage or missing items immediately</li>
            <li>Keep this delivery note for your records</li>
            <li>For questions, contact us at +62 21 1234 5678</li>
        </ul>
    </div>

    <div class="footer">
        <p>This delivery note was generated on {{ now()->format('d M Y H:i') }}</p>
        <div class="no-print" style="margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #FF6B35; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Delivery Note</button>
        </div>
    </div>
</body>
</html>
