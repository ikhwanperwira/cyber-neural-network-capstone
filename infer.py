# pylint: disable=import-error

# import keras
# from keras.layers import Input, Conv2D, MaxPooling2D, UpSampling2D, Dropout, concatenate # type: ignore
from keras.models import load_model  # type: ignore
# from keras.optimizers import Adam # type: ignore
from dotenv import load_dotenv
import cv2
import numpy as np
import os

# pylint: disable=no-member

load_dotenv()

IMAGE_SIZE = (256, 256)

# test path
# test_images_path = './tmp/test/image/tmp.jpg'
# test_labels_path = './tmp/test/label/tmp.jpg'


def preproces(image_filepath):
  # Preprocess image
  image = cv2.imread(image_filepath, cv2.IMREAD_GRAYSCALE)
  image = cv2.resize(image, IMAGE_SIZE)
  image = image.astype(np.float32) / 255.

  # label = cv2.imread(test_labels_path, cv2.IMREAD_GRAYSCALE)
  # label = cv2.resize(label, IMAGE_SIZE)
  # label = label.astype(np.float32) / 255.
  # label[label > 0.5] = 1.0
  # label[label <= 0.5] = 0.0

  return np.array([np.expand_dims(image, axis=2)])

# @markdown 5. Mendefinisikan arsitektur CNN yaitu U-Net.


# def unet(input_size=(256, 256, 1)):
#   inputs = Input(input_size)

#   # Encoder
#   conv1 = Conv2D(64, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(inputs)
#   conv1 = Conv2D(64, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv1)
#   pool1 = MaxPooling2D(pool_size=(2, 2))(conv1)

#   conv2 = Conv2D(128, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(pool1)
#   conv2 = Conv2D(128, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv2)
#   pool2 = MaxPooling2D(pool_size=(2, 2))(conv2)

#   conv3 = Conv2D(256, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(pool2)
#   conv3 = Conv2D(256, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv3)
#   pool3 = MaxPooling2D(pool_size=(2, 2))(conv3)

#   conv4 = Conv2D(512, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(pool3)
#   conv4 = Conv2D(512, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv4)
#   drop4 = Dropout(0.5)(conv4)
#   pool4 = MaxPooling2D(pool_size=(2, 2))(drop4)

#   # Decoder
#   conv5 = Conv2D(1024, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(pool4)
#   conv5 = Conv2D(1024, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv5)
#   drop5 = Dropout(0.5)(conv5)

#   up6 = Conv2D(512, 2, activation='relu', padding='same',
#                kernel_initializer='he_normal')(UpSampling2D(size=(2, 2))(drop5))
#   merge6 = concatenate([drop4, up6], axis=3)
#   conv6 = Conv2D(512, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(merge6)
#   conv6 = Conv2D(512, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv6)

#   up7 = Conv2D(256, 2, activation='relu', padding='same',
#                kernel_initializer='he_normal')(UpSampling2D(size=(2, 2))(conv6))
#   merge7 = concatenate([conv3, up7], axis=3)
#   conv7 = Conv2D(256, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(merge7)
#   conv7 = Conv2D(256, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv7)

#   up8 = Conv2D(128, 2, activation='relu', padding='same',
#                kernel_initializer='he_normal')(UpSampling2D(size=(2, 2))(conv7))
#   merge8 = concatenate([conv2, up8], axis=3)
#   conv8 = Conv2D(128, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(merge8)
#   conv8 = Conv2D(128, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv8)

#   up9 = Conv2D(64, 2, activation='relu', padding='same',
#                kernel_initializer='he_normal')(UpSampling2D(size=(2, 2))(conv8))
#   merge9 = concatenate([conv1, up9], axis=3)
#   conv9 = Conv2D(64, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(merge9)
#   conv9 = Conv2D(64, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv9)
#   conv9 = Conv2D(2, 3, activation='relu', padding='same',
#                  kernel_initializer='he_normal')(conv9)

#   outputs = Conv2D(1, 1, activation='sigmoid')(conv9)

#   model = Model(inputs=inputs, outputs=outputs)
#   model.summary()
#   model.load_weights('keras-model/model-transventricular2.weights.h5')
#   model.compile(optimizer=Adam(learning_rate=1e-4),
#                 loss='binary_crossentropy', metrics=['accuracy'])

#   return model


def postprocess(prediction, preprocessed_image):
  detections = []

  binary_image = (prediction * 255).astype(np.uint8)
  transventricular_image = cv2.cvtColor((preprocessed_image * 255).astype(np.uint8),
                                        cv2.COLOR_GRAY2BGR)

  # Membuat blur dan thres untuk menghilangkan noise.
  # kernel size haruslah (ganjil, ganjl)
  blurredFrame = cv2.GaussianBlur(binary_image, (7, 7), 0)
  _, binary_image = cv2.threshold(blurredFrame, 127, 255, cv2.THRESH_BINARY)

  # Mencari kontur agar dapat mengakses kordinat tepi.
  contours, _ = cv2.findContours(binary_image, cv2.RETR_EXTERNAL,
                                 cv2.CHAIN_APPROX_SIMPLE)

  # Mendapatkan gambar deteksi.
  detected_image = transventricular_image
  biggest_area = 0
  for cnt in contours:

    # calculate the area of the contour
    current_area = cv2.contourArea(cnt)
    if current_area > biggest_area:
      # Mencari nilai minimax koordinat yang digunakan untuk titik mulai dan titik akhir kotak pembatas.
      min_x = min(cnt[:, 0][:, 0]) - 5
      max_x = max(cnt[:, 0][:, 0]) + 5
      min_y = min(cnt[:, 0][:, 1]) - 5
      max_y = max(cnt[:, 0][:, 1]) + 5
      biggest_area = current_area

  # Menggambar kotak pembatas berwarna hijau.
  cv2.rectangle(detected_image, (min_x, min_y),
                (max_x, max_y), (0, 0, 255), 2)

  detections.append(np.array(detected_image))
  cv2.imwrite(os.path.join('./', 'infer_output.jpg'), detected_image)


def infer_image(image_filepath):
  # Preprocess image
  images: np.ndarray[np.Any, np.dtype[np.floating[np.Any]]
                     ] = preproces(image_filepath)
  print(images.shape)

  # load model
  # model = unet()
  model = load_model('keras-model/model-transventricular-v3.keras')

  # Predict image
  prediction = model.predict(images)
  predictions = np.argmax(prediction, axis=1)

  # Mengonversi prediksi dan label ke nilai biner (*binary image*)
  predictions[predictions > 0.5] = 1.0
  predictions[predictions <= 0.5] = 0.0

  postprocess(prediction[0], images[0])


infer_image('input.dat')
