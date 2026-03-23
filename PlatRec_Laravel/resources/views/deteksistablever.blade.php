<!DOCTYPE html>
<html>
<head>
    <title>Deteksi Plat Nomor - Capture & Detect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <!-- HEADER -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6">
        <a href="/dashboard" class="text-blue-300 hover:text-white mr-4">← Back</a>
        <h1 class="text-xl">Deteksi Plat Nomor (Capture Manual)</h1>
    </header>

    <!-- MAIN CONTENT -->
    <div class="max-w-6xl mx-auto mt-8 p-6">

        <!-- CAMERA SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Camera Feed -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-4">Kamera Live</h3>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-black min-h-96 flex items-center justify-center">
                        <img id="cameraFeed" src="" alt="Camera Feed" 
                             class="w-full h-auto max-h-96 object-contain hidden">
                        
                        <div id="cameraPlaceholder" class="text-center py-20 text-gray-500">
                            Kamera belum dinyalakan
                        </div>
                        <div id="cameraLoading" class="text-center py-20 text-white hidden">
                            ⏳ Memuat kamera (ini mungkin butuh waktu karena PC lambat)...
                        </div>
                        <div id="cameraError" class="text-center py-20 text-red-500 hidden">
                            ❌ Gagal memuat kamera
                        </div>
                    </div>

                    <div class="mt-4 flex gap-4 flex-wrap">
                        <!-- STEP 1: Nyalakan Kamera -->
                        <button id="powerOnCameraBtn" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded disabled:bg-gray-400">
                            💡 1. Nyalakan Kamera
                        </button>
                        
                        <!-- STEP 2: Ambil Gambar (muncul setelah kamera menyala) -->
                        <button id="captureBtn" 
                                class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded disabled:bg-gray-400" disabled>
                            📸 2. Ambil Gambar
                        </button>
                        
                        <button id="stopBtn" 
                                class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded disabled:bg-gray-400" disabled>
                            Matikan Kamera
                        </button>
                        
                        <button id="retryBtn" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded hidden">
                            🔄 Coba Lagi Nyalakan Kamera
                        </button>
                    </div>

                    <!-- Info Delay -->
                    <div id="delayInfo" class="mt-4 text-sm text-yellow-600 bg-yellow-50 border border-yellow-300 rounded p-3 hidden">
                        ⏱️ Kamera sedang menyala, mohon tunggu... (PC lambat, bisa butuh 10-15 detik)
                    </div>
                    
                    <!-- Status Kamera -->
                    <div class="mt-4 text-sm" id="cameraStatusInfo">
                        Status Kamera: <span class="font-semibold" id="cameraStatusText">Mati</span>
                    </div>
                </div>
            </div>

            <!-- Detection Results & Logs -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-4">Hasil Deteksi & Log</h3>
                    
                    <!-- STEP 3: Hasil Plat Nomor -->
                    <div id="plateResult" class="mb-6 p-4 bg-gray-100 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Plat Nomor Terdeteksi:</h4>
                        <div id="detectedPlate" class="text-2xl font-mono text-center text-green-600 min-h-[3rem]">
                            -
                        </div>
                    </div>

                    <!-- STEP 4: Log dengan Format -->
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Log Deteksi:</h4>
                        <div id="detectionLogs" class="space-y-2 max-h-60 overflow-y-auto text-sm">
                            <div class="text-center text-gray-500 py-4">
                                Belum ada log. Ambil gambar untuk memulai.
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mt-6 p-3 bg-gray-100 rounded">
                        <h4 class="font-semibold">Status Sistem:</h4>
                        <p id="statusText">Menunggu perintah</p>
                        <p id="apiStatus" class="text-sm text-gray-600">API: Memeriksa...</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        const cameraFeed = document.getElementById('cameraFeed');
        const cameraPlaceholder = document.getElementById('cameraPlaceholder');
        const cameraLoading = document.getElementById('cameraLoading');
        const cameraError = document.getElementById('cameraError');
        const powerOnCameraBtn = document.getElementById('powerOnCameraBtn');
        const captureBtn = document.getElementById('captureBtn');
        const stopBtn = document.getElementById('stopBtn');
        const retryBtn = document.getElementById('retryBtn');
        const delayInfo = document.getElementById('delayInfo');
        const statusText = document.getElementById('statusText');
        const apiStatus = document.getElementById('apiStatus');
        const cameraStatusText = document.getElementById('cameraStatusText');
        const detectedPlate = document.getElementById('detectedPlate');
        const detectionLogs = document.getElementById('detectionLogs');

        let cameraActive = false;
        let isCameraStarting = false;

        // ==================== STEP 1: Nyalakan Kamera ====================
        powerOnCameraBtn.addEventListener('click', async () => {
            await powerOnCamera();
        });

        async function powerOnCamera() {
            if (isCameraStarting) return;
            
            try {
                isCameraStarting = true;
                powerOnCameraBtn.disabled = true;
                captureBtn.disabled = true;
                stopBtn.disabled = true;
                
                showLoading('Memulai kamera...');
                delayInfo.classList.remove('hidden');
                statusText.textContent = 'Menghidupkan kamera (mohon tunggu, ini bisa lama)...';
                
                // Panggil endpoint untuk start camera
                const startResponse = await fetchWithTimeout('http://localhost:5000/start_camera_with_stream', 30000);
                
                if (!startResponse.ok) {
                    let errorMsg = 'Gagal menghidupkan kamera';
                    try {
                        const errorData = await startResponse.json();
                        errorMsg = errorData.message || errorMsg;
                    } catch (e) {}
                    throw new Error(errorMsg);
                }

                statusText.textContent = 'Kamera mulai, menunggu stabilisasi...';
                await new Promise(resolve => setTimeout(resolve, 3000));

                // Cek apakah stream siap
                statusText.textContent = 'Memeriksa koneksi stream...';
                try {
                    const streamCheck = await fetchWithTimeout('http://localhost:5000/check_stream_ready', 10000);
                    const streamData = await streamCheck.json();
                    
                    if (!streamData.ready) {
                        console.warn('Stream not ready yet, but continuing...');
                    }
                } catch (e) {
                    console.warn('Stream check failed, but continuing...');
                }

                // Mulai stream video
                statusText.textContent = 'Memulai stream video...';
                cameraFeed.src = 'http://localhost:5000/video_feed?' + new Date().getTime();
                
                // Update UI: Kamera aktif (langsung anggap sukses)
                cameraFeed.classList.remove('hidden');
                hideLoading();
                delayInfo.classList.add('hidden');
                
                cameraActive = true;
                isCameraStarting = false;
                
                // STEP 2: Tombol Ambil Gambar sekarang aktif
                captureBtn.disabled = false;
                stopBtn.disabled = false;
                powerOnCameraBtn.disabled = false;
                
                statusText.textContent = 'Kamera siap. Silakan ambil gambar.';
                cameraStatusText.textContent = 'Menyala (siap)';
                cameraStatusText.classList.add('text-green-600');
                cameraStatusText.classList.remove('text-red-600');

            } catch (error) {
                console.error('Power on camera error:', error);
                showError(`Gagal menyalakan kamera: ${error.message}`);
                
                cameraActive = false;
                isCameraStarting = false;
                
                powerOnCameraBtn.disabled = false;
                captureBtn.disabled = true;
                stopBtn.disabled = true;
                
                retryBtn.classList.remove('hidden');
                
                statusText.textContent = 'Gagal menyalakan kamera. Coba lagi.';
                cameraStatusText.textContent = 'Error';
                cameraStatusText.classList.remove('text-green-600');
                cameraStatusText.classList.add('text-red-600');
            }
        }

        // ==================== STEP 2: Ambil Gambar ====================
        captureBtn.addEventListener('click', async () => {
            if (!cameraActive) {
                alert('Kamera belum menyala. Nyalakan kamera dulu.');
                return;
            }
            
            try {
                captureBtn.disabled = true;
                statusText.textContent = 'Mengambil gambar dan mendeteksi plat...';
                
                const captureResponse = await fetchWithTimeout('http://localhost:5000/capture_and_detect', 30000);
                
                if (!captureResponse.ok) {
                    let errorMsg = 'Gagal mengambil gambar';
                    try {
                        const errorData = await captureResponse.json();
                        errorMsg = errorData.message || errorMsg;
                    } catch (e) {}
                    throw new Error(errorMsg);
                }
                
                const result = await captureResponse.json();
                
                if (result.success && result.plate_text && result.plate_text !== "Tidak Terdeteksi") {
                    detectedPlate.textContent = result.plate_text;
                    
                    const now = new Date();
                    const tanggal = now.getDate().toString().padStart(2, '0');
                    const bulan = (now.getMonth() + 1).toString().padStart(2, '0');
                    const tahun = now.getFullYear();
                    const jam = now.getHours().toString().padStart(2, '0');
                    const menit = now.getMinutes().toString().padStart(2, '0');
                    
                    const logEntry = `${result.plate_text}-${tanggal}/${bulan}/${tahun}-${jam}:${menit}`;
                    
                    addLogToDisplay(logEntry, result.image_path);
                    
                    statusText.textContent = 'Deteksi berhasil!';
                } else {
                    detectedPlate.textContent = 'Tidak terdeteksi';
                    
                    const now = new Date();
                    const tanggal = now.getDate().toString().padStart(2, '0');
                    const bulan = (now.getMonth() + 1).toString().padStart(2, '0');
                    const tahun = now.getFullYear();
                    const jam = now.getHours().toString().padStart(2, '0');
                    const menit = now.getMinutes().toString().padStart(2, '0');
                    
                    const logEntry = `Tidak Terdeteksi-${tanggal}/${bulan}/${tahun}-${jam}:${menit}`;
                    addLogToDisplay(logEntry, null, true);
                    
                    statusText.textContent = 'Tidak ada plat terdeteksi.';
                }
                
                captureBtn.disabled = false;

            } catch (error) {
                console.error('Capture error:', error);
                statusText.textContent = `Error: ${error.message}`;
                alert(`Gagal mengambil gambar: ${error.message}`);
                captureBtn.disabled = false;
            }
        });

        function addLogToDisplay(logText, imagePath, isNoDetection = false) {
            if (detectionLogs.children.length === 1 && detectionLogs.children[0].textContent.includes('Belum ada log')) {
                detectionLogs.innerHTML = '';
            }
            
            const logDiv = document.createElement('div');
            logDiv.className = `p-2 rounded ${isNoDetection ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-800'} border text-sm font-mono`;
            logDiv.textContent = logText;
            
            if (imagePath) {
                logDiv.title = `Gambar tersimpan: ${imagePath}`;
                logDiv.classList.add('cursor-help');
                
                // Bisa diklik untuk lihat gambar
                logDiv.addEventListener('click', () => {
                    window.open(`http://localhost:5000/${imagePath}`, '_blank');
                });
            }
            
            detectionLogs.prepend(logDiv);
            
            while (detectionLogs.children.length > 20) {
                detectionLogs.removeChild(detectionLogs.lastChild);
            }
        }

        stopBtn.addEventListener('click', async () => {
            await stopCamera();
        });

        async function stopCamera() {
            try {
                statusText.textContent = 'Mematikan kamera...';
                await fetchWithTimeout('http://localhost:5000/stop_camera', 5000);
            } catch (error) {
                console.error('Stop camera error:', error);
            } finally {
                resetCameraState();
            }
        }

        function resetCameraState() {
            cameraActive = false;
            isCameraStarting = false;
            cameraFeed.src = '';
            cameraFeed.classList.add('hidden');
            hideLoading();
            cameraError.classList.add('hidden');
            cameraPlaceholder.classList.remove('hidden');
            delayInfo.classList.add('hidden');
            
            captureBtn.disabled = true;
            stopBtn.disabled = true;
            powerOnCameraBtn.disabled = false;
            retryBtn.classList.add('hidden');
            
            cameraStatusText.textContent = 'Mati';
            cameraStatusText.classList.remove('text-green-600', 'text-red-600');
        }

        function fetchWithTimeout(url, timeout = 15000) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);
            
            return fetch(url, { 
                signal: controller.signal,
                cache: 'no-cache',
                mode: 'cors'
            }).finally(() => {
                clearTimeout(timeoutId);
            });
        }

        function showLoading(message) {
            cameraLoading.classList.remove('hidden');
            cameraPlaceholder.classList.add('hidden');
            cameraError.classList.add('hidden');
            cameraFeed.classList.add('hidden');
            retryBtn.classList.add('hidden');
            
            const loadingEl = cameraLoading;
            loadingEl.innerHTML = `⏳ ${message}`;
        }

        function hideLoading() {
            cameraLoading.classList.add('hidden');
        }

        function showError(message) {
            cameraLoading.classList.add('hidden');
            cameraError.classList.remove('hidden');
            cameraError.innerHTML = `❌ ${message}<br><small>Kamera mungkin butuh waktu lebih lama untuk menyala</small>`;
            cameraPlaceholder.classList.add('hidden');
            cameraFeed.classList.add('hidden');
        }

        retryBtn.addEventListener('click', async () => {
            retryBtn.classList.add('hidden');
            await powerOnCamera();
        });

        window.addEventListener('beforeunload', () => {
            if (cameraActive) {
                fetch('http://localhost:5000/stop_camera', { 
                    method: 'GET',
                    keepalive: true 
                }).catch(() => {});
            }
        });

        // Check Python API status on load - TAPI JANGAN DISABLE TOMBOL!
        async function checkPythonAPI() {
            try {
                const response = await fetchWithTimeout('http://localhost:5000/health', 3000);
                if (response.ok) {
                    const data = await response.json();
                    statusText.textContent = 'Sistem siap';
                    apiStatus.textContent = `API: Terhubung - Kamera: ${data.camera_status}`;
                    apiStatus.classList.remove('text-red-600');
                    apiStatus.classList.add('text-green-600');
                    
                    if (data.camera_status === 'active') {
                        cameraActive = true;
                        captureBtn.disabled = false;
                        stopBtn.disabled = false;
                        cameraStatusText.textContent = 'Menyala (dari server)';
                        cameraStatusText.classList.add('text-green-600');
                        
                        cameraFeed.src = 'http://localhost:5000/video_feed?' + new Date().getTime();
                        cameraFeed.classList.remove('hidden');
                        cameraPlaceholder.classList.add('hidden');
                    }
                } else {
                    apiStatus.textContent = `API: Gagal response (${response.status})`;
                    apiStatus.classList.add('text-red-600');
                }
            } catch (error) {
                console.warn('API health check failed:', error);
                apiStatus.textContent = `API: Tidak terhubung (pastikan server Python berjalan di port 5000)`;
                apiStatus.classList.add('text-red-600');
                statusText.textContent = 'Menunggu koneksi ke server...';
            } finally {
                // TOMBOL TETAP AKTIF! user tetap bisa coba nyalakan kamera
                powerOnCameraBtn.disabled = false;
            }
        }

        // Panggil sekali, jangan looping terus
        checkPythonAPI();
        
        // Optional: cek ulang setiap 30 detik, tapi jangan disable tombol
        setInterval(() => {
            checkPythonAPI();
        }, 30000);
    </script>
</body>
</html>