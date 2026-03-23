from flask import Flask, request, jsonify, Response
from flask_cors import CORS
import cv2
import numpy as np
from ultralytics import YOLO
import easyocr
import base64
import threading
import time
import json

app = Flask(__name__)
CORS(app)

# Global variables for camera
camera = None
camera_lock = threading.Lock()
model = YOLO('epoch50.pt')
reader = easyocr.Reader(['en'])

def init_camera():
    """Initialize camera dengan error handling yang lebih baik"""
    global camera
    try:
        # Coba berbagai camera index
        for camera_index in [0, 1, 2]:
            temp_camera = cv2.VideoCapture(camera_index)
            if temp_camera.isOpened():
                temp_camera.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                temp_camera.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                temp_camera.set(cv2.CAP_PROP_FPS, 30)
                temp_camera.set(cv2.CAP_PROP_BUFFERSIZE, 1)  # Reduce buffer
                
                # Test read frame
                success, frame = temp_camera.read()
                if success:
                    print(f"Camera initialized successfully on index {camera_index}")
                    return temp_camera, camera_index
                else:
                    temp_camera.release()
            else:
                temp_camera.release()
        
        print("No camera device found")
        return None, -1
        
    except Exception as e:
        print(f"Camera initialization error: {e}")
        return None, -1

def generate_frames():
    """Generate camera frames dengan error handling yang robust"""
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
                    print("Failed to read frame, attempting to reinitialize camera...")
                    retry_count += 1
                    with camera_lock:
                        if camera:
                            camera.release()
                            camera = None
                    break
                
                # Reset retry count on successful read
                retry_count = 0
                
                try:
                    # Process detection
                    results = model(frame)
                    annotated_frame = frame.copy()
                    
                    for r in results:
                        annotated_frame = r.plot()
                    
                    # Encode frame to JPEG
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
    
    print("Max retries exceeded in generate_frames")

@app.route('/video_feed')
def video_feed():
    """Video streaming route dengan timeout handling"""
    try:
        return Response(generate_frames(),
                       mimetype='multipart/x-mixed-replace; boundary=frame')
    except Exception as e:
        return jsonify({'error': f'Stream error: {str(e)}'}), 500

@app.route('/get_single_frame')
def get_single_frame():
    """Get single frame for testing dengan camera initialization"""
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
    """Start camera dan tunggu sampai benar-benar ready untuk stream"""
    global camera
    
    try:
        with camera_lock:
            if camera is None or not camera.isOpened():
                camera, camera_index = init_camera()
                if camera is None:
                    return jsonify({
                        'status': 'error', 
                        'message': 'Failed to start camera: No camera device found'
                    }), 500
                
                # Warm up camera dengan lebih banyak frame
                warm_up_frames = 10
                for i in range(warm_up_frames):
                    success, frame = camera.read()
                    if not success:
                        print(f"Warm-up frame {i+1} failed")
                    else:
                        print(f"Warm-up frame {i+1} success")
                    time.sleep(0.1)  # Beri jeda antara frame
                
                print("Camera warm-up completed")
                
            return jsonify({
                'status': 'camera_ready', 
                'message': 'Camera is ready for streaming',
                'camera_index': camera_index if 'camera_index' in locals() else 'unknown'
            })
                
    except Exception as e:
        return jsonify({
            'status': 'error', 
            'message': f'Camera start error: {str(e)}'
        }), 500

@app.route('/check_stream_ready')
def check_stream_ready():
    """Check if camera stream is ready"""
    global camera
    try:
        if camera and camera.isOpened():
            # Test read a frame
            success, frame = camera.read()
            return jsonify({
                'ready': success,
                'message': 'Stream is ready' if success else 'Camera opened but cannot read frames'
            })
        else:
            return jsonify({
                'ready': False,
                'message': 'Camera not initialized'
            })
    except Exception as e:
        return jsonify({
            'ready': False,
            'message': f'Stream check error: {str(e)}'
        })

@app.route('/detect_10_seconds')
def detect_10_seconds():
    """Detect plates for 10 seconds dengan timeout yang lebih panjang"""
    global camera
    
    try:
        with camera_lock:
            if camera is None or not camera.isOpened():
                camera, camera_index = init_camera()
                if camera is None:
                    return jsonify({'success': False, 'error': 'Camera not available'}), 500
        
        all_detections = []
        start_time = time.time()
        frame_count = 0
        
        print("Starting 10-second detection...")
        
        while time.time() - start_time < 10:  # Run for 10 seconds
            success, frame = camera.read()
            if not success:
                print("Frame read failed in detection")
                # Coba baca ulang sekali
                success, frame = camera.read()
                if not success:
                    break
            
            frame_count += 1
            
            try:
                # Process detection
                results = model(frame)
                
                for r in results:
                    # OCR for each detected plate
                    for box in r.boxes.xyxy:
                        x1, y1, x2, y2 = map(int, box)
                        plate_crop = frame[y1:y2, x1:x2]
                        
                        if plate_crop.size > 0:
                            ocr_result = reader.readtext(plate_crop)
                            for (_, text, confidence) in ocr_result:
                                if confidence > 0.3:
                                    # Add timestamp and detection data
                                    detection_data = {
                                        'text': text.upper().replace(' ', ''),
                                        'confidence': float(confidence),
                                        'timestamp': round(time.time() - start_time, 1),
                                        'frame': frame_count
                                    }
                                    all_detections.append(detection_data)
                                    print(f"Detected: {detection_data['text']} (confidence: {confidence:.2f})")
            except Exception as e:
                print(f"Detection processing error: {e}")
                continue
            
            time.sleep(0.05)
        
        print(f"Detection completed. Processed {frame_count} frames, found {len(all_detections)} detections")
        
        # Remove duplicates and return unique detections
        unique_detections = []
        seen_texts = set()
        
        for detection in all_detections:
            clean_text = detection['text']
            if clean_text not in seen_texts and len(clean_text) >= 3:
                seen_texts.add(clean_text)
                unique_detections.append(detection)
        
        return jsonify({
            'success': True,
            'detections': unique_detections,
            'total_detected': len(unique_detections),
            'duration': 10,
            'frames_processed': frame_count
        })
        
    except Exception as e:
        print(f"Detection error: {e}")
        return jsonify({
            'success': False,
            'error': f'Detection error: {str(e)}'
        }), 500

@app.route('/start_camera')
def start_camera():
    """Start camera dengan initialization yang lebih baik"""
    global camera
    
    try:
        with camera_lock:
            if camera is None or not camera.isOpened():
                camera, camera_index = init_camera()
                if camera is not None:
                    # Warm up camera dengan beberapa frame
                    for _ in range(5):
                        camera.read()
                    return jsonify({
                        'status': 'camera_started', 
                        'message': f'Camera started successfully on index {camera_index}',
                        'camera_index': camera_index
                    })
                else:
                    return jsonify({
                        'status': 'error', 
                        'message': 'Failed to start camera: No camera device found'
                    }), 500
            else:
                return jsonify({
                    'status': 'camera_already_started',
                    'message': 'Camera is already running'
                })
                
    except Exception as e:
        return jsonify({
            'status': 'error', 
            'message': f'Camera start error: {str(e)}'
        }), 500

@app.route('/stop_camera')
def stop_camera():
    """Stop camera dan cleanup"""
    global camera
    try:
        with camera_lock:
            if camera and camera.isOpened():
                camera.release()
                camera = None
                print("Camera stopped and released")
            return jsonify({'status': 'camera_stopped'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/health')
def health_check():
    """Health check dengan camera status"""
    global camera
    camera_status = 'active' if camera and camera.isOpened() else 'inactive'
    return jsonify({
        'status': 'Python API is running',
        'camera_status': camera_status,
        'timestamp': time.time()
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True, threaded=True)