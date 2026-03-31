# Hướng dẫn deploy production (Dtask / Jira Pro)

Tài liệu này hướng dẫn deploy ứng dụng Laravel + Vue 3 lên môi trường production (ví dụ domain **dtask.io.vn**), dùng MySQL.

---

## 1. Frontend có nằm trong `public` không?

**Nguồn frontend** (Vue, CSS) đang nằm trong `resources/js` và `resources/css`, **không** nằm sẵn trong thư mục `public`. Điều này **vẫn dùng bình thường** khi deploy.

Cách hoạt động:

- **Development:** chạy `npm run dev` → Vite dev server (port 5173) phục vụ JS/CSS.
- **Production:** chạy `npm run build` → Vite **build** toàn bộ Vue/CSS ra thư mục **`public/build/`** (file dạng `build/assets/*.js`, `*.css` có hash).
- Trang HTML do Laravel render từ `resources/views/app.blade.php`, dùng directive **`@vite(['resources/css/app.css', 'resources/js/app.js'])`**.
- Khi `APP_ENV=production`, Laravel đọc file **manifest** trong `public/build/.vite/manifest.json` và tự inject đúng đường dẫn file JS/CSS đã build vào HTML.

**Kết luận:** Sau khi chạy `npm run build`, frontend đã nằm trong `public/build` và được phục vụ qua thư mục `public`. Bạn không cần copy thủ công FE vào `public`; chỉ cần build đủ một lần mỗi khi deploy.

---

## 2. Yêu cầu server

- **PHP** >= 8.2 (với extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL)
- **Composer** 2.x
- **Node.js** >= 18 (để chạy `npm run build`)
- **MySQL** >= 8.0 (hoặc MariaDB tương đương)
- **Web server:** Nginx hoặc Apache (chỉ trỏ document root vào thư mục `public`)

---

## 3. Các bước deploy

### 3.1. Clone và cài đặt

```bash
# Clone repo (hoặc upload code)
cd /var/www
git clone <repository-url> dtask
cd dtask
```

### 3.2. Environment

```bash
# Dùng file env production (đã cấu hình MySQL, APP_URL=https://dtask.io.vn)
cp env.prod.example .env
# Chỉnh .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD cho đúng MySQL của bạn
nano .env
```

Trong `.env` cần đảm bảo:

- `APP_URL=https://dtask.io.vn` (hoặc http nếu chưa có SSL)
- **`DB_CONNECTION=mysql`** — project dùng MySQL/MariaDB, không dùng PostgreSQL.
- `DB_HOST`, `DB_PORT` (3306), `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` đúng với MySQL của bạn.

Sinh key ứng dụng:

```bash
php artisan key:generate
```

### 3.3. PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3.4. Frontend build (đưa FE vào `public/build`)

**Chạy trực tiếp trên server (có cài Node/npm):**
```bash
npm ci
npm run build
```

**Khi dùng Docker** (container `app` không có Node — chạy build trong container `vite`):
```bash
docker-compose run --rm vite sh -c "yarn install && yarn build"
```
Hoặc nếu container `vite` đang chạy: `docker-compose exec vite sh -c "yarn build"`.

Sau bước này, trong `public/build/` sẽ có đủ JS/CSS đã build; Laravel sẽ dùng chúng qua `@vite(...)` khi `APP_ENV=production`.

### 3.5. Database

Tạo database và user MySQL (ví dụ):

```sql
CREATE DATABASE dtask CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dtask'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON dtask.* TO 'dtask'@'localhost';
FLUSH PRIVILEGES;
```

Chạy migration:

```bash
php artisan migrate --force
```

### 3.6. Cache và storage

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 3.7. Quyền thư mục

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

(Thay `www-data` bằng user chạy web server nếu khác.)

---

## 4. Cấu hình web server

Document root **bắt buộc** trỏ vào thư mục **`public`** của project (không trỏ vào thư mục gốc). Như vậy mọi request sẽ qua `public/index.php` và Laravel mới load đúng `public/build/` cho frontend.

### 4.1. Nginx (ví dụ)

```nginx
server {
    listen 80;
    server_name dtask.io.vn;
    root /var/www/dtask/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
}
```

Sau khi cấu hình SSL (Let’s Encrypt, v.v.) có thể thêm `listen 443 ssl;` và các directive `ssl_*` tương ứng.

### 4.2. Apache

Đảm bảo `DocumentRoot` trỏ tới **`/var/www/dtask/public`** (hoặc đường dẫn tương ứng), và `mod_rewrite` bật với file `.htaccess` trong `public` (Laravel mặc định đã có).

---

## 5. Sau mỗi lần cập nhật code

```bash
cd /var/www/dtask
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Nếu không đổi dependency JS/CSS, có thể chỉ cần `npm run build` rồi cache lại config/route/view.

---

## 6. Tóm tắt

| Câu hỏi | Trả lời |
|--------|---------|
| FE có nằm trong `public` không? | Nguồn FE nằm ở `resources/`. Sau khi chạy **`npm run build`**, bản build (JS/CSS) nằm trong **`public/build/`** và **được dùng bình thường** thông qua `@vite()` trong `app.blade.php`. |
| Có cần copy tay FE vào `public` không? | Không. Chỉ cần chạy `npm run build` khi deploy / khi đổi frontend. |
| Document root đặt ở đâu? | **Bắt buộc** là thư mục **`public`** của project (chứa `index.php` và thư mục `build/`). |

Nếu làm đúng các bước trên (env, build, migration, document root = `public`), ứng dụng sẽ chạy đầy đủ cả backend Laravel và frontend Vue trên production.

---

## 7. Lưu ý SPA (Vue Router)

Ứng dụng là SPA: mọi đường dẫn như `/login`, `/register`, `/user`, `/user/notes`, `/admin`, `/admin/users` đều do Vue Router xử lý. Laravel cần trả về cùng một view `app` cho các URL này (đã cấu hình trong `routes/web.php`) để khi user refresh hoặc truy cập trực tiếp không bị 404. Nếu thêm route mới ở frontend, nhớ thêm route tương ứng trong `web.php` trả về `view('app')`.
