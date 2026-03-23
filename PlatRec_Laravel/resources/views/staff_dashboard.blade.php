<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Staff - Aplikasi Plat Nomor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <!-- HEADER -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6">
        <h1 class="text-xl">Dashboard Staff</h1>

        <form action="/logout" method="POST" class="ml-auto">
            @csrf
            <button class="bg-red-500 px-4 py-2 rounded">
                Logout
            </button>
        </form>
    </header>

    <!-- MAIN CONTENT -->
    <div class="max-w-6xl mx-auto mt-8">

        <!-- WELCOME MSG -->
        <h2 class="text-2xl font-semibold mb-6">
            Selamat Datang di Dashboard Staff
        </h2>

        <!-- FEATURE BUTTONS (Filtered for Staff) -->
        <div class="grid grid-cols-2 gap-4 mb-10">

            <a href="/deteksi"
               class="text-white text-center py-6 rounded shadow"
               style="background:#3498db;">
                Deteksi Plat Nomor
            </a>

            <a href="/riwayat"
               class="text-white text-center py-6 rounded shadow"
               style="background:#2ecc71;">
                Riwayat Deteksi
            </a>

            <!-- Removed Admin-only links here (Manajemen/Input, Pengaturan, Laporan) -->
        </div>

        <!-- RECENT ACTIVITY -->
        <h3 class="text-xl font-semibold mb-3">Aktivitas Terbaru</h3>

        <div class="bg-white shadow rounded p-4 overflow-x-auto">

            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="p-3 border">Tanggal</th>
                        <th class="p-3 border">Aktivitas</th>
                        <th class="p-3 border">Status</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- Contoh data statis, nanti tinggal ganti dari database -->
                    <tr>
                        <td class="border p-3">2024-01-15 10:30</td>
                        <td class="border p-3">Deteksi plat nomor B 1234 CD</td>
                        <td class="border p-3">Berhasil</td>
                    </tr>

                    <tr>
                        <td class="border p-3">2024-01-15 09:15</td>
                        <td class="border p-3">Deteksi plat nomor AB 567 EF</td>
                        <td class="border p-3">Berhasil</td>
                    </tr>

                    <tr>
                        <td class="border p-3">2024-01-14 16:45</td>
                        <td class="border p-3">Deteksi plat nomor H 7890 JK</td>
                        <td class="border p-3">Gagal</td>
                    </tr>

                </tbody>
            </table>

        </div>

    </div>

</body>
</html>
