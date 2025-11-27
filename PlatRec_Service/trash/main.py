# main.py
from flask import Flask, Response, render_template_string
import cv2
import time
from detect import PlatNomorDetector

app = Flask(__name__)

detector = PlatNomorDetector()

def gen_frames():
    # open webcam jika belum
    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        cap.open(0)
    while True:
        ret, frame = cap.read()
        if not ret:
            break

        # gunakan model YOLO untuk deteksi
        results = detector.model(frame)

        texts_in_frame = []
        # ambil anotasi & crop plat
        for r in results:
            annotated = r.plot()  # anotasi frame
            for box in r.boxes.xyxy:
                x1, y1, x2, y2 = map(int, box)
                plate_crop = frame[y1:y2, x1:x2]
                if plate_crop.size == 0:
                    continue
                ocr_result = detector.reader.readtext(plate_crop)
                for (_, text, _) in ocr_result:
                    texts_in_frame.append(text)

            # kalau ada annotated gunakan itu, jika tidak gunakan original
            try:
                frame_rgb = annotated
            except:
                frame_rgb = frame

        # overlay teks OCR di frame
        display = frame_rgb.copy()
        y = 30
        for t in texts_in_frame:
            cv2.putText(display, t, (10, y), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0,255,0), 2)
            y += 30

        # encode ke jpeg
        ret2, buffer = cv2.imencode('.jpg', display)
        frame_bytes = buffer.tobytes()

        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')

        # throttle sedikit
        time.sleep(0.03)

    cap.release()

@app.route('/stream')
def stream():
    return Response(gen_frames(),
                    mimetype='multipart/x-mixed-replace; boundary=frame')

# optional simple page to test
@app.route('/')
def index():
    return render_template_string("""
    <html><body>
      <h3>Camera Stream</h3>
      <img src="/stream" style="max-width:100%;"/>
    </body></html>
    """)

if __name__ == "__main__":
    # host=0.0.0.0 agar bisa diakses dari booted localhost lain; port 5000
    app.run(host='0.0.0.0', port=5000, threaded=True)
