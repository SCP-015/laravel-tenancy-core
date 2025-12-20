@extends('emails.layouts.master')

@section('title', 'Undangan Admin NusaHire')

@section('content')
    <p>Halo, {{ $receiverMail }}</p>
    <p>Anda diundang untuk bergabung sebagai admin <b>{{ $tenantName }}</b> di NusaHire.</p>
    <p>Dengan NusaHire, Anda dapat:</p>
    <ul>
        <li>Mengelola proses rekrutmen dengan lebih mudah dan terstruktur</li>
        <li>Memproses kandidat secara efisien</li>
        <li>Berkontribusi langsung dalam membangun tim terbaik</li>
    </ul>

    <p>Silahkan isi kolom registrasi dengan:</p>
    <p>Nama Perusahaan: <b>{{ $tenantName }}</b></p>
    <p>Code Company: <b>{{ $tenantCode }}</b></p>

    <p>Atau klik tautan berikut untuk bergabung:<br>
        <u><a href="{{ $inviteUrl }}">{{ $inviteUrl }}</a></u>
    </p>
    <p>Terima kasih atas partisipasi Anda.</p>
@endsection
