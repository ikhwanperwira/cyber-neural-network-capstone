from fastapi import FastAPI, HTTPException, UploadFile, Header
from fastapi.middleware.cors import CORSMiddleware
from typing import Annotated
from pydantic import BaseModel
from tokenizer import generate_token, validate_token
from datetime import timedelta
from fastapi.logger import logger
# import os
from dotenv import load_dotenv
import logging
from infer import infer_image
from uploader import upload_temporary_file
from model_initializer import init_model

load_dotenv()

app = FastAPI()

# Add middleware to enable CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # This allows requests from any origin, you can specify specific origins if needed
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE"],  # Allow the required HTTP methods
    allow_headers=["Authorization", "Content-Type"],  # Allow the required headers
)


class User(BaseModel):
  username: str
  password: str


@app.post('/login')
async def login(user: User):
  username = user.username
  password = user.password

  # Logging the login request
  logger.info("User %s is trying to login", username)

  # Add your login logic here
  if username == 'rumah_sakit_gws' and password == 'aamiin':
    return {
        "detail": "Login berhasil!",
        "token": generate_token({"username": username}, timedelta(minutes=15))
    }

  logger.info("User %s failed to login", username)

  # Raise HTTPException with status code 401 for unauthorized
  raise HTTPException(status_code=401, detail="Login gagal!")


@app.post('/infer')
async def infer(authorization: Annotated[str, Header()], file: UploadFile):

  init_model()

  try:
    # read token from Authorization header
    token = authorization.split('Bearer ')[1]
    decoded_jwt = validate_token(token)
  except Exception as exc:
    raise HTTPException(status_code=401, detail="Token tidak valid!") from exc

  username = decoded_jwt['username']

  # Log fastapi of logged username with fastapi log utils
  logger.info("User %s is trying to upload a file", username)

  # store image on disk
  with open('tmp/infer_input.dat', 'wb') as f:
    f.write(file.file.read())

  # infer the image (stored in tmp/infer_output.jpg)
  infer_image('tmp/infer_input.dat')

  infer_result = upload_temporary_file('tmp/infer_output.jpg')

  return {"detail": "Infer berhasil!", "url": infer_result}
  # return {"detail": "Upload berhasil!"}


if __name__ == '__main__':
  logging.basicConfig(level=logging.INFO)
