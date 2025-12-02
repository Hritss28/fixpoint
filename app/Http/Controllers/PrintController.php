<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\DeliveryNote;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrintController extends Controller
{
    public function invoice(Order $order)
    {
        // Load necessary relationships
        $order->load([
            'customer', 
            'orderItems.product', 
            'paymentTerm'
        ]);
        
        // Check if user has permission to view this order
        if (!auth()->user()->hasRole('admin') && auth()->id() !== $order->customer_id) {
            abort(403, 'Unauthorized to view this invoice.');
        }
        
        return view('invoices.print', compact('order'));
    }
    
    public function deliveryNote(DeliveryNote $deliveryNote)
    {
        // Load necessary relationships
        $deliveryNote->load([
            'order.orderItems.product',
            'order.customer'
        ]);
        
        // Check if user has permission to view this delivery note
        if (!auth()->user()->hasRole('admin') && auth()->id() !== $deliveryNote->order->customer_id) {
            abort(403, 'Unauthorized to view this delivery note.');
        }
        
        return view('delivery-notes.print', compact('deliveryNote'));
    }
}
