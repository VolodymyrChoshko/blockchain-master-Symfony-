#!/usr/bin/env bash
if [[ $1 == "i" ]]
then
echo "Deploying to devapp with yarn install"
ssh ubuntu@172.31.100.145 'cd /var/www/devapp.blocksedit.com && git reset --hard master && git pull && composer run post-deploy && yarn install && yarn run build'
elif [[ $1 == "p" ]]
then
echo "Pulling to devapp"
ssh ubuntu@172.31.100.145 'cd /var/www/devapp.blocksedit.com && git reset --hard master && git pull && composer run post-deploy'
else
echo "Deploying to devapp"
ssh ubuntu@172.31.100.145 'cd /var/www/devapp.blocksedit.com && git pull && composer run post-deploy && yarn run build'
fi
