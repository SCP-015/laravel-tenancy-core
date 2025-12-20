@extends('emails.layouts.master')

@section('title', 'Test Email NusaHire')

@section('content')
    <h2>Test Email NusaHire</h2>
    <p>Ini adalah email test dari aplikasi NusaHire.</p>
    
    <div style="padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #f9f9f9; margin: 20px 0;">
        <p><strong>Pesan Test:</strong></p>
        <p>{{ $content }}</p>
    </div>
    
    <p>Email ini dikirim menggunakan command artisan <code>email:test</code>.</p>
    <p>Waktu pengiriman: {{ now()->format('d M Y H:i:s') }}</p>
@endsection
