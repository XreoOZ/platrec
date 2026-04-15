<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar Staff - Aplikasi Plat Nomor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">

    <!-- HEADER -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6 shadow-md">
        <div class="flex items-center space-x-3">
            <i class="fas fa-users text-2xl"></i>
            <h1 class="text-xl font-semibold tracking-wide">Manajemen Staff</h1>
        </div>

        <div class="ml-auto flex items-center space-x-4">
            <a href="/admin/dashboard" class="text-gray-300 hover:text-white transition">
                <i class="fas fa-home"></i> Kembali ke Dashboard
            </a>
            <form action="/logout" method="POST" class="ml-auto inline">
                @csrf
                <button class="bg-red-600 hover:bg-red-700 transition duration-200 px-5 py-2 rounded-lg shadow flex items-center gap-2">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto mt-8 px-4">

        <!-- WELCOME & ACTIONS -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800 border-l-4 border-fuchsia-500 pl-3">
                Daftar Staff
            </h2>
        </div>

        <!-- ALERTS -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Gagal mendaftarkan staff!</strong>
                <ul class="list-disc pl-5 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- FORM TAMBAH STAFF -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-user-plus text-blue-600"></i>
                        Daftarkan Staff Baru
                    </h3>
                    
                    <form action="{{ route('staff.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                Nama Lengkap
                            </label>
                            <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                id="name" name="name" type="text" placeholder="Masukkan nama staff" required value="{{ old('name') }}">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Email
                            </label>
                            <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                id="email" name="email" type="email" placeholder="email@contoh.com" required value="{{ old('email') }}">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                                Password
                            </label>
                            <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                id="password" name="password" type="password" placeholder="********" required>
                            <p class="text-xs text-gray-500 mt-1">*Minimal 6 karakter</p>
                        </div>

                        <div class="flex items-center justify-end">
                            <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-200 flex items-center gap-2" type="submit">
                                <i class="fas fa-save"></i> Simpan Staff
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL DAFTAR STAFF -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-700 flex items-center gap-2">
                            <i class="fas fa-list-ul text-fuchsia-600"></i> 
                            List User Staff
                        </h3>
                        <span class="text-sm text-gray-500">Total: {{ $staffs->count() }} staff</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-200 text-gray-700 uppercase text-xs tracking-wider">
                                    <th class="p-4 border-b-2 border-gray-300 text-left">No</th>
                                    <th class="p-4 border-b-2 border-gray-300 text-left">Info Staff</th>
                                    <th class="p-4 border-b-2 border-gray-300 text-left">Role</th>
                                    <th class="p-4 border-b-2 border-gray-300 text-left">Tanggal Daftar</th>
                                    <th class="p-4 border-b-2 border-gray-300 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staffs as $index => $staff)
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition" id="staff-row-{{ $staff->id }}">
                                    <td class="p-4 font-mono text-xs">{{ $index + 1 }}</td>
                                    <td class="p-4">
                                        <p class="font-semibold text-gray-800">{{ $staff->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $staff->email }}</p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 uppercase tracking-wide">
                                            {{ $staff->role }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-gray-600 text-xs">
                                        {{ \Carbon\Carbon::parse($staff->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- EDIT BUTTON -->
                                            <button onclick="openEditModal({{ json_encode($staff) }})" 
                                                class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition duration-200 flex items-center gap-1">
                                                <i class="fas fa-edit text-xs"></i> Edit
                                            </button>
                                            
                                            <!-- DELETE BUTTON (Optional) -->
                                            <button onclick="confirmDelete({{ $staff->id }}, '{{ $staff->name }}')" 
                                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition duration-200 flex items-center gap-1">
                                                <i class="fas fa-trash text-xs"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center p-8 text-gray-500 bg-gray-50">
                                        <i class="fas fa-users fa-2x mb-2 text-gray-300"></i>
                                        <p>Belum ada staff terdaftar.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- PAGINATION (if using pagination) -->
                    @if(method_exists($staffs, 'links'))
                        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
                            {{ $staffs->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- EDIT STAFF MODAL -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-2xl bg-white">
            <div class="flex justify-between items-center mb-4 pb-3 border-b">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-user-edit text-amber-500"></i>
                    Edit Data Staff
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="editStaffForm" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyimpan perubahan data staff ini?');">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_name">
                        Nama Lengkap
                    </label>
                    <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-amber-500" 
                        id="edit_name" name="name" type="text" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_email">
                        Email
                    </label>
                    <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-amber-500" 
                        id="edit_email" name="email" type="email" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_password">
                        Password (Kosongkan jika tidak diubah)
                    </label>
                    <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-amber-500" 
                        id="edit_password" name="password" type="password" placeholder="********">
                    <p class="text-xs text-gray-500 mt-1">*Minimal 6 karakter jika diisi</p>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-3 border-t">
                    <button type="button" onclick="closeEditModal()" 
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-200">
                        Batal
                    </button>
                    <button type="submit" 
                        class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-4 rounded transition duration-200 flex items-center gap-2">
                        <i class="fas fa-save"></i> Update Staff
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-2xl bg-white">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-500 mb-4" id="deleteMessage">Apakah Anda yakin ingin menghapus staff ini?</p>
                
                <form id="deleteStaffForm" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="flex items-center justify-center gap-3">
                        <button type="button" onclick="closeDeleteModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-200">
                            Batal
                        </button>
                        <button type="submit" 
                            class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200">
                            Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center text-gray-400 text-xs py-6 border-t mt-8 bg-white">
        &copy; {{ date('Y') }} Aplikasi Plat Nomor
    </footer>

    <script>
        // EDIT MODAL FUNCTIONS
        function openEditModal(staff) {
            const modal = document.getElementById('editModal');
            const form = document.getElementById('editStaffForm');
            
            // Set form action URL
            form.action = `/staff/${staff.id}/update`;
            
            // Fill form with staff data
            document.getElementById('edit_name').value = staff.name;
            document.getElementById('edit_email').value = staff.email;
            document.getElementById('edit_password').value = ''; // Clear password field
            
            // Show modal
            modal.classList.remove('hidden');
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            
            // Reset form
            document.getElementById('editStaffForm').reset();
        }
        
        // DELETE MODAL FUNCTIONS
        function confirmDelete(id, name) {
            const modal = document.getElementById('deleteModal');
            const form = document.getElementById('deleteStaffForm');
            const message = document.getElementById('deleteMessage');
            
            // Set form action URL
            form.action = `/staff/${id}/delete`;
            
            // Set message
            message.innerHTML = `Apakah Anda yakin ingin menghapus staff "<strong>${name}</strong>"?`;
            
            // Show modal
            modal.classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>