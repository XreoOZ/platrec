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

def generate_frames():
    """Generate camera frames with plate detection"""
    global camera
    
    with camera_lock:
        if camera is None or not camera.isOpened():
            camera = cv2.VideoCapture(0)
            camera.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            camera.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
    
    while True:
        success, frame = camera.read()
        if not success:
            print("Failed to read frame from camera")
            break
        
        # Process detection
        results = model(frame)
        detected_texts = []
        annotated_frame = frame.copy()  # Default to original frame
        
        for r in results:
            annotated_frame = r.plot()
            
            # OCR for each detected plate
            for box in r.boxes.xyxy:
                x1, y1, x2, y2 = map(int, box)
                plate_crop = frame[y1:y2, x1:x2]
                
                if plate_crop.size > 0:
                    ocr_result = reader.readtext(plate_crop)
                    for (_, text, confidence) in ocr_result:
                        if confidence > 0.5:
                            detected_texts.append(text)
        
        # Encode frame to JPEG
        ret, buffer = cv2.imencode('.jpg', annotated_frame)
        if not ret:
            print("Failed to encode frame")
            continue
            
        frame_bytes = buffer.tobytes()
        
        # Yield frame with detection data
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + 
               frame_bytes + b'\r\n')

@app.route('/video_feed')
def video_feed():
    """Video streaming route"""
    return Response(generate_frames(),
                   mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/get_single_frame')
def get_single_frame():
    """Get single frame for testing"""
    global camera
    
    with camera_lock:
        if camera is None or not camera.isOpened():
            camera = cv2.VideoCapture(0)
    
    success, frame = camera.read()
    if success:
        ret, buffer = cv2.imencode('.jpg', frame)
        if ret:
            frame_bytes = buffer.tobytes()
            return Response(frame_bytes, mimetype='image/jpeg')
    
    return jsonify({'error': 'Failed to capture frame'}), 500

@app.route('/detect_10_seconds')
def detect_10_seconds():
    """Detect plates for 10 seconds and return all results"""
    global camera
    
    with camera_lock:
        if camera is None or not camera.isOpened():
            camera = cv2.VideoCapture(0)
            camera.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            camera.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
    
    all_detections = []
    start_time = time.time()
    
    while time.time() - start_time < 10:  # Run for 10 seconds
        success, frame = camera.read()
        if not success:
            break
        
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
                        if confidence > 0.5:
                            # Add timestamp and detection data
                            detection_data = {
                                'text': text,
                                'confidence': float(confidence),
                                'timestamp': time.time() - start_time,
                                'time_remaining': 10 - (time.time() - start_time)
                            }
                            all_detections.append(detection_data)
        
        time.sleep(0.1)  # Small delay to prevent overloading
    
    # Remove duplicates and return unique detections
    unique_detections = []
    seen_texts = set()
    
    for detection in all_detections:
        if detection['text'] not in seen_texts:
            seen_texts.add(detection['text'])
            unique_detections.append(detection)
    
    return jsonify({
        'success': True,
        'detections': unique_detections,
        'total_detected': len(unique_detections),
        'duration': 10
    })

@app.route('/start_camera')
def start_camera():
    """Start camera"""
    global camera
    with camera_lock:
        if camera is None:
            camera = cv2.VideoCapture(0)
            if camera.isOpened():
                return jsonify({'status': 'camera_started', 'message': 'Camera started successfully'})
            else:
                return jsonify({'status': 'error', 'message': 'Failed to start camera'}), 500
    return jsonify({'status': 'camera_already_started'})

@app.route('/stop_camera')
def stop_camera():
    """Stop camera"""
    global camera
    with camera_lock:
        if camera and camera.isOpened():
            camera.release()
            camera = None
    return jsonify({'status': 'camera_stopped'})

@app.route('/health')
def health_check():
    return jsonify({'status': 'Python API is running'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)