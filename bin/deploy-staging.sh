#!/usr/bin/env bash
if [[ $1 == "i" ]]
then
echo "Deploying to stagingapp with yarn install"
ssh ubuntu@172.31.100.145 'cd /var/www/stagingapp.blocksedit.com && git reset --hard master && git pull && composer run post-deploy && yarn install && yarn run build'
elif [[ $1 == "p" ]]
then
echo "Pulling to stagingapp"
ssh ubuntu@172.31.100.145 'cd /var/www/stagingapp.blocksedit.com && git reset --hard master && git pull && composer run post-deploy'
else
echo "Deploying to stagingapp"
ssh ubuntu@172.31.100.145 'cd /var/www/stagingapp.blocksedit.com && git reset --hard master && git pull && composer run post-deploy && yarn run build'
fi
