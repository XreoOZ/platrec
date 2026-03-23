<!DOCTYPE html>
<html>
<head>
    <title>Login - Deteksi Plat Nomor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <!-- HEADER (sama persis dengan halaman deteksi) -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6">
        <h1 class="text-xl">Login - Deteksi Plat Nomor</h1>
    </header>

    <!-- MAIN CONTENT - CENTERED LOGIN CARD -->
    <div class="max-w-md mx-auto mt-12 p-6">
        
        <!-- LOGIN CARD (mirip dengan card camera feed) -->
        <div class="bg-white shadow rounded-lg p-8">
            <h3 class="text-2xl font-semibold mb-6 text-center text-gray-800">Login</h3>
            
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Field -->
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition @error('email') border-red-500 @enderror"
                           placeholder="Masukkan email">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition @error('password') border-red-500 @enderror"
                           placeholder="Masukkan password">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between mb-6">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                            Lupa password?
                        </a>
                    @endif
                </div>

                <!-- Login Button & Register Link (seperti layout tombol di halaman deteksi) -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <a href="{{ route('register') }}" 
                       class="text-sm text-gray-600 hover:text-gray-900 hover:underline order-2 sm:order-1">
                        Belum punya akun? Register
                    </a>

                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-2.5 rounded-lg transition duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 order-1 sm:order-2 w-full sm:w-auto">
                        Login
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
            submitBtn.innerHTML = 'Loading...';
        });
    </script>
</body>
</html>