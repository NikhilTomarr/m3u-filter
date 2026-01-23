FROM vercel/php:8.3
COPY . /app
WORKDIR /app
CMD ["php", "-S", "0.0.0.0:3000"]
