#!/bin/bash

# install packages for the server component
sudo apt-get install python-software-properties
sudo add-apt-repository ppa:nginx/stable
sudo add-apt-repository ppa:ondrej/php5

sudo apt-get update && sudo apt-get upgrade
sudo apt-get install nginx-extras mysql-client mysql-server python-mysql.connector php5-common php5-fpm php5-dev php5-mysql php5-curl php5-gd php5-intl php-pear php5-imagick php5-imap php5-mcrypt php5-ming php5-ps php5-pspell php5-recode php5-snmp php5-sqlite php5-tidy php5-xsl php5-apcu

sudo apt-get -y install python-pip

# make log directory
mkdir log

# the submission dir must correspond to the UPLOADS parameter in web/app/config/globals.ini
# and php process must be able to mkdir and r+w files in it
mkdir submissions
sudo usermod -a -G www-data $(whoami)
sudo chgrp www-data submissions
chmod 770 submissions

cd web
# create tmp dir for the web program
mkdir tmp
mkdir -p tmp/cache
chmod 775 tmp
chmod 755 tmp/cache

cd ..

sudo ln -s `pwd`/web/nginx.conf /etc/nginx/sites-enabled/autograder
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -s reload

cp grader/grader.json.def grader/grader.json
cp web/app/config/globals.ini.def web/app/config/globals.ini

echo "To set up the MySQL database, cd to utils/dump/, change the MySQL root password in import.sh, and run it. Update the credentials according to its response."
