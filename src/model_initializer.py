import os
import gdown
import logging


def init_model():
  logging.info("Initializing model...")
  # check whether keras model already downloaded
  KERAS_MODEL_PATH = 'keras-model/model-transventricular-v3.keras'
  GDRIVE_ID = '1--CnWO_ThAWgFVeoB9Esag1qy2SSB6Mu'

  logging.info("Checking model at %s...", KERAS_MODEL_PATH)
  # check is file exists
  if not os.path.exists(KERAS_MODEL_PATH):

    logging.info("Creating parent directory...")
    # create parent directory
    os.makedirs(os.path.dirname(KERAS_MODEL_PATH), exist_ok=True)
    os.makedirs('tmp', exist_ok=True)

    logging.info("Downloading model from Google Drive...")
    # download model from google drive
    gdown.download(id=GDRIVE_ID, output=KERAS_MODEL_PATH, quiet=False)
  else:
    logging.info(
        "Model already exists in %s, skipping model init.", KERAS_MODEL_PATH)
