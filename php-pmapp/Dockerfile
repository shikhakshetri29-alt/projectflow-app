FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# PHP + MySQL server install karo
RUN apt-get update && apt-get install -y \
    php8.1 \
    php8.1-cli \
    php8.1-mysql \
    mysql-server \
    && apt-get clean

WORKDIR /app

COPY . /app/

RUN chmod +x /app/start.sh

EXPOSE 8080

CMD ["/app/start.sh"]
