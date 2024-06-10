import os
from botocore.exceptions import ClientError
import logging
import boto3

# AWS credentials
access_key_id = 'AKIA4MTWND2JNCKJ7QQA'
secret_access_key = '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'
region = 'ap-southeast-1'


def upload_file(file_name, bucket, object_name=None):
  """Upload a file to an S3 bucket

  :param file_name: File to upload
  :param bucket: Bucket to upload to
  :param object_name: S3 object name. If not specified then file_name is used
  :return: True if file was uploaded, else False
  """

  # If S3 object_name was not specified, use file_name
  if object_name is None:
    object_name = os.path.basename(file_name)

  # Upload the file
  s3_client = boto3.client('s3', aws_access_key_id=access_key_id,
                           aws_secret_access_key=secret_access_key, region_name=region)
  try:
    response = s3_client.upload_file(file_name, bucket, object_name)
  except ClientError as e:
    logging.error(e)
    return False

  logging.info(
      "File uploaded to %s/%s with response: %s", bucket, object_name, response)
  return True
