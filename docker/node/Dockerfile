FROM node:20-alpine

# Bật Corepack để dùng Yarn (có sẵn trong Node 16+)
RUN corepack enable && corepack prepare yarn@stable --activate

WORKDIR /var/www

EXPOSE 5173

# Cài deps + chạy Vite dev server (volume mount sẽ có source)
CMD ["sh", "-c", "yarn install && yarn dev"]
