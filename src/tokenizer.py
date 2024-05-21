import jwt
from jwt import PyJWTError
from datetime import datetime, timedelta
from dotenv import load_dotenv
import os

load_dotenv()


def generate_token(data: dict, expires_delta: timedelta = None):
  to_encode = data.copy()
  if expires_delta:
    expire = datetime.now() + expires_delta
  else:
    expire = datetime.now() + timedelta(minutes=15)
  to_encode.update({"exp": expire})
  encoded_jwt = jwt.encode(
      to_encode, os.environ['SECRET_KEY'], algorithm="HS256")
  return encoded_jwt


def validate_token(token: str):
  try:
    # Decode the token using the same secret key and algorithm
    decoded_jwt = jwt.decode(
        token, os.environ['SECRET_KEY'], algorithms=["HS256"])
    return decoded_jwt
  except PyJWTError as exc:
    # If the token is not valid or expired, raise an exception
    raise ValueError("Invalid token") from exc
