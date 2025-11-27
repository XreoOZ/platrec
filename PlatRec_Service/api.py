from flask import Flask, request, jsonify, Response
from flask_cors import CORS
import cv2
import numpy as np
from ultralytics import YOLO
import easyocr
import base64
import threading
import time

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
            break
        
        # Process detection
        results = model(frame)
        detected_texts = []
        
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
        ret, buffer = cv2.imencode('.jpg', annotated_frame if 'annotated_frame' in locals() else frame)
        frame_bytes = buffer.tobytes()
        
        # Yield frame with detection data
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n'
               b'X-Detection-Data: ' + ",".join(detected_texts).encode() + b'\r\n\r\n' + 
               frame_bytes + b'\r\n')

@app.route('/video_feed')
def video_feed():
    """Video streaming route"""
    return Response(generate_frames(),
                   mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/start_camera')
def start_camera():
    """Start camera"""
    global camera
    with camera_lock:
        if camera is None:
            camera = cv2.VideoCapture(0)
    return jsonify({'status': 'camera_started'})

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