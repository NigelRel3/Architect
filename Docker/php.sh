# from root of project
docker build -f Docker/php/dockerfile -t php:7.4-apache .

docker run --detach -v /home/nigel/eclipse-workspace/Architect:/var/www/html --name ArchPHP php:7.4-apache
