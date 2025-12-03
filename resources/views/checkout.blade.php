{{-- {{ dd($addresses) }} --}}
@extends('layouts.app')

@section('styles')
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
    .form-control {
        @apply w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500;
    }
    
    .form-label {
        @apply block text-sm font-medium text-gray-700 mb-1;
    }
    
    .section-title {
        @apply text-lg font-semibold text-gray-900 mb-4;
    }
    
    .address-card {
        @apply border rounded-lg p-4 cursor-pointer transition-all duration-200;
    }
    
    .address-card.selected {
        @apply border-blue-500 bg-blue-50;
    }
    
    .address-card:hover:not(.selected) {
        @apply border-gray-400 bg-gray-50;
    }
    
    .address-badge {
        @apply inline-block px-2 py-1 text-xs rounded-full font-medium;
    }
    
    .address-badge-primary {
        @apply bg-blue-100 text-blue-800;
    }
    
    .address-badge-secondary {
        @apply bg-gray-100 text-gray-800;
    }
    
    .shipping-method {
        @apply flex items-center p-3 border rounded-md cursor-pointer transition-all duration-200;
    }
    
    .shipping-method:hover {
        @apply bg-gray-50;
    }
    
    .shipping-method.selected {
        @apply border-blue-500 bg-blue-50;
    }
    
    .btn-primary {
        @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-md transition duration-200;
    }
    
    .btn-outline {
        @apply border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-md transition duration-200;
    }
    
    .payment-card {
        height: 32px;
        margin-right: 8px;
    }
    
    .animated-bg {
        @apply absolute inset-0 bg-gradient-to-b from-blue-50 to-white -z-10;
    }
    
    /* Custom animation for address selection */
    @keyframes pulse-border {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.5); }
        70% { box-shadow: 0 0 0 5px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
    
    .pulse-animation {
        animation: pulse-border 2s infinite;
    }
    
    /* Loading spinner */
    .spinner {
        @apply inline-block w-4 h-4 border-2 border-t-2 border-white rounded-full animate-spin;
        border-top-color: transparent;
    }
    
    /* Alert styles */
    .alert {
        @apply p-3 rounded-md text-sm mb-4;
    }
    
    .alert-success {
        @apply bg-green-100 text-green-700 border border-green-200;
    }
    
    .alert-danger {
        @apply bg-red-100 text-red-700 border border-red-200;
    }
</style>
@endsection

@section('content')
<!-- Breadcrumb with nice background -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="text-gray-500 hover:text-blue-600">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('cart') }}" class="ml-1 text-gray-500 md:ml-2 hover:text-blue-600">Cart</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-800 font-medium md:ml-2">Checkout</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>
</div>

<!-- Checkout Steps Progress Bar -->
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between max-w-3xl mx-auto">
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">1</div>
            <span class="mt-2 text-sm font-medium text-blue-600">Shipping</span>
        </div>
        <div class="flex-auto h-1 mx-2 bg-blue-200"></div>
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-semibold">2</div>
            <span class="mt-2 text-sm font-medium text-gray-600">Payment</span>
        </div>
        <div class="flex-auto h-1 mx-2 bg-gray-200"></div>
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-semibold">3</div>
            <span class="mt-2 text-sm font-medium text-gray-600">Confirmation</span>
        </div>
    </div>
</div>

<!-- Checkout Content -->
<div class="container mx-auto px-4 py-6 mb-16 relative">
    <!-- Background effect -->
    <div class="animated-bg"></div>

    <!-- Validation Errors Display -->
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Terjadi kesalahan!</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <form id="checkout-form" action="{{ route('checkout.process') }}" method="POST" class="relative">
        @csrf
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Customer Information and Shipping Details -->
            <div class="w-full lg:w-2/3">
                <!-- Customer Information - Modified to be Order Contact Info -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="section-title flex items-center">
                        <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </span>
                        Informasi Kontak Pemesanan
                    </h2>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 mt-2 rounded-r-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">Informasi ini akan digunakan untuk notifikasi status pesanan dan konfirmasi pembayaran.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="form-label">Email Konfirmasi Pesanan</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-control w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="phone" class="form-label">Nomor HP Aktif</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="form-control w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 08123456789" required>
                            @error('phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="hidden">
                            <input type="hidden" id="name" name="name" value="{{ old('name', $user->name ?? '') }}">
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Addresses -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="section-title flex items-center">
                        <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </span>
                        Shipping Address
                    </h2>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">Alamat pengiriman yang dipilih:</p>
                    </div>

                    <!-- Address Selection -->
                    <div class="space-y-3 mb-6">
                        <!-- Saved Addresses -->
                        @if(isset($addresses) && count($addresses) > 0)
                            @php
                                // Get default address, or first address if no default
                                $selectedAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();
                            @endphp
                            <div class="border rounded-lg p-4 bg-blue-50 border-blue-200">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900">{{ $selectedAddress->name ?? $selectedAddress->recipient_name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ $selectedAddress->phone }}</p>
                                    </div>
                                    <div>
                                        @if($selectedAddress->is_default)
                                            <span class="address-badge address-badge-primary">Default</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-sm text-gray-700">
                                    <p>{{ $selectedAddress->address_line1 }}</p>
                                    @if($selectedAddress->address_line2)
                                        <p>{{ $selectedAddress->address_line2 }}</p>
                                    @endif
                                    <p>{{ $selectedAddress->city }}, {{ $selectedAddress->province }} {{ $selectedAddress->postal_code }}</p>
                                    @if($selectedAddress->country)
                                        <p>{{ $selectedAddress->country }}</p>
                                    @endif
                                </div>
                                
                                <input type="hidden" name="address_id" value="{{ $selectedAddress->id }}">

                                <div class="mt-4 text-center">
                                    <a href="{{ route('profile.addresses') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Ubah Alamat
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="p-6 text-center border rounded-lg bg-yellow-50 border-yellow-200">
                                <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Anda belum memiliki alamat tersimpan.</h3>
                                <p class="mt-1 text-gray-500 mb-4">Silahkan tambahkan alamat terlebih dahulu untuk melanjutkan.</p>
                                <a href="{{ route('addresses.create') }}" class="btn-primary inline-block">
                                    Tambahkan Alamat Baru
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Shipping Method -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="section-title flex items-center">
                        <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </span>
                        Shipping Method
                    </h2>
                    
                    <div class="space-y-3 mt-4">
                        <!-- Info Pengiriman Lokal -->
                        <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg mb-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-orange-800">Pengiriman Lokal</h4>
                                    <p class="text-sm text-orange-700 mt-1">Pengiriman hanya tersedia untuk area dalam kota dan sekitarnya menggunakan kurir toko.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pilihan Metode Pengiriman -->
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Pilih Metode Pengiriman</h3>
                        
                        <div id="shipping-options" class="space-y-3">
                            <!-- Kurir Toko - Same Day -->
                            <div class="shipping-method flex items-start p-4 border-2 border-orange-500 rounded-lg bg-orange-50 cursor-pointer" onclick="selectShippingMethod(this, 'kurir_toko_sameday')">
                                <input type="radio" name="shipping_method" value="kurir_toko_sameday" checked
                                    data-cost="15000" data-courier="KURIR TOKO"
                                    class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 mt-1">
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="text-sm font-semibold text-gray-900">Kurir Toko - Same Day</span>
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Rekomendasi
                                            </span>
                                        </div>
                                        <span class="text-sm font-bold text-orange-600">Rp 15.000</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Pengiriman hari ini (order sebelum jam 14:00)</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Estimasi tiba: 2-4 jam
                                    </p>
                                </div>
                            </div>

                            <!-- Kurir Toko - Regular -->
                            <div class="shipping-method flex items-start p-4 border rounded-lg hover:border-orange-300 hover:bg-orange-50 cursor-pointer transition-all" onclick="selectShippingMethod(this, 'kurir_toko_regular')">
                                <input type="radio" name="shipping_method" value="kurir_toko_regular"
                                    data-cost="10000" data-courier="KURIR TOKO"
                                    class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 mt-1">
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-semibold text-gray-900">Kurir Toko - Regular</span>
                                        <span class="text-sm font-bold text-orange-600">Rp 10.000</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Pengiriman 1-2 hari kerja</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Estimasi tiba: 1-2 hari
                                    </p>
                                </div>
                            </div>

                            <!-- Ambil di Toko -->
                            <div class="shipping-method flex items-start p-4 border rounded-lg hover:border-orange-300 hover:bg-orange-50 cursor-pointer transition-all" onclick="selectShippingMethod(this, 'pickup')">
                                <input type="radio" name="shipping_method" value="pickup"
                                    data-cost="0" data-courier="PICKUP"
                                    class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 mt-1">
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-semibold text-gray-900">Ambil di Toko</span>
                                        <span class="text-sm font-bold text-green-600">GRATIS</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Ambil pesanan langsung di toko kami</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Jl. Raya Utama No.123, Jakarta Selatan
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Area Pengiriman -->
                        <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-600">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-700">Area Jangkauan Pengiriman:</p>
                                    <p class="mt-1">Jakarta Selatan, Jakarta Pusat, Jakarta Timur, Depok, Tangerang Selatan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Notes -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="section-title flex items-center">
                        <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        Order Notes
                    </h2>
                    
                    <div class="mt-6 flex">
                        <label for="notes" class="form-label w-[40%]">Order Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" class="form-control w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Special instructions for delivery or order">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="w-full lg:w-1/3">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-6">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>
                    </div>
                    
                    <div class="px-6 py-4">
                        <!-- Order Items -->
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Items in Your Order ({{ isset($cartItems) ? count($cartItems) : 0 }})</h3>
                            
                            <div class="max-h-60 overflow-y-auto pr-2" style="scrollbar-width: thin;">
                                @if(isset($cartItems))
                                    @foreach($cartItems as $item)
                                        <div class="flex items-center py-3 {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                                            <div class="flex-shrink-0 w-16 h-16 border border-gray-200 rounded overflow-hidden bg-gray-100 flex items-center justify-center">
                                                @if($item->product->image)
                                                    <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="w-full h-full items-center justify-center text-gray-400 hidden">
                                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h4 class="text-sm font-medium text-gray-900 line-clamp-1">{{ $item->product->name }}</h4>
                                                <p class="text-xs text-gray-500">Qty: {{ $item->quantity }}</p>
                                            </div>
                                            <div class="ml-4 text-right">
                                                @if($item->product->discount_price && $item->product->discount_price < $item->product->price)
                                                    <p class="text-sm font-medium text-blue-600">Rp {{ number_format($item->product->discount_price * $item->quantity, 0, ',', '.') }}</p>
                                                    <p class="text-xs text-gray-400 line-through">Rp {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}</p>
                                                @else
                                                    <p class="text-sm font-medium text-blue-600">Rp {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="py-3 text-center text-gray-500">
                                        No items in cart
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="border-t border-gray-200 pt-4 pb-2 space-y-2">
                            <div class="flex justify-between py-1">
                                <span class="text-sm text-gray-600">Subtotal</span>
                                <span class="text-sm font-medium" id="subtotal-amount">Rp {{ isset($subtotal) ? number_format($subtotal, 0, ',', '.') : '0' }}</span>
                            </div>
                            
                            <div class="flex justify-between py-1">
                                <span class="text-sm text-gray-600">Tax (11%)</span>
                                <span class="text-sm font-medium" id="tax-amount">Rp {{ number_format($tax, 0, ',', '.') }}</span>
                            </div>
                            
                            <!-- Shipping row will be added here dynamically -->

                            <div id="discount-row" class="flex justify-between py-1 {{ $discount > 0 ? 'block' : 'hidden' }}">
                                <span class="text-sm text-gray-600">Discount</span>
                                <span class="text-sm font-medium text-red-600" id="discount-amount">- Rp {{ number_format($discount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        
                        <!-- Only one Total row -->
                        <div class="flex justify-between py-3 border-t border-gray-200 mt-2">
                            <span class="text-base font-semibold text-gray-900">Total</span>
                            <span class="text-lg font-bold text-blue-600" id="order-total">Rp {{ number_format($subtotal + $tax - $discount, 0, ',', '.') }}</span>
                        </div>
                        
                        <!-- Coupon Code -->
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <label for="coupon_code" class="form-label">Coupon Code</label>
                            <div class="flex">
                                <input type="text" id="coupon_code" name="coupon_code" 
                                    placeholder="Enter coupon code" 
                                    class="flex-1 form-control rounded-r-none {{ isset($appliedPromo) ? 'bg-green-50 border-green-500' : '' }} w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    value="{{ $appliedPromo['code'] ?? '' }}"
                                    {{ isset($appliedPromo) ? 'readonly' : '' }}>
                                
                                @if(isset($appliedPromo))
                                    <button type="button" id="remove-coupon" 
                                        class="btn-outline rounded-l-none text-red-600 hover:bg-red-50 border-l-0 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Remove
                                    </button>
                                @else
                                    <button type="button" id="apply-coupon" 
                                        class="btn-outline rounded-l-none text-blue-600 hover:bg-blue-50 border-l-0">
                                        Apply
                                    </button>
                                @endif
                            </div>
                            
                            <div id="promo-message" class="mt-2 text-sm hidden"></div>
                        </div>
                        
                        <!-- Payment Information -->
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Payment Information</h3>
                            <p class="text-xs text-gray-500 mb-4">After clicking "Place Order", you will be redirected to our secure payment gateway to complete your payment.</p>
                        </div>
                        
                        <button type="submit" id="place-order-button" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition mt-4">
                            <span>Place Order</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                        <p class="text-xs text-gray-500 mt-4">
                            By placing your order, you agree to our <a href="#" class="text-blue-600 hover:underline">Terms and Conditions</a> and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Make shipping method functions globally accessible
function selectShippingMethod(element, method) {
    const shippingMethods = document.querySelectorAll('.shipping-method');
    shippingMethods.forEach(method => {
        method.classList.remove('selected');
    });
    
    element.classList.add('selected');
    
    const radio = element.querySelector('input[type="radio"]');
    if (radio) {
        radio.checked = true;
        
        // Get shipping cost and courier from data attribute
        const shippingCost = parseInt(radio.getAttribute('data-cost') || 0);
        const courier = radio.getAttribute('data-courier');
        
        // Update shipping cost in order summary
        updateOrderSummary(shippingCost);
        
        // Add hidden input fields for the form submission
        let shippingInput = document.querySelector('input[name="selected_shipping_cost"]');
        let courierInput = document.querySelector('input[name="selected_courier"]');
        let serviceInput = document.querySelector('input[name="selected_service"]');
        
        if (!shippingInput) {
            shippingInput = document.createElement('input');
            shippingInput.type = 'hidden';
            shippingInput.name = 'selected_shipping_cost';
            document.getElementById('checkout-form').appendChild(shippingInput);
        }
        
        if (!courierInput) {
            courierInput = document.createElement('input');
            courierInput.type = 'hidden';
            courierInput.name = 'selected_courier';
            document.getElementById('checkout-form').appendChild(courierInput);
        }
        
        if (!serviceInput) {
            serviceInput = document.createElement('input');
            serviceInput.type = 'hidden';
            serviceInput.name = 'selected_service';
            document.getElementById('checkout-form').appendChild(serviceInput);
        }
        
        shippingInput.value = shippingCost;
        courierInput.value = courier;
        serviceInput.value = method;
        
        // Log the shipping cost change for debugging
        console.log('Shipping cost updated:', {
            cost: shippingCost,
            courier: courier,
            service: method
        });
    }
}

// Helper function to update order summary (making globally accessible)
function updateOrderSummary(shippingCost) {
    let shippingRow = document.getElementById('shipping-row');
    const orderTotals = document.querySelector('.border-t.border-gray-200.pt-4.pb-2.space-y-2');
    
    if (!shippingRow) {
        // Create shipping row if it doesn't exist
        shippingRow = document.createElement('div');
        shippingRow.id = 'shipping-row';
        shippingRow.className = 'flex justify-between py-1';
        shippingRow.innerHTML = `
            <span class="text-sm text-gray-600">Shipping</span>
            <span class="text-sm font-medium" id="shipping-cost">Rp ${formatNumber(shippingCost)}</span>
        `;
        
        // Insert before discount row if it exists, otherwise append to the container
        const discountRow = document.getElementById('discount-row');
        if (discountRow && orderTotals) {
            discountRow.insertAdjacentElement('beforebegin', shippingRow);
        } else if (orderTotals) {
            orderTotals.appendChild(shippingRow);
        }
    } else {
        // Update existing shipping cost
        const shippingCostEl = document.getElementById('shipping-cost');
        if (shippingCostEl) {
            shippingCostEl.textContent = `Rp ${formatNumber(shippingCost)}`;
        }
    }
    
    // Recalculate total with the new shipping cost
    recalculateTotal();
}

// Utilities needed by our global functions
function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function parseCurrency(currencyStr) {
    if (!currencyStr) return 0;
    return parseInt(currencyStr.replace(/[^\d]/g, '')) || 0;
}

function recalculateTotal() {
    const subtotalElement = document.getElementById('subtotal-amount');
    const taxElement = document.getElementById('tax-amount');
    const shippingElement = document.getElementById('shipping-cost');
    const discountElement = document.getElementById('discount-amount');
    const totalElement = document.getElementById('order-total');
    
    if (!subtotalElement || !totalElement) return;
    
    // Parse all currency values
    let subtotal = parseCurrency(subtotalElement.textContent);
    let tax = taxElement ? parseCurrency(taxElement.textContent) : 0;
    let shipping = 0; // Default to 0
    let discount = 0;
    
    // Only add shipping if the element exists (meaning shipping was selected)
    if (shippingElement) {
        shipping = parseCurrency(shippingElement.textContent);
        console.log('Current shipping cost:', shipping);
    }
    
    // Only subtract discount if it exists
    if (discountElement && window.getComputedStyle(discountElement.parentElement).display !== 'none') {
        discount = parseCurrency(discountElement.textContent);
    }
    
    // Calculate total
    const total = subtotal + tax + shipping - discount;
    console.log('Calculated total:', { subtotal, tax, shipping, discount, total });
    
    // Update the display
    totalElement.textContent = `Rp ${formatNumber(total)}`;
    
    // CRITICAL FIX: We need to ensure these exact values are used for payment
    // Create special form elements for payment gateway
    // Use direct value assignment to avoid any formatting/parsing issues
    
    // Create form fields for Midtrans/payment gateway specifically
    updateOrCreateHiddenInput('payment_total', total);
    updateOrCreateHiddenInput('payment_subtotal', subtotal); 
    updateOrCreateHiddenInput('payment_tax', tax);
    updateOrCreateHiddenInput('payment_shipping', shipping);
    updateOrCreateHiddenInput('payment_discount', discount);
    
    // Set a flag to use these exact values in backend without recalculation
    updateOrCreateHiddenInput('bypass_calculation', 'true');
    
    // Extra logging to trace the values being submitted
    console.log('PAYMENT VALUES (Sending to gateway):', {
        payment_total: total,
        payment_subtotal: subtotal,
        payment_tax: tax,
        payment_shipping: shipping,
        payment_discount: discount
    });
}

// Helper function to create or update hidden inputs
function updateOrCreateHiddenInput(name, value) {
    let input = document.querySelector(`input[name="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        document.getElementById('checkout-form').appendChild(input);
    }
    input.value = value;
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Checkout page loaded');
    
    // Initialize AOS
    try {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    } catch(e) {
        console.error('AOS initialization error:', e);
    }

    // Check if user has address
    const hasAddress = {{ isset($addresses) && count($addresses) > 0 ? 'true' : 'false' }};
    
    // Initialize shipping cost with first selected option (Same Day - Rp 15.000)
    const initialShippingMethod = document.querySelector('input[name="shipping_method"]:checked');
    if (initialShippingMethod) {
        const initialCost = parseInt(initialShippingMethod.getAttribute('data-cost') || 0);
        updateOrderSummary(initialCost);
        
        // Set hidden inputs for the initial shipping
        updateOrCreateHiddenInput('selected_shipping_cost', initialCost);
        updateOrCreateHiddenInput('selected_courier', initialShippingMethod.getAttribute('data-courier'));
        updateOrCreateHiddenInput('selected_service', initialShippingMethod.value);
    }
    
    // Calculate initial total
    recalculateTotal();
    
    // Form submission handler
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const button = document.getElementById('place-order-button');
            
            // Check if shipping method is selected
            const shippingMethodSelected = document.querySelector('input[name="shipping_method"]:checked');
            if (!shippingMethodSelected) {
                e.preventDefault();
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Metode Pengiriman Diperlukan',
                    text: 'Silakan pilih metode pengiriman sebelum melanjutkan ke pembayaran',
                    confirmButtonText: 'OK'
                });
                
                return false;
            }
            
            // Ensure all total amounts are calculated and added to form
            recalculateTotal();
            
            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="spinner mr-2"></span>Processing...';
                button.classList.add('opacity-75', 'cursor-not-allowed');
            }
            
            // If no address is selected, prevent form submission
            if (!hasAddress) {
                e.preventDefault();
                button.disabled = false;
                button.innerHTML = '<span>Place Order</span><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>';
                button.classList.remove('opacity-75', 'cursor-not-allowed');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Alamat Pengiriman Diperlukan',
                    text: 'Anda harus menambahkan alamat pengiriman terlebih dahulu',
                    confirmButtonText: 'Tambah Alamat',
                    showCancelButton: true,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('addresses.create') }}";
                    }
                });
            }
        });
    }

    // Make function to select shipping method globally accessible
    function selectShippingMethod(element, method) {
        const shippingMethods = document.querySelectorAll('.shipping-method');
        shippingMethods.forEach(method => {
            method.classList.remove('selected');
        });
        
        element.classList.add('selected');
        
        const radio = element.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            
            // Get shipping cost and courier from data attribute
            const shippingCost = parseInt(radio.getAttribute('data-cost') || 0);
            const courier = radio.getAttribute('data-courier');
            
            // Update shipping cost in order summary
            updateOrderSummary(shippingCost);
            
            // Add hidden input fields for the form submission
            let shippingInput = document.querySelector('input[name="selected_shipping_cost"]');
            let courierInput = document.querySelector('input[name="selected_courier"]');
            let serviceInput = document.querySelector('input[name="selected_service"]');
            
            if (!shippingInput) {
                shippingInput = document.createElement('input');
                shippingInput.type = 'hidden';
                shippingInput.name = 'selected_shipping_cost';
                document.getElementById('checkout-form').appendChild(shippingInput);
            }
            
            if (!courierInput) {
                courierInput = document.createElement('input');
                courierInput.type = 'hidden';
                courierInput.name = 'selected_courier';
                document.getElementById('checkout-form').appendChild(courierInput);
            }
            
            if (!serviceInput) {
                serviceInput = document.createElement('input');
                serviceInput.type = 'hidden';
                serviceInput.name = 'selected_service';
                document.getElementById('checkout-form').appendChild(serviceInput);
            }
            
            shippingInput.value = shippingCost;
            courierInput.value = courier;
            serviceInput.value = method;
        }
    }

    // Make updateOrderSummary function globally accessible too
    function updateOrderSummary(shippingCost) {
        let shippingRow = document.getElementById('shipping-row');
        const orderTotals = document.querySelector('.border-t.border-gray-200.pt-4.pb-2.space-y-2');
        
        if (!shippingRow) {
            // Create shipping row if it doesn't exist
            shippingRow = document.createElement('div');
            shippingRow.id = 'shipping-row';
            shippingRow.className = 'flex justify-between py-1';
            shippingRow.innerHTML = `
                <span class="text-sm text-gray-600">Shipping</span>
                <span class="text-sm font-medium" id="shipping-cost">Rp ${formatNumber(shippingCost)}</span>
            `;
            
            // Insert before discount row if it exists, otherwise append to the container
            const discountRow = document.getElementById('discount-row');
            if (discountRow && orderTotals) {
                discountRow.insertAdjacentElement('beforebegin', shippingRow);
            } else if (orderTotals) {
                orderTotals.appendChild(shippingRow);
            }
        } else {
            // Update existing shipping cost
            document.getElementById('shipping-cost').textContent = `Rp ${formatNumber(shippingCost)}`;
        }
        
        // Recalculate total with the new shipping cost
        recalculateTotal();
    }

    // Utility function to properly parse Indonesian currency format
    function parseCurrency(currencyStr) {
        if (!currencyStr) return 0;
        return parseInt(currencyStr.replace(/[^\d]/g, '')) || 0;
    }

    // Global recalculateTotal function
    function recalculateTotal() {
        const subtotalElement = document.getElementById('subtotal-amount');
        const taxElement = document.getElementById('tax-amount');
        const shippingElement = document.getElementById('shipping-cost');
        const discountElement = document.getElementById('discount-amount');
        const totalElement = document.getElementById('order-total');
        
        if (!subtotalElement || !totalElement) return;
        
        // Parse all currency values
        let subtotal = parseCurrency(subtotalElement.textContent);
        let tax = taxElement ? parseCurrency(taxElement.textContent) : 0;
        let shipping = 0; // Default to 0
        let discount = 0;
        
        // Only add shipping if the element exists (meaning shipping was selected)
        if (shippingElement) {
            shipping = parseCurrency(shippingElement.textContent);
            console.log('Current shipping cost:', shipping);
        }
        
        // Only subtract discount if it exists
        if (discountElement && window.getComputedStyle(discountElement.parentElement).display !== 'none') {
            discount = parseCurrency(discountElement.textContent);
        }
        
        // Calculate total
        const total = subtotal + tax + shipping - discount;
        console.log('Calculated total:', { subtotal, tax, shipping, discount, total });
        
        // Update the display
        totalElement.textContent = `Rp ${formatNumber(total)}`;
        
        // CRITICAL FIX: We need to ensure these exact values are used for payment
        // Create special form elements for payment gateway
        // Use direct value assignment to avoid any formatting/parsing issues
        
        // Create form fields for Midtrans/payment gateway specifically
        updateOrCreateHiddenInput('payment_total', total);
        updateOrCreateHiddenInput('payment_subtotal', subtotal); 
        updateOrCreateHiddenInput('payment_tax', tax);
        updateOrCreateHiddenInput('payment_shipping', shipping);
        updateOrCreateHiddenInput('payment_discount', discount);
        
        // Set a flag to use these exact values in backend without recalculation
        updateOrCreateHiddenInput('bypass_calculation', 'true');
        
        // Extra logging to trace the values being submitted
        console.log('PAYMENT VALUES (Sending to gateway):', {
            payment_total: total,
            payment_subtotal: subtotal,
            payment_tax: tax,
            payment_shipping: shipping,
            payment_discount: discount
        });
    }

    // Ketika shipping method dipilih (untuk styling tambahan)
    const shippingMethods = document.querySelectorAll('.shipping-method');
    shippingMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class and border styling from all methods
            shippingMethods.forEach(m => {
                m.classList.remove('border-orange-500', 'bg-orange-50', 'border-2');
                m.classList.add('border');
            });
            
            // Add selected styling to clicked method
            this.classList.remove('border');
            this.classList.add('border-2', 'border-orange-500', 'bg-orange-50');
        });
    });

    // Apply Coupon Code - Fixed version
    const applyCouponButton = document.getElementById('apply-coupon');
    const removeCouponButton = document.getElementById('remove-coupon');
    const couponCodeInput = document.getElementById('coupon_code');
    const promoMessage = document.getElementById('promo-message');
    const discountRow = document.getElementById('discount-row');
    const discountAmount = document.getElementById('discount-amount');
    const orderTotal = document.getElementById('order-total');

    if (applyCouponButton) {
        applyCouponButton.addEventListener('click', function() {
            const code = couponCodeInput.value.trim();
            if (!code) {
                showPromoMessage('Please enter a coupon code', 'danger');
                return;
            }
            
            // Show loading state
            applyCouponButton.disabled = true;
            applyCouponButton.innerHTML = '<span class="spinner mr-2"></span> Applying...';
            
            // Get subtotal from the page
            const subtotalElement = document.querySelector('#subtotal-amount');
            const subtotal = parseCurrency(subtotalElement.textContent);
            
            // Use URLSearchParams for proper form data encoding
            const params = new URLSearchParams();
            params.append('coupon_code', code);
            params.append('subtotal', subtotal);
            
            // Send AJAX request to apply coupon using axios
            axios.post('{{ route("coupon.apply") }}', params)
                .then(function(response) {
                    const data = response.data;
                    if (data.success) {
                        // Reload the page to ensure proper state update
                        window.location.reload();
                    } else {
                        // Error
                        showPromoMessage(data.message || 'Failed to apply coupon code', 'danger');
                        applyCouponButton.disabled = false;
                        applyCouponButton.innerHTML = 'Apply';
                    }
                })
                .catch(function(error) {
                    console.error('Coupon application error:', error);
                    showPromoMessage('An error occurred while applying the coupon', 'danger');
                    applyCouponButton.disabled = false;
                    applyCouponButton.innerHTML = 'Apply';
                });
        });
    }

    // Fix the remove coupon functionality as well
    function setupRemoveCouponEvent() {
        const removeCoupon = document.getElementById('remove-coupon');
        if (removeCoupon) {
            removeCoupon.addEventListener('click', function() {
                // Show loading state
                removeCoupon.disabled = true;
                removeCoupon.innerHTML = '<span class="spinner mr-2"></span> Removing...';
                
                // Send AJAX request to remove coupon using axios
                axios.post('{{ route("coupon.remove") }}')
                    .then(function(response) {
                        const data = response.data;
                        if (data.success) {
                            // Reload the page to ensure proper state update
                            window.location.reload();
                        } else {
                            removeCoupon.disabled = false;
                            removeCoupon.innerHTML = `
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Remove
                            `;
                            showPromoMessage(data.message || 'Failed to remove promo code', 'danger');
                        }
                    })
                    .catch(function(error) {
                        console.error('Coupon removal error:', error);
                        removeCoupon.disabled = false;
                        removeCoupon.innerHTML = `
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Remove
                        `;
                        showPromoMessage('An error occurred while removing the coupon', 'danger');
                    });
            });
        }
    }

    // If remove button exists on page load, set up its event listener
    if (removeCouponButton) {
        setupRemoveCouponEvent();
    }

    // Helper function to show promo message
    function showPromoMessage(message, type) {
        promoMessage.textContent = message;
        promoMessage.className = 'mt-2 text-sm';
        promoMessage.classList.remove('hidden', 'text-green-600', 'text-red-600');
        
        if (type === 'success') {
            promoMessage.classList.add('text-green-600');
        } else if (type === 'danger') {
            promoMessage.classList.add('text-red-600');
        }
        
        promoMessage.classList.remove('hidden');
    }
});
</script>
@endsection