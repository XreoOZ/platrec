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
}