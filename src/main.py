from fastapi import FastAPI, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv
import logging
from infer import infer_image
from model_initializer import init_model
from awsutils import upload_file
import hashlib
import os


def md5sum(filename):
  md5 = hashlib.md5()
  with open(filename, 'rb') as f:
    for chunk in iter(lambda: f.read(128 * md5.block_size), b''):
      md5.update(chunk)
  return md5.hexdigest()


load_dotenv()


app = FastAPI()

# Add middleware to enable CORS
app.add_middleware(
    CORSMiddleware,
    # This allows requests from any origin, you can specify specific origins if needed
    allow_origins=["*"],
    allow_credentials=True,
    # Allow the required HTTP methods
    allow_methods=["GET", "POST", "PUT", "DELETE"],
    # Allow the required headers
    allow_headers=["Authorization", "Content-Type"],
)


@ app.post('/infer')
async def infer(file: UploadFile):
  init_model()

  # store image on disk
  with open('tmp/infer_input.dat', 'wb') as f:
    f.write(file.file.read())

  # infer the image (stored in tmp/infer_output.jpg)
  infer_image('tmp/infer_input.dat')

  # rename infer_output.jpg to md5.jpg
  md5 = md5sum('tmp/infer_output.jpg')
  os.rename('tmp/infer_output.jpg', f'tmp/{md5}.jpg')

  # upload the infer_output.jpg to s3
  is_success = upload_file(f'tmp/{md5}.jpg', 'imagecnnfiles', f'{md5}.jpg')

  return {"detail": "Infer berhasil!", "is_sucesss": is_success, "md5": md5}

if __name__ == '__main__':
  logging.basicConfig(level=logging.INFO)
