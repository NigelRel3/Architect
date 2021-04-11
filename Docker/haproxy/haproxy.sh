# Build initial image
#docker build -t haproxy .
# Test the config works
#docker run -it -v /home/nigel/workspace/Architecture/Docker/haproxy:/usr/local/etc/haproxy:ro --rm --name haproxy-syntax-check haproxy haproxy -c -f /usr/local/etc/haproxy/haproxy.cfg
# Final container
docker run -d --name haproxy -v /home/nigel/workspace/Architecture/Docker/haproxy:/usr/local/etc/haproxy:ro haproxy