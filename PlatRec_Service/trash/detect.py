# app/core/deteksi.py
import threading
from ultralytics import YOLO
import cv2
import torch
from PIL import Image, ImageTk
import easyocr

class PlatNomorDetector:
    def __init__(self):
        # Cek GPU
        if torch.cuda.is_available():
            print("GPU tersedia!")
        else:
            print("GPU tidak tersedia, menggunakan CPU.")
            
        # Load model
        self.model = YOLO('epoch50.pt')
        self.reader = easyocr.Reader(['en'])
        self.running = False
        self.cap = None
    
    def start_detection(self, camera_label, status_label, ocr_label):
        """Start detection"""
        if not self.running:
            self.running = True
            self.cap = cv2.VideoCapture(0)
            status_label.config(text="Status: Deteksi dimulai!")
            
            # Start thread
            t = threading.Thread(target=self._process_frames, 
                               args=(camera_label, ocr_label))
            t.daemon = True
            t.start()
    
    def stop_detection(self, status_label):
        """Stop detection"""
        if self.running:
            self.running = False
            if self.cap:
                self.cap.release()
            status_label.config(text="Status: Deteksi dihentikan!")
    
    def _process_frames(self, camera_label, ocr_label):
        """Process frames"""
        if not self.cap.isOpened():
            ocr_label.config(text="Error: Tidak dapat membuka kamera.")
            return

        while self.running:
            ret, frame = self.cap.read()
            if not ret:
                break

            results = self.model(frame)
            texts_in_frame = []

            for r in results:
                annotated_frame = r.plot()
                # Crop plat nomor untuk OCR
                for box in r.boxes.xyxy:
                    x1, y1, x2, y2 = map(int, box)
                    plate_crop = frame[y1:y2, x1:x2]
                    ocr_result = self.reader.readtext(plate_crop)
                    for (_, text, _) in ocr_result:
                        texts_in_frame.append(text)

                # Convert untuk GUI
                frame_rgb = cv2.cvtColor(annotated_frame, cv2.COLOR_BGR2RGB)
                img = Image.fromarray(frame_rgb)
                imgtk = ImageTk.PhotoImage(image=img)
                
                # Update GUI
                camera_label.after(0, lambda: self._update_gui(
                    camera_label, imgtk, ocr_label, texts_in_frame
                ))

        self.cap.release()
    
    def _update_gui(self, camera_label, imgtk, ocr_label, texts):
        """Update GUI components"""
        camera_label.imgtk = imgtk
        camera_label.config(image=imgtk)
        
        if texts:
            ocr_label.config(text="\n".join(texts))
        else:
            ocr_label.config(text="-")