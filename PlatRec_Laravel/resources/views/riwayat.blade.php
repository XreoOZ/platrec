<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Deteksi - Deteksi Plat Nomor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100">

    <!-- HEADER -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6">
        <a href="/dashboard" class="text-blue-300 hover:text-white mr-4">← Back</a>
        <h1 class="text-xl">Riwayat Deteksi Plat Nomor</h1>
    </header>

    <!-- MAIN CONTENT -->
    <div class="max-w-6xl mx-auto mt-8 p-6">
        
        <!-- FILTER CARD -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <form method="GET" action="{{ url()->current() }}" class="flex flex-wrap gap-4 items-center">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari Plat</label>
                    <input type="text" name="search" placeholder="Contoh: B 1234 ABC" value="{{ request('search') }}" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-blue-500 focus:border-blue-500 w-64">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        Filter
                    </button>
                    <a href="{{ url()->current() }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- NOTIFICATION -->
        <div id="successAlert" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"></div>
        <div id="errorAlert" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>

        <!-- RIWAYAT CARD -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gambar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php $no = 1; @endphp
                        @forelse($plates as $plate)
                        @php 
                            $isTidakTerdeteksi = empty($plate->plate_number) || $plate->plate_number == 'Tidak Terdeteksi';
                            $isKurangLengkap = false;
                            if (!$isTidakTerdeteksi) {
                                $isKurangLengkap = !preg_match('/^[A-Za-z]{1,2}\s*\d{1,4}\s*[A-Za-z]{1,3}$/', trim($plate->plate_number));
                            }
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $no++ }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($plate->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($plate->created_at)->format('H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($plate->original_image)
                                <button onclick="lihatGambar('{{ $plate->original_image }}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Lihat
                                </button>
                                @else
                                <span class="text-gray-400 text-sm">Tidak ada</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick="openEditModal({{ $plate->id }}, '{{ $plate->plate_number }}', '{{ $plate->original_image ?? '' }}')" 
                                        class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada data riwayat deteksi
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="bg-gray-50 px-6 py-4 border-t">
                {{ $plates->links() }}
            </div>
        </div>

    </div>

    <!-- Modal Lihat Gambar -->
    <div id="gambarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-3xl mx-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Gambar Plat Nomor</h3>
                <button onclick="tutupModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <img id="modalImage" src="" alt="Gambar Plat" class="max-w-full h-auto rounded">
            </div>
        </div>
    </div>

    <!-- MODAL EDIT PLAT -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-2xl w-full mx-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Edit Plat Nomor</h3>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4 flex flex-col md:flex-row gap-6">
                <!-- Image Side -->
                <div class="w-full md:w-1/2 flex flex-col items-center justify-center bg-gray-100 rounded-lg overflow-hidden border border-gray-200 min-h-[200px]">
                    <img id="editPlateImage" src="" alt="Gambar Plat" class="max-w-full max-h-48 object-contain hidden">
                    <span id="noEditImageText" class="text-gray-400 text-sm">Tidak ada gambar</span>
                </div>
                <!-- Form Side -->
                <div class="w-full md:w-1/2 flex flex-col justify-center">
                    <form id="editForm" onsubmit="saveEdit(event)" class="w-full">
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plat Nomor</label>
                            <input type="text" id="editPlateNumber" name="plate_number" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Masukkan plat nomor">
                        </div>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" onclick="closeEditModal()" 
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 shadow-sm">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentEditId = null;

        function lihatGambar(imagePath) {
            const modal = document.getElementById('gambarModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = `http://localhost:5000/${imagePath}`;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function tutupModal() {
            const modal = document.getElementById('gambarModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // FUNGSI EDIT MODAL
        function openEditModal(id, plateNumber, imagePath) {
            currentEditId = id;
            document.getElementById('editId').value = id;
            document.getElementById('editPlateNumber').value = plateNumber;
            
            const imageEl = document.getElementById('editPlateImage');
            const noImageText = document.getElementById('noEditImageText');
            
            if (imagePath && imagePath.trim() !== '') {
                imageEl.src = `http://localhost:5000/${imagePath}`;
                imageEl.classList.remove('hidden');
                noImageText.classList.add('hidden');
            } else {
                imageEl.src = '';
                imageEl.classList.add('hidden');
                noImageText.classList.remove('hidden');
            }
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
            currentEditId = null;
        }

        function saveEdit(event) {
            event.preventDefault();
            
            const id = document.getElementById('editId').value;
            const newPlateNumber = document.getElementById('editPlateNumber').value;
            
            fetch(`/api/plates/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    plate_number: newPlateNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh halaman atau update row
                    showAlert('success', 'Plat nomor berhasil diperbarui!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('error', 'Gagal memperbarui plat nomor: ' + data.error);
                }
            })
            .catch(error => {
                showAlert('error', 'Terjadi kesalahan: ' + error);
            })
            .finally(() => {
                closeEditModal();
            });
        }

        function showAlert(type, message) {
            const alertDiv = document.getElementById(type + 'Alert');
            alertDiv.textContent = message;
            alertDiv.classList.remove('hidden');
            
            setTimeout(() => {
                alertDiv.classList.add('hidden');
            }, 3000);
        }

        // Click outside modal to close
        document.getElementById('gambarModal').addEventListener('click', function(e) {
            if (e.target === this) {
                tutupModal();
            }
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>