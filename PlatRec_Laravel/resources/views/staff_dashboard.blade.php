<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Staff - Aplikasi Plat Nomor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .feature-btn {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .feature-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        tbody tr:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- HEADER -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6 shadow-md">
        <div class="flex items-center space-x-3">
            <i class="fas fa-car-side text-2xl"></i>
            <h1 class="text-xl font-semibold tracking-wide">Dashboard Staff - Plat Nomor</h1>
        </div>

        <form action="/logout" method="POST" class="ml-auto">
            @csrf
            <button class="bg-red-600 hover:bg-red-700 transition duration-200 px-5 py-2 rounded-lg shadow flex items-center gap-2">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </header>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto mt-8 px-4">

        <!-- WELCOME MSG -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800 border-l-4 border-blue-500 pl-3">
                Selamat Datang di Dashboard Staff
            </h2>
            <div class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full shadow-sm">
                <i class="far fa-calendar-alt mr-1"></i> {{ date('d F Y') }}
            </div>
        </div>

        <!-- FEATURE BUTTONS -->
        <div class="grid grid-cols-2 gap-4 mb-10">
            <a href="/deteksi" class="feature-btn text-white text-center py-4 rounded-xl shadow-md transition" style="background:#3498db;">
                <i class="fas fa-camera-retro block text-2xl mb-1"></i>
                <span class="font-medium">Deteksi Plat Nomor</span>
            </a>
            <a href="/riwayat" class="feature-btn text-white text-center py-4 rounded-xl shadow-md transition" style="background:#2ecc71;">
                <i class="fas fa-history block text-2xl mb-1"></i>
                <span class="font-medium">Riwayat Deteksi</span>
            </a>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="mt-8 mb-6">
            <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4 border-l-4 border-green-500 max-w-sm">
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Berhasil Terdeteksi Hari Ini</p>
                    <p class="text-2xl font-bold">{{ $successfulDetections ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- RECENT ACTIVITY SECTION -->
        <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-blue-600"></i> 
                    Aktivitas Terbaru
                </h3>

            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-200 text-gray-700 uppercase text-xs tracking-wider">
                            <th class="p-4 border-b-2 border-gray-300 text-left">No</th>
                            <th class="p-4 border-b-2 border-gray-300 text-left">Plat Nomor</th>
                            <th class="p-4 border-b-2 border-gray-300 text-left">Tanggal</th>
                            <th class="p-4 border-b-2 border-gray-300 text-left">Jam</th>
                            <th class="p-4 border-b-2 border-gray-300 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($plates as $plate)
                        @php 
                            $isTidakTerdeteksi = empty($plate->plate_number) || $plate->plate_number == 'Tidak Terdeteksi';
                            $isKurangLengkap = false;
                            if (!$isTidakTerdeteksi) {
                                $isKurangLengkap = !preg_match('/^[A-Za-z]{1,2}\s*\d{1,4}\s*[A-Za-z]{1,3}$/', trim($plate->plate_number));
                            }
                        @endphp
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="p-4 font-mono text-xs">{{ $no++ }}</td>
                            <td class="p-4 font-semibold">
                                @if($isTidakTerdeteksi)
                                <span class="text-sm font-mono font-medium text-red-600 bg-red-100 px-3 py-1 rounded-full">
                                    Tidak Terdeteksi
                                </span>
                                @elseif($isKurangLengkap)
                                <span class="text-sm font-mono font-medium text-red-600 bg-red-100 px-3 py-1 rounded-full">
                                    {{ $plate->plate_number }}
                                </span>
                                @else
                                <span class="text-sm font-mono font-medium text-green-600 bg-green-100 px-3 py-1 rounded-full">
                                    {{ $plate->plate_number }}
                                </span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600 text-sm">
                                {{ \Carbon\Carbon::parse($plate->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="p-4 text-gray-600 text-sm">
                                {{ \Carbon\Carbon::parse($plate->created_at)->format('H:i:s') }}
                            </td>
                            <td class="p-4">
                                @if($isTidakTerdeteksi)
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Gagal
                                </span>
                                @elseif($isKurangLengkap)
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Perbaiki
                                </span>
                                @else
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Berhasil
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center p-8 text-gray-500 bg-gray-50">
                                Belum ada data plat nomor. Silakan lakukan deteksi atau input plat.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50 px-6 py-3 text-right text-xs text-gray-500 border-t border-gray-200 flex justify-between items-center">
                <div>
                    <i class="fas fa-info-circle"></i> Data diambil dari riwayat deteksi terbaru
                </div>
                <div>
                    <i class="fas fa-sync-alt"></i> Real-time data
                </div>
            </div>
        </div>

    </div>

    <footer class="text-center text-gray-400 text-xs py-6 border-t mt-6 bg-white">
        &copy; {{ date('Y') }} Aplikasi Plat Nomor
    </footer>
</body>
</html>
