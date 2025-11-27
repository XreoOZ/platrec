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
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-2 bg-black min-h-96 flex items-center justify-center">
                        <!-- Method 1: Using img tag for MJPEG stream -->
                        <img id="cameraFeed" src="" alt="Camera Feed" 
                             class="w-full h-auto max-h-96 object-contain hidden">
                        
                        <!-- Method 2: Using iframe as fallback -->
                        <iframe id="cameraFrame" src="" class="w-full h-96 hidden border-0"></iframe>
                        
                        <div id="cameraPlaceholder" class="text-center py-20 text-gray-500">
                            Kamera belum aktif
                        </div>
                        <div id="cameraLoading" class="text-center py-20 text-white hidden">
                            ⏳ Memuat kamera...
                        </div>
                        <div id="cameraError" class="text-center py-20 text-red-500 hidden">
                            ❌ Gagal memuat kamera
                        </div>
                    </div>

                    <div class="mt-4 flex gap-4">
                        <button id="startBtn" 
                                class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded">
                            Start Deteksi (10 Detik)
                        </button>
                        <button id="stopBtn" 
                                class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded" disabled>
                            Stop Kamera
                        </button>
                        <button id="testBtn" 
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded">
                            Test Kamera
                        </button>
                    </div>

                    <!-- Countdown Timer -->
                    <div id="countdownContainer" class="mt-4 hidden">
                        <div class="bg-blue-100 border border-blue-300 rounded p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Waktu tersisa:</span>
                                <span id="countdown" class="text-xl font-bold text-blue-600">10</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <div id="countdownProgress" class="bg-blue-600 h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detection Results -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-4">Hasil Deteksi</h3>
                    
                    <div id="detectionResults" class="space-y-4 max-h-96 overflow-y-auto">
                        <div class="text-center text-gray-500 py-8">
                            Klik "Start Deteksi" untuk memulai
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mt-6 p-3 bg-gray-100 rounded">
                        <h4 class="font-semibold">Status:</h4>
                        <p id="statusText">Menunggu perintah</p>
                        <p id="detectionInfo" class="text-sm text-gray-600">-</p>
                        <p id="cameraStatus" class="text-sm text-gray-600">Kamera: Tidak aktif</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        const cameraFeed = document.getElementById('cameraFeed');
        const cameraFrame = document.getElementById('cameraFrame');
        const cameraPlaceholder = document.getElementById('cameraPlaceholder');
        const cameraLoading = document.getElementById('cameraLoading');
        const cameraError = document.getElementById('cameraError');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const testBtn = document.getElementById('testBtn');
        const statusText = document.getElementById('statusText');
        const detectionInfo = document.getElementById('detectionInfo');
        const cameraStatus = document.getElementById('cameraStatus');
        const detectionResults = document.getElementById('detectionResults');
        const countdownContainer = document.getElementById('countdownContainer');
        const countdown = document.getElementById('countdown');
        const countdownProgress = document.getElementById('countdownProgress');

        let cameraActive = false;
        let detectionData = [];
        let isDetecting = false;

        // Test Camera Function
        testBtn.addEventListener('click', async () => {
            try {
                cameraLoading.classList.remove('hidden');
                cameraPlaceholder.classList.add('hidden');
                cameraError.classList.add('hidden');
                statusText.textContent = 'Testing kamera...';
                
                // Test single frame first
                const testResponse = await fetch('http://localhost:5000/get_single_frame');
                if (testResponse.ok) {
                    const blob = await testResponse.blob();
                    const url = URL.createObjectURL(blob);
                    cameraFeed.src = url;
                    cameraFeed.classList.remove('hidden');
                    cameraLoading.classList.add('hidden');
                    statusText.textContent = 'Kamera test berhasil!';
                    cameraStatus.textContent = 'Kamera: Test berhasil';
                } else {
                    throw new Error('Gagal mengambil frame');
                }
            } catch (error) {
                console.error('Camera test error:', error);
                cameraLoading.classList.add('hidden');
                cameraError.classList.remove('hidden');
                statusText.textContent = 'Test kamera gagal: ' + error.message;
            }
        });

        // Start 10 Second Detection
        startBtn.addEventListener('click', async () => {
            if (isDetecting) return;
            
            try {
                isDetecting = true;
                startBtn.disabled = true;
                testBtn.disabled = true;
                statusText.textContent = 'Memulai deteksi 10 detik...';
                cameraPlaceholder.classList.add('hidden');
                cameraError.classList.add('hidden');
                cameraLoading.classList.remove('hidden');
                
                // Start camera first
                const startResponse = await fetch('http://localhost:5000/start_camera');
                const startResult = await startResponse.json();
                
                if (!startResponse.ok) {
                    throw new Error(startResult.message || 'Gagal menghidupkan kamera');
                }
                
                // Show video feed using MJPEG stream
                cameraFeed.src = 'http://localhost:5000/video_feed?' + new Date().getTime();
                cameraFeed.classList.remove('hidden');
                cameraLoading.classList.add('hidden');
                cameraActive = true;
                stopBtn.disabled = false;
                
                // Show countdown
                countdownContainer.classList.remove('hidden');
                startCountdown(10);
                
                statusText.textContent = 'Sedang mendeteksi plat nomor...';
                cameraStatus.textContent = 'Kamera: Aktif - Mendeteksi';
                
                // Start 10-second detection
                const detectionResponse = await fetch('http://localhost:5000/detect_10_seconds');
                const result = await detectionResponse.json();
                
                if (result.success) {
                    detectionData = result.detections;
                    updateDetectionResults();
                    statusText.textContent = `Deteksi selesai! ${result.total_detected} plat ditemukan`;
                    detectionInfo.textContent = `Durasi: ${result.duration} detik`;
                    
                    // Enable save button if detections found
                    captureBtn.disabled = detectionData.length === 0;
                } else {
                    throw new Error('Deteksi gagal');
                }
                
            } catch (error) {
                console.error('Detection error:', error);
                statusText.textContent = 'Error: ' + error.message;
                cameraError.classList.remove('hidden');
                cameraLoading.classList.add('hidden');
            } finally {
                isDetecting = false;
                startBtn.disabled = false;
                testBtn.disabled = false;
                countdownContainer.classList.add('hidden');
                // Don't stop camera immediately, let user see the results
            }
        });

        // Countdown function
        function startCountdown(seconds) {
            let timeLeft = seconds;
            countdown.textContent = timeLeft;
            countdownProgress.style.width = '100%';
            
            const countdownInterval = setInterval(() => {
                timeLeft--;
                countdown.textContent = timeLeft;
                
                // Update progress bar
                const progressPercent = (timeLeft / seconds) * 100;
                countdownProgress.style.width = progressPercent + '%';
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }

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
            cameraFrame.classList.add('hidden');
            cameraLoading.classList.add('hidden');
            cameraError.classList.add('hidden');
            cameraPlaceholder.classList.remove('hidden');
            
            startBtn.disabled = false;
            testBtn.disabled = false;
            stopBtn.disabled = true;
            statusText.textContent = 'Kamera tidak aktif';
            detectionInfo.textContent = '-';
            cameraStatus.textContent = 'Kamera: Tidak aktif';
        }

        function updateDetectionResults() {
            if (detectionData.length > 0) {
                detectionResults.innerHTML = detectionData.map(detection => `
                    <div class="p-3 bg-green-100 border border-green-300 rounded">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>Plat Terdeteksi:</strong> 
                                <span class="font-mono text-lg">${detection.text}</span>
                            </div>
                            <span class="bg-green-500 text-white px-2 py-1 rounded text-xs">
                                ${(detection.confidence * 100).toFixed(1)}%
                            </span>
                        </div>
                        <div class="text-xs text-gray-600 mt-1">
                            Deteksi pada: ${detection.timestamp.toFixed(1)}s
                        </div>
                    </div>
                `).join('');
                
                detectionInfo.textContent = `Total: ${detectionData.length} plat terdeteksi`;
            } else {
                detectionResults.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        Tidak ada plat yang terdeteksi selama 10 detik
                    </div>
                `;
                detectionInfo.textContent = 'Tidak ada deteksi';
            }
        }

        // Save Results
        const captureBtn = document.getElementById('captureBtn');
        if (captureBtn) {
            captureBtn.addEventListener('click', async () => {
                if (detectionData.length > 0) {
                    try {
                        const plateNumbers = detectionData.map(d => d.text);
                        
                        const response = await fetch('/api/save-detection', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                plate_numbers: plateNumbers,
                                timestamp: new Date().toISOString(),
                                total_detected: detectionData.length
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
        }

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
                if (response.ok) {
                    statusText.textContent = 'Python API siap';
                    cameraStatus.textContent = 'Status: Python API terhubung';
                } else {
                    statusText.textContent = 'Python API tidak aktif';
                    startBtn.disabled = true;
                    testBtn.disabled = true;
                }
            } catch (error) {
                statusText.textContent = 'Python API tidak terhubung';
                cameraStatus.textContent = 'Status: Python API tidak terhubung';
                startBtn.disabled = true;
                testBtn.disabled = true;
            }
        }

        checkPythonAPI();
    </script>
</body>
</html>