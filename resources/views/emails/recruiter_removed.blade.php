@extends('emails.layouts.master')

@section('title', 'Akun Admin NusaHire')

@section('content')
    <p>Halo, {{ $receiverMail }}</p>
    <p>Anda telah <b>dihapus sebagai admin</b> untuk <b>{{ $tenantName }}</b> di NusaHire.</p>

    <p>Anda tidak lagi dapat mengakses fitur-fitur admin di NusaHire, termasuk:</p>
    <ul>
        <li>Mengelola proses rekrutmen</li>
        <li>Memproses kandidat</li>
    </ul>

    <p>Terima kasih atas partisipasi Anda sebelumnya.</p>
@endsection
