#!/usr/bin/env bash
TMP_DIR=$(mktemp -d -t backups.XXXXXX) || exit 1
mkdir "${TMP_DIR}/app"
echo "Using directory ${TMP_DIR}"

echo "Copying cache"
cp -r cache ${TMP_DIR}/app
echo "Copying templates"
cp -r templates ${TMP_DIR}/app
echo "Copying avatars"
cp -r public/avatars ${TMP_DIR}
echo "Copying screenshots"
cp -r public/screenshots ${TMP_DIR}

cd ${TMP_DIR}
TODAY=`date +%Y-%m-%d`
TAR_FILE="backups-${TODAY}.tar.gz"
echo "Creating ${TAR_FILE}"
tar -czf ${TAR_FILE} *

echo "Copying ${TAR_FILE} to S3"
aws s3 --region=us-east-2 cp ${TAR_FILE} s3://backups.app.blocksedit.com

echo "Cleaning up"
rm -rf ${TMP_DIR}

echo "Done!"
