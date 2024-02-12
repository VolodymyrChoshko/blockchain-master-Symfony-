#!/usr/bin/env bash

FILE="config/version.yaml"
COMMIT=$(git rev-parse --short HEAD)
echo "Updating version to ${COMMIT}"

echo "# This is an auto generated file that will be updated at every deploy" > ${FILE}
echo "version: \"${COMMIT}\"" >> ${FILE}
