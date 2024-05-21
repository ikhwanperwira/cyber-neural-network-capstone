import os
import gdown

# check whether keras model already downloaded
KERAS_MODEL_PATH = 'keras-model/model-transventricular-v3.keras'
GDRIVE_ID = '1--CnWO_ThAWgFVeoB9Esag1qy2SSB6Mu'

# # check whether keras model already downloaded (dummy)
# KERAS_MODEL_PATH = 'keras-model-foo/yeah.jpg'
# GDRIVE_ID = '1dnvWM44sJ8s5_TPHymlL7ebqhOMeipfS'

# check is file exists
if not os.path.exists(KERAS_MODEL_PATH):

  # create parent directory
  os.makedirs(os.path.dirname(KERAS_MODEL_PATH), exist_ok=True)
  os.makedirs('tmp', exist_ok=True)

  # download model from google drive
  gdown.download(id=GDRIVE_ID, output=KERAS_MODEL_PATH, quiet=False)
else:
  print(f"Model already exists in {KERAS_MODEL_PATH}, skipping model init.")
