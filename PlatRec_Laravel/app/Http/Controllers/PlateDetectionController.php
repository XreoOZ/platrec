<?php
/**
 * PlateDetectionController
 * 
 * Fungsi inti: Menghandle permintaan dari frontend untuk mendeteksi plat nomor
 * menggunakan Python API. Controller ini melakukan validasi gambar, mengirim
 * gambar ke Python API, menerima hasil deteksi, dan mengembalikan response
 * JSON ke client. Juga menyediakan endpoint untuk mengecek koneksi dengan 
 * Python API.
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PlateDetectionController extends Controller
{
    private $pythonApiUrl = 'http://localhost:5000';
    
    public function detectPlate(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        try {
            // Kirim gambar ke Python API
            $response = Http::timeout(30)
                ->attach('image', $request->file('image')->get(), 'image.jpg')
                ->post($this->pythonApiUrl . '/detect-plate');
            
            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'message' => 'Detection completed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Python API error: ' . $response->body()
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function checkPythonAPI()
    {
        try {
            $response = Http::get($this->pythonApiUrl . '/health');
            return response()->json([
                'status' => $response->successful() ? 'connected' : 'disconnected',
                'response' => $response->json()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'disconnected',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========== FUNGSI UNTUK VIEW ==========
    
    public function dashboard()
    {
        return view('dashboard');
    }

    public function staffDashboard()
    {
        return view('staff_dashboard');
    }

    public function deteksi()
    {
        return view('deteksi');
    }

    public function riwayat(Request $request)
    {
        $query = DB::table('plates');
        
        // Filter search
        if ($request->filled('search')) {
            $query->where('plate_number', 'like', '%' . $request->search . '%');
        }
        
        // Filter tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        // Order by terbaru
        $query->orderBy('created_at', 'desc');
        
        // Pagination
        $plates = $query->paginate(10);
        
        return view('riwayat', compact('plates'));
    }

    // ========== FUNGSI BARU: UPDATE PLATE ==========
    
    public function updatePlate(Request $request, $id)
    {
        try {
            $request->validate([
                'plate_number' => 'required|string|max:20'
            ]);

            $updated = DB::table('plates')
                ->where('id', $id)
                ->update(['plate_number' => $request->plate_number]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plat nomor berhasil diperbarui'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Data tidak ditemukan'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // ============================================
}