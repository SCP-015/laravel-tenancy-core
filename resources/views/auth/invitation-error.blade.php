<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Tidak Valid - NusaHire</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Icon Error -->
        <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-gray-900 mb-4">
            Undangan Tidak Valid
        </h1>

        <!-- Error Message -->
        <p class="text-gray-600 mb-6">
            {{ $message ?? 'Terjadi kesalahan dengan undangan Anda.' }}
        </p>

        <!-- Additional Info -->
        @if(isset($details))
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <h3 class="font-semibold text-gray-800 mb-2">Detail:</h3>
            <p class="text-sm text-gray-600">{{ $details }}</p>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="space-y-3">
            <button onclick="closePopupAndGoHome()" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
                <i class="fas fa-home mr-2"></i>
                Kembali ke Beranda
            </button>
            
            <a href="mailto:support@nusahire.com" 
               class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg transition duration-200 inline-block">
                <i class="fas fa-envelope mr-2"></i>
                Hubungi Support
            </a>
        </div>

        <script>
        function closePopupAndGoHome() {
            // Jika halaman dibuka di popup/window baru
            if (window.opener) {
                // Redirect parent window ke beranda
                window.opener.location.href = '{{ url('/') }}';
                // Tutup popup
                window.close();
            } else {
                // Jika bukan popup, redirect normal
                window.location.href = '{{ url('/') }}';
            }
        }
        </script>

        <!-- Footer -->
        <!-- <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-xs text-gray-500">
                © {{ date('Y') }} NusaHire. Semua hak dilindungi.
            </p>
        </div> -->
    </div>
</body>
</html>
