from requests import post
from requests.exceptions import HTTPError


def upload_temporary_file(file_path):
  url = "https://tmpfiles.org/api/v1/upload"
  files = {'file': open(file_path, 'rb')}
  response = post(url, files=files, timeout=10)
  if response.status_code == 200:
    return response.json()['data']['url'].replace('.org/', '.org/dl/')
  raise HTTPError('Error di upload_temporary_file karena kode bukan 200')
