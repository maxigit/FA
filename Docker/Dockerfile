FROM php:5.3-apache

#RUN docker-php-ext-install pdo_mysql
#RUN docker-php-ext-install mysql mysqli

#RUN apt-get update && apt-get install -y \
#	php5-memcache   \
#	&& cp /usr/lib/php5/20131226/*.so  \
#	      /usr/local/lib/php/extensions/no-debug-non-zts-20131226



# Install mysql client to connect to the database easily
RUN apt-get update && apt-get install -y mysql-client

#RUN apt-get update && apt-get install -y \
#    libpng12-dev libfreetype6-dev libjpeg62-turbo-dev \
#    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
#    && docker-php-ext-install gd
#
# Enable Apache rewrite mod
RUN a2enmod rewrite
# Make drush use local php.ini
COPY php.ini /usr/local/etc/php/php.ini



#RUN docker-php-ext-install opcache

# memcache
# sendmail
RUN apt-get update && apt-get install -y sendmail
RUN apt-get update && apt-get install -y vim net-tools
RUN apt-get update && apt-get install -y telnet 
