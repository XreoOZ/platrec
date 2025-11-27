<!DOCTYPE html>
<html>
<head>
    <title>Deteksi Plat Nomor - Real Time</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <!-- HEADER -->
    <header class="bg-gray-800 text-white h-20 flex items-center px-6">
        <a href="/dashboard" class="text-blue-300 hover:text-white mr-4">← Back</a>
        <h1 class="text-xl">Deteksi Plat Nomor Real-Time</h1>
    </header>

    <!-- MAIN CONTENT -->
    <div class="max-w-6xl mx-auto mt-8 p-6">

        <!-- CAMERA SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Camera Feed -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-4">Kamera Live</h3>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-black">
                        <img id="cameraFeed" src="" alt="Camera Feed" 
                             class="w-full h-auto max-h-96 object-contain hidden">
                        <div id="cameraPlaceholder" class="text-center py-20 text-gray-500">
                            Kamera belum aktif
                        </div>
                        <div id="cameraLoading" class="text-center py-20 text-white hidden">
                            ⏳ Memuat kamera...
                        </div>
                    </div>

                    <div class="mt-4 flex gap-4">
                        <button id="startBtn" 
                                class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded">
                            Start Kamera
                        </button>
                        <button id="stopBtn" 
                                class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded" disabled>
                            Stop Kamera
                        </button>
                        <button id="captureBtn" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded" disabled>
                            Capture Plat
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detection Results -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-4">Hasil Deteksi</h3>
                    
                    <div id="detectionResults" class="space-y-4 max-h-96 overflow-y-auto">
                        <div class="text-center text-gray-500 py-8">
                            Hasil deteksi akan muncul di sini
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mt-6 p-3 bg-gray-100 rounded">
                        <h4 class="font-semibold">Status:</h4>
                        <p id="statusText">Kamera tidak aktif</p>
                        <p id="detectionInfo" class="text-sm text-gray-600">-</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        const cameraFeed = document.getElementById('cameraFeed');
        const cameraPlaceholder = document.getElementById('cameraPlaceholder');
        const cameraLoading = document.getElementById('cameraLoading');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const captureBtn = document.getElementById('captureBtn');
        const statusText = document.getElementById('statusText');
        const detectionInfo = document.getElementById('detectionInfo');
        const detectionResults = document.getElementById('detectionResults');

        let cameraActive = false;
        let detectionData = [];
        let videoStream = null;

        // Start Camera - FIXED VERSION
        startBtn.addEventListener('click', async () => {
            try {
                statusText.textContent = 'Menghidupkan kamera...';
                startBtn.disabled = true;
                cameraPlaceholder.classList.add('hidden');
                cameraLoading.classList.remove('hidden');
                
                // Start Python camera
                const startResponse = await fetch('http://localhost:5000/start_camera');
                if (!startResponse.ok) {
                    throw new Error('Gagal menghidupkan kamera di Python');
                }
                
                // Start video stream dengan cache bust
                cameraFeed.src = 'http://localhost:5000/video_feed?' + new Date().getTime();
                
                // Tunggu frame pertama load
                await new Promise((resolve, reject) => {
                    cameraFeed.onload = resolve;
                    cameraFeed.onerror = reject;
                    
                    // Timeout setelah 10 detik
                    setTimeout(() => reject(new Error('Timeout memuat kamera')), 10000);
                });

                cameraLoading.classList.add('hidden');
                cameraFeed.classList.remove('hidden');
                cameraActive = true;
                
                stopBtn.disabled = false;
                captureBtn.disabled = false;
                statusText.textContent = 'Kamera aktif - Deteksi berjalan';
                detectionInfo.textContent = 'Sedang mendeteksi...';
                
                // Start listening untuk detection data (TANPA XMLHttpRequest SYNC)
                startDetectionListening();
                
            } catch (error) {
                console.error('Camera start error:', error);
                cameraLoading.classList.add('hidden');
                cameraPlaceholder.classList.remove('hidden');
                statusText.textContent = 'Error: ' + error.message;
                startBtn.disabled = false;
            }
        });

        // Stop Camera
        stopBtn.addEventListener('click', async () => {
            try {
                await fetch('http://localhost:5000/stop_camera');
                stopCamera();
            } catch (error) {
                statusText.textContent = 'Error: ' + error.message;
            }
        });

        function stopCamera() {
            cameraActive = false;
            cameraFeed.src = '';
            cameraFeed.classList.add('hidden');
            cameraLoading.classList.add('hidden');
            cameraPlaceholder.classList.remove('hidden');
            
            startBtn.disabled = false;
            stopBtn.disabled = true;
            captureBtn.disabled = true;
            statusText.textContent = 'Kamera tidak aktif';
            detectionInfo.textContent = '-';
            
            // Clear results
            detectionData = [];
            updateDetectionResults();
            
            // Cleanup video stream
            if (videoStream) {
                videoStream = null;
            }
        }

        // FIXED: Detection listening tanpa ngehang
        function startDetectionListening() {
            // Pake EventSource/Server-Sent Events atau polling yang lebih ringan
            startLightweightPolling();
        }

        function startLightweightPolling() {
            let isPolling = false;
            
            const pollDetection = async () => {
                if (!cameraActive || isPolling) return;
                
                isPolling = true;
                try {
                    // Cek header detection dari video feed
                    const response = await fetch('http://localhost:5000/video_feed?' + new Date().getTime());
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    
                    let buffer = '';
                    
                    while (cameraActive) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        
                        buffer += decoder.decode(value, { stream: true });
                        
                        // Parse detection data dari header
                        const frameBoundary = buffer.indexOf('--frame');
                        if (frameBoundary !== -1) {
                            const frameData = buffer.substring(0, frameBoundary);
                            buffer = buffer.substring(frameBoundary);
                            
                            // Extract X-Detection-Data header
                            const detectionMatch = frameData.match(/X-Detection-Data:\s*([^\r\n]+)/i);
                            if (detectionMatch) {
                                const detectionHeader = detectionMatch[1];
                                if (detectionHeader) {
                                    detectionData = detectionHeader.split(',').filter(text => text.trim() !== '');
                                    updateDetectionResults();
                                }
                            }
                        }
                    }
                } catch (error) {
                    console.log('Detection polling error:', error);
                } finally {
                    isPolling = false;
                }
            };
            
            // Start polling
            pollDetection();
        }

        // Alternative: Simple polling (lebih reliable)
        function startSimplePolling() {
            const pollInterval = setInterval(() => {
                if (!cameraActive) {
                    clearInterval(pollInterval);
                    return;
                }
                
                // Karena kita ga bisa akses header langsung, kita tampilkan info saja
                detectionInfo.textContent = 'Deteksi berjalan - lihat hasil di video';
                
            }, 2000);
        }

        function updateDetectionResults() {
            if (detectionData.length > 0) {
                detectionResults.innerHTML = detectionData.map(text => `
                    <div class="p-3 bg-green-100 border border-green-300 rounded">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>Plat Terdeteksi:</strong> 
                                <span class="font-mono text-lg">${text}</span>
                            </div>
                            <span class="bg-green-500 text-white px-2 py-1 rounded text-xs">LIVE</span>
                        </div>
                        <div class="text-xs text-gray-600 mt-1">
                            ${new Date().toLocaleTimeString()}
                        </div>
                    </div>
                `).join('');
                
                detectionInfo.textContent = `Terdeteksi: ${detectionData.length} plat`;
            } else {
                detectionResults.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        Arahkan kamera ke plat nomor...
                    </div>
                `;
                detectionInfo.textContent = 'Menunggu deteksi...';
            }
        }

        // Capture Plate (Simpan ke database)
        captureBtn.addEventListener('click', async () => {
            if (detectionData.length > 0) {
                try {
                    const response = await fetch('/api/save-detection', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            plate_numbers: detectionData,
                            timestamp: new Date().toISOString()
                        })
                    });

                    if (response.ok) {
                        alert('✅ Plat nomor berhasil disimpan!');
                    } else {
                        alert('❌ Gagal menyimpan plat nomor');
                    }
                } catch (error) {
                    alert('❌ Error menyimpan data: ' + error.message);
                }
            } else {
                alert('⚠️ Tidak ada plat nomor yang terdeteksi!');
            }
        });

        // Auto cleanup ketika user leave page
        window.addEventListener('beforeunload', () => {
            if (cameraActive) {
                fetch('http://localhost:5000/stop_camera').catch(() => {});
            }
        });

        // Check Python API status on load
        async function checkPythonAPI() {
            try {
                const response = await fetch('http://localhost:5000/health');
                if (!response.ok) {
                    statusText.textContent = 'Python API tidak aktif';
                    startBtn.disabled = true;
                }
            } catch (error) {
                statusText.textContent = 'Python API tidak terhubung';
                startBtn.disabled = true;
            }
        }

        // Start dengan simple polling (lebih reliable)
        startDetectionListening = startSimplePolling;
        
        checkPythonAPI();
    </script>
</body>
</html>