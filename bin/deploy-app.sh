#!/usr/bin/env bash
if [[ $1 == "i" ]]
then
echo "Deploying to app with yarn install"
ssh ubuntu@172.31.100.96 'cd /var/www/app.blocksedit.com && git reset --hard master && git pull && composer run post-deploy && yarn install && yarn run build'
elif [[ $1 == "p" ]]
then
echo "Pulling to app"
ssh ubuntu@172.31.100.96 'cd /var/www/app.blocksedit.com && git reset --hard master && git pull && composer run post-deploy'
else
echo "Deploying to app"
ssh ubuntu@172.31.100.96 'cd /var/www/app.blocksedit.com && git reset --hard master && git pull && composer run post-deploy && yarn run build'
fi
