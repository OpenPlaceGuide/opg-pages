FROM cgr.dev/chainguard/wolfi-base:latest

WORKDIR /var/www/html

COPY . .

RUN apk add --no-cache php-8.1

CMD [ "php", "./test_error.php" ]
