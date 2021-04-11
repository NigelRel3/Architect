# Run a docker container for RabbitMQ - with management interface (guest)
docker run -d --hostname mq --name RabbitMQ -p 5672:5672 -p 15672:15672 rabbitmq:3-management

