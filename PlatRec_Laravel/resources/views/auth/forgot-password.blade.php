<!DOCTYPE html>
<html>
<head>
    <title>Lupa Password - Deteksi Plat Nomor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <!-- HEADER (sama persis dengan halaman deteksi) -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6">
        <a href="{{ route('login') }}" class="text-blue-300 hover:text-white mr-4">← Back to Login</a>
        <h1 class="text-xl">Lupa Password - Deteksi Plat Nomor</h1>
    </header>

    <!-- MAIN CONTENT - CENTERED FORGOT PASSWORD CARD -->
    <div class="max-w-md mx-auto mt-12 p-6">
        
        <!-- FORGOT PASSWORD CARD (mirip dengan card camera feed) -->
        <div class="bg-white shadow rounded-lg p-8">
            <h3 class="text-2xl font-semibold mb-2 text-center text-gray-800">Lupa Password?</h3>
            
            <!-- Info Text (styling konsisten dengan status info) -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                <p class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}</span>
                </p>
            </div>

            <!-- Session Status (success message) -->
            @if (session('status'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    <p class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('status') }}</span>
                    </p>
                </div>
            @endif
            
            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition @error('email') border-red-500 @enderror"
                           placeholder="Masukkan email terdaftar">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <a href="{{ route('login') }}" 
                       class="text-sm text-gray-600 hover:text-gray-900 hover:underline order-2 sm:order-1">
                        Kembali ke Login
                    </a>

                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-2.5 rounded-lg transition duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 order-1 sm:order-2 w-full sm:w-auto">
                        Kirim Link Reset Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Status kecil di bawah (mirip dengan status di halaman deteksi) -->
        <div class="mt-4 text-center text-sm text-gray-500">
            Sistem Deteksi Plat Nomor v1.0
        </div>
    </div>

    <script>
        // Optional: Tambahkan efek loading saat submit form
        const form = document.querySelector('form');
        const submitBtn = document.querySelector('button[type="submit"]');
        
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Mengirim...';
        });
    </script>
</body>
</html>