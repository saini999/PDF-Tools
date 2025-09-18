import cv2, sys, json

img_path = sys.argv[1]
output_json = sys.argv[2]

face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + "haarcascade_frontalface_default.xml")

img = cv2.imread(img_path)
gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

faces = face_cascade.detectMultiScale(gray, 1.3, 5)

if len(faces) > 0:
    x,y,w,h = faces[0]
    coords = {"x": int(x), "y": int(y), "width": int(w), "height": int(h)}
    with open(output_json, "w") as f:
        f.write(json.dumps(coords))
else:
    with open(output_json, "w") as f:
        f.write(json.dumps({"error": "No face found"}))
