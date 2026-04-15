from flask import Flask, request, jsonify, Response, send_from_directory
from flask_cors import CORS
import cv2
import numpy as np
from ultralytics import YOLO
import easyocr
import threading
import time
import os
import datetime
import mysql.connector
from mysql.connector import Error
import re

app = Flask(__name__)
CORS(app)

# ==================== DATABASE CONFIG ====================
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  
    'database': 'platrec_laravel'
}

def get_db_connection():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        return connection
    except Error as e:
        print(f"Error connecting to MySQL: {e}")
        return None

# Global variables for camera
camera = None
camera_lock = threading.Lock()
model = YOLO('epoch50.pt')
reader = easyocr.Reader(['en'])

# ==================== FOLDER CAPTURED_PHOTOS ====================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CAPTURE_FOLDER = os.path.join(BASE_DIR, 'captured_photos')

if not os.path.exists(CAPTURE_FOLDER):
    os.makedirs(CAPTURE_FOLDER)

def get_date_folder_path():
    today = datetime.datetime.now().strftime("%d-%m-%Y")
    date_folder = os.path.join(CAPTURE_FOLDER, today)
    if not os.path.exists(date_folder):
        os.makedirs(date_folder)
    return date_folder

def save_to_database(plate_number, image_path):
    """Menyimpan data plat ke database platrec_laravel tabel plates"""
    conn = None
    cursor = None
    try:
        conn = get_db_connection()
        if not conn:
            print("❌ Gagal konek database")
            return False
        
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO plates (plate_number, original_image) VALUES (%s, %s)",
            (plate_number, image_path)
        )
        conn.commit()
        print(f"✅ Tersimpan di DB platrec_laravel: {plate_number} - {image_path}")
        return True
    except Error as e:
        print(f"❌ DB Error: {e}")
        return False
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

def filter_largest_text(ocr_results):
    """
    Filter OCR results berdasarkan ukuran bounding box
    Ambil yang HURUFNYA PALING BESAR (bukan tanggal kecil)
    """
    if not ocr_results:
        return None, 0
    
    # Hitung luas bounding box untuk setiap hasil OCR
    results_with_area = []
    for (bbox, text, confidence) in ocr_results:
        # bbox format: [[x1,y1], [x2,y1], [x2,y2], [x1,y2]]
        x_coords = [point[0] for point in bbox]
        y_coords = [point[1] for point in bbox]
        width = max(x_coords) - min(x_coords)
        height = max(y_coords) - min(y_coords)
        area = width * height
        
        # Hanya proses teks yang mengandung huruf/angka
        if re.search(r'[A-Z0-9]', text.upper()):
            results_with_area.append((text, confidence, area))
            print(f"OCR: '{text}' - area: {area:.0f}, conf: {confidence:.2f}")
    
    if not results_with_area:
        return None, 0
    
    # Urutkan berdasarkan AREA (terbesar ke terkecil)
    results_with_area.sort(key=lambda x: x[2], reverse=True)
    
    # Ambil yang TERBESAR
    best_text, best_conf, best_area = results_with_area[0]
    
    # Cek apakah ada teks lain dengan area > 50% dari teks terbesar
    # Bisa jadi plat terpisah jadi beberapa teks
    combined_text = best_text
    for text, conf, area in results_with_area[1:]:
        if area > best_area * 0.5:  # Area > 50% dari teks terbesar
            combined_text += text
            print(f"  + Gabung: '{text}'")
    
    return combined_text.upper().replace(' ', ''), best_conf

def format_indonesian_plate(text):
    """
    Format plat nomor Indonesia: (LL) NNNN LLL
    Contoh: B 1234 ABC, AB 1234 CD
    - (LL) = 1-2 huruf (kode daerah)
    - NNNN = 1-4 angka
    - LLL = 1-3 huruf (akhiran)
    """
    # Bersihin dari karakter aneh, ambil huruf dan angka aja
    text = re.sub(r'[^A-Z0-9]', '', text.upper())
    
    if len(text) < 3:
        return text
    
    print(f"🔄 Formatting raw: '{text}'")
    
    # POLA 1: Format lengkap [HURUF 1-2][ANGKA 1-4][HURUF 1-3] - B1234ABC, AB1234CD
    # Ini yang paling penting buat (LL) NNNN LLL
    match = re.match(r'^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$', text)
    if match:
        kode = match.group(1)  # 1-2 huruf pertama (kode daerah)
        angka = match.group(2)  # angka
        huruf = match.group(3)  # 1-3 huruf terakhir
        result = f"{kode} {angka} {huruf}"
        print(f"  → POLA 1 (LL NNNN LLL): '{result}'")
        return result
    
    # POLA 2: Format [HURUF 1-2][ANGKA 1-4] - B1234, AB1234
    match = re.match(r'^([A-Z]{1,2})(\d{1,4})$', text)
    if match:
        kode = match.group(1)
        angka = match.group(2)
        result = f"{kode} {angka}"
        print(f"  → POLA 2 (LL NNNN): '{result}'")
        return result
    
    # POLA 3: Format [ANGKA 1-4][HURUF 1-3] - 1234ABC
    match = re.match(r'^(\d{1,4})([A-Z]{1,3})$', text)
    if match:
        angka = match.group(1)
        huruf = match.group(2)
        result = f"{angka} {huruf}"
        print(f"  → POLA 3 (NNNN LLL): '{result}'")
        return result
    
    # POLA 4: Manual parse - cari posisi angka pertama
    # Ini buat jaga-jaga kalau formatnya aneh
    angka_pos = re.search(r'\d', text)
    if angka_pos:
        kode_daerah = text[:angka_pos.start()]  # huruf sebelum angka
        sisa = text[angka_pos.start():]
        
        # Cari posisi huruf setelah angka
        huruf_pos = re.search(r'[A-Z]', sisa)
        if huruf_pos:
            angka = sisa[:huruf_pos.start()]
            huruf_akhir = sisa[huruf_pos.start():]
            if kode_daerah:
                result = f"{kode_daerah} {angka} {huruf_akhir}"
            else:
                result = f"{angka} {huruf_akhir}"
        else:
            if kode_daerah:
                result = f"{kode_daerah} {sisa}"
            else:
                result = sisa
        
        print(f"  → POLA 4 (manual): '{result}'")
        return result
    
    print(f"  → NO MATCH: '{text}'")
    return text

def init_camera():
    global camera
    try:
        for camera_index in [0, 1, 2]:
            temp_camera = cv2.VideoCapture(camera_index)
            if temp_camera.isOpened():
                temp_camera.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                temp_camera.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                temp_camera.set(cv2.CAP_PROP_FPS, 30)
                temp_camera.set(cv2.CAP_PROP_BUFFERSIZE, 1)
                
                success, frame = temp_camera.read()
                if success:
                    print(f"✓ Camera initialized on index {camera_index}")
                    return temp_camera, camera_index
                else:
                    temp_camera.release()
            else:
                temp_camera.release()
        
        print("✗ No camera found")
        return None, -1
    except Exception as e:
        print(f"✗ Camera error: {e}")
        return None, -1

def generate_frames():
    global camera
    max_retries = 5
    retry_count = 0
    
    while retry_count < max_retries:
        try:
            with camera_lock:
                if camera is None or not camera.isOpened():
                    camera, _ = init_camera()
                    if camera is None:
                        retry_count += 1
                        time.sleep(1)
                        continue
            
            while True:
                success, frame = camera.read()
                if not success:
                    retry_count += 1
                    with camera_lock:
                        if camera:
                            camera.release()
                            camera = None
                    break
                
                retry_count = 0
                
                try:
                    results = model(frame)
                    annotated_frame = frame.copy()
                    
                    for r in results:
                        annotated_frame = r.plot()
                    
                    ret, buffer = cv2.imencode('.jpg', annotated_frame, [cv2.IMWRITE_JPEG_QUALITY, 80])
                    if not ret:
                        continue
                        
                    frame_bytes = buffer.tobytes()
                    
                    yield (b'--frame\r\n'
                           b'Content-Type: image/jpeg\r\n\r\n' + 
                           frame_bytes + b'\r\n')
                except Exception as e:
                    print(f"Frame processing error: {e}")
                    continue
        except Exception as e:
            print(f"Generate frames error: {e}")
            retry_count += 1
            time.sleep(1)
    
    print("Max retries exceeded")

# ==================== ENDPOINT CAPTURE AND DETECT ====================
@app.route('/capture_and_detect')
def capture_and_detect():
    global camera
    
    try:
        with camera_lock:
            if camera is None or not camera.isOpened():
                camera, camera_index = init_camera()
                if camera is None:
                    return jsonify({'success': False, 'message': 'Kamera tidak tersedia'}), 500
                
                for _ in range(5):
                    camera.read()
                    time.sleep(0.1)
            
            success, frame = camera.read()
            if not success:
                time.sleep(0.2)
                success, frame = camera.read()
                if not success:
                    return jsonify({'success': False, 'message': 'Gagal mengambil gambar'}), 500
        
        date_folder = get_date_folder_path()
        timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"plate_{timestamp}.jpg"
        filepath = os.path.join(date_folder, filename)
        relative_path = os.path.join('captured_photos', 
                                     datetime.datetime.now().strftime("%d-%m-%Y"), 
                                     filename)
        
        cv2.imwrite(filepath, frame)
        print(f"✓ Gambar tersimpan: {filepath}")
        
        results = model(frame)
        plate_text = "Tidak Terdeteksi"
        detection_confidence = 0
        detection_bbox = None
        
        for r in results:
            for box in r.boxes.xyxy:
                x1, y1, x2, y2 = map(int, box)
                detection_bbox = [x1, y1, x2, y2]
                plate_crop = frame[y1:y2, x1:x2]
                
                if plate_crop.size > 0:
                    # ========== OCR DENGAN FILTER UKURAN ==========
                    ocr_result = reader.readtext(plate_crop)
                    
                    # Filter ambil huruf PALING BESAR
                    best_text, best_conf = filter_largest_text(ocr_result)
                    
                    if best_text:
                        plate_text = best_text
                        detection_confidence = best_conf
                        print(f"✓ Plat terdeteksi (huruf besar): {plate_text}")
                        
                        # Format sesuai Indonesia
                        formatted_text = format_indonesian_plate(plate_text)
                        if formatted_text != plate_text:
                            print(f"  → Format Indonesia: {formatted_text}")
                            plate_text = formatted_text
                        
                        break
                    # =============================================
        
        # ========== AUTO SAVE KE DATABASE ==========
        db_saved = False
        if plate_text != "Tidak Terdeteksi":
            db_saved = save_to_database(plate_text, relative_path.replace('\\', '/'))
        # ===========================================
        
        return jsonify({
            'success': True,
            'plate_text': plate_text,
            'confidence': detection_confidence,
            'image_path': relative_path.replace('\\', '/'),
            'filename': filename,
            'date_folder': datetime.datetime.now().strftime("%d-%m-%Y"),
            'timestamp': timestamp,
            'full_path': filepath,
            'bbox': detection_bbox,
            'db_saved': db_saved
        })
        
    except Exception as e:
        print(f"✗ Capture error: {e}")
        return jsonify({'success': False, 'message': f'Error: {str(e)}'}), 500

# ==================== ENDPOINT UNTUK AKSES GAMBAR ====================
@app.route('/captured_photos/<path:filepath>')
def get_captured_photo(filepath):
    try:
        parts = filepath.split('/')
        if len(parts) == 2:
            date_folder, filename = parts
            full_path = os.path.join(CAPTURE_FOLDER, date_folder, filename)
        else:
            full_path = os.path.join(CAPTURE_FOLDER, filepath)
        
        if os.path.exists(full_path) and os.path.isfile(full_path):
            directory = os.path.dirname(full_path)
            file = os.path.basename(full_path)
            return send_from_directory(directory, file)
        else:
            return jsonify({'error': 'File not found'}), 404
    except Exception as e:
        return jsonify({'error': f'Error: {str(e)}'}), 404

# ==================== ENDPOINT LIST GAMBAR ====================
@app.route('/list_captured_photos')
def list_captured_photos():
    try:
        all_files = []
        for date_folder in os.listdir(CAPTURE_FOLDER):
            date_folder_path = os.path.join(CAPTURE_FOLDER, date_folder)
            if os.path.isdir(date_folder_path) and len(date_folder) == 10 and date_folder[2] == '-' and date_folder[5] == '-':
                for filename in os.listdir(date_folder_path):
                    if filename.endswith(('.jpg', '.jpeg', '.png')):
                        filepath = os.path.join(date_folder_path, filename)
                        file_stat = os.stat(filepath)
                        all_files.append({
                            'filename': filename,
                            'date_folder': date_folder,
                            'path': f"captured_photos/{date_folder}/{filename}",
                            'size': file_stat.st_size,
                            'created': datetime.datetime.fromtimestamp(file_stat.st_ctime).strftime("%Y-%m-%d %H:%M:%S"),
                        })
        
        all_files.sort(key=lambda x: x['created'], reverse=True)
        
        return jsonify({
            'success': True,
            'total': len(all_files),
            'files': all_files
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# ==================== ENDPOINT CEK FOLDER TANGGAL ====================
@app.route('/check_date_folder')
def check_date_folder():
    try:
        today = datetime.datetime.now().strftime("%d-%m-%Y")
        date_folder = os.path.join(CAPTURE_FOLDER, today)
        
        if os.path.exists(date_folder):
            files = [f for f in os.listdir(date_folder) if f.endswith(('.jpg', '.jpeg', '.png'))]
            return jsonify({
                'success': True,
                'date': today,
                'folder_exists': True,
                'file_count': len(files),
                'files': files[-10:]
            })
        else:
            return jsonify({
                'success': True,
                'date': today,
                'folder_exists': False,
                'message': 'Folder hari ini belum dibuat'
            })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# ==================== ENDPOINT UNTUK LIHAT DATA PLATES ====================
@app.route('/api/plates')
def get_plates():
    conn = None
    cursor = None
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'success': False, 'error': 'Database connection failed'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM plates ORDER BY created_at DESC")
        plates = cursor.fetchall()
        
        return jsonify({
            'success': True,
            'total': len(plates),
            'data': plates
        })
    except Error as e:
        return jsonify({'success': False, 'error': str(e)}), 500
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

# ==================== ENDPOINT LAINNYA ====================
@app.route('/video_feed')
def video_feed():
    try:
        return Response(generate_frames(),
                       mimetype='multipart/x-mixed-replace; boundary=frame')
    except Exception as e:
        return jsonify({'error': f'Stream error: {str(e)}'}), 500

@app.route('/get_single_frame')
def get_single_frame():
    global camera
    try:
        with camera_lock:
            if camera is None or not camera.isOpened():
                camera, camera_index = init_camera()
                if camera is None:
                    return jsonify({'error': 'No camera available'}), 500
            
            success, frame = camera.read()
            if success:
                ret, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 80])
                if ret:
                    frame_bytes = buffer.tobytes()
                    return Response(frame_bytes, mimetype='image/jpeg')
            
            return jsonify({'error': 'Failed to capture frame'}), 500
    except Exception as e:
        return jsonify({'error': f'Camera error: {str(e)}'}), 500

@app.route('/start_camera_with_stream')
def start_camera_with_stream():
    global camera
    try:
        with camera_lock:
            if camera is None or not camera.isOpened():
                camera, camera_index = init_camera()
                if camera is None:
                    return jsonify({'status': 'error', 'message': 'Failed to start camera'}), 500
                
                for i in range(10):
                    camera.read()
                    time.sleep(0.1)
                
            return jsonify({'status': 'camera_ready', 'message': 'Camera is ready'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': f'Camera start error: {str(e)}'}), 500

@app.route('/check_stream_ready')
def check_stream_ready():
    global camera
    try:
        if camera and camera.isOpened():
            success, frame = camera.read()
            return jsonify({'ready': success})
        else:
            return jsonify({'ready': False})
    except Exception as e:
        return jsonify({'ready': False})

@app.route('/start_camera')
def start_camera():
    global camera
    try:
        with camera_lock:
            if camera is None or not camera.isOpened():
                camera, camera_index = init_camera()
                if camera is not None:
                    for _ in range(5):
                        camera.read()
                    return jsonify({'status': 'camera_started'})
                else:
                    return jsonify({'status': 'error'}), 500
            else:
                return jsonify({'status': 'camera_already_started'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/stop_camera')
def stop_camera():
    global camera
    try:
        with camera_lock:
            if camera and camera.isOpened():
                camera.release()
                camera = None
            return jsonify({'status': 'camera_stopped'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/health')
def health_check():
    global camera
    camera_status = 'active' if camera and camera.isOpened() else 'inactive'
    
    db_status = 'connected' if get_db_connection() else 'disconnected'
    
    return jsonify({
        'status': 'running',
        'camera_status': camera_status,
        'database_status': db_status,
        'timestamp': time.time()
    })

if __name__ == '__main__':
    print("="*60)
    print("🚀 PLAT RECOGNITION API STARTING...")
    print("="*60)
    print(f"📁 Capture folder: {CAPTURE_FOLDER}")
    print(f"🗄️  Database: platrec_laravel")
    print("="*60)
    
    today_folder = get_date_folder_path()
    print(f"📁 Today's folder: {today_folder}")
    print("="*60)
    
    app.run(host='0.0.0.0', port=5000, debug=True, threaded=True)