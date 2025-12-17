# Laravel Sail Docker Setup

Bu proje Laravel Sail kullanarak Docker'da çalıştırılabilir.

## Gereksinimler

-   Docker
-   Docker Compose

## Kurulum

1. `.env` dosyanızın doğru yapılandırıldığından emin olun:

    ```
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=tekindb
    DB_USERNAME=sail
    DB_PASSWORD=password
    ```

2. Docker container'larını başlatın:

    ```bash
    ./vendor/bin/sail up -d
    ```

3. Uygulama anahtarını oluşturun (eğer yoksa):

    ```bash
    ./vendor/bin/sail artisan key:generate
    ```

4. Veritabanı migration'larını çalıştırın:

    ```bash
    ./vendor/bin/sail artisan migrate
    ```

5. (Opsiyonel) Veritabanı seed'lerini çalıştırın:

    ```bash
    ./vendor/bin/sail artisan db:seed
    ```

6. Storage linkini oluşturun:

    ```bash
    ./vendor/bin/sail artisan storage:link
    ```

## Kullanım

-   Uygulama: http://localhost (varsayılan port 80)
-   MySQL: localhost:3306
-   Redis: localhost:6379

## Temel Komutlar

### Container Yönetimi

-   Container'ları başlat: `./vendor/bin/sail up -d`
-   Container'ları durdur: `./vendor/bin/sail down`
-   Container'ları yeniden başlat: `./vendor/bin/sail restart`
-   Logları görüntüle: `./vendor/bin/sail logs -f`

### Artisan Komutları

-   Migration çalıştır: `./vendor/bin/sail artisan migrate`
-   Seed çalıştır: `./vendor/bin/sail artisan db:seed`
-   Cache temizle: `./vendor/bin/sail artisan cache:clear`
-   Tüm artisan komutları: `./vendor/bin/sail artisan [komut]`

### Composer Komutları

-   Paket yükle: `./vendor/bin/sail composer require [paket]`
-   Paket güncelle: `./vendor/bin/sail composer update`
-   Tüm composer komutları: `./vendor/bin/sail composer [komut]`

### NPM/Node Komutları

-   NPM install: `./vendor/bin/sail npm install`
-   NPM run dev: `./vendor/bin/sail npm run dev`
-   Tüm npm komutları: `./vendor/bin/sail npm [komut]`

### Container'a Giriş

-   Bash shell: `./vendor/bin/sail shell`
-   Root shell: `./vendor/bin/sail root-shell`

## Sorun Giderme

-   Container'ları yeniden başlatmak için: `./vendor/bin/sail restart`
-   Veritabanını sıfırlamak için: `./vendor/bin/sail down -v` (dikkat: tüm veriler silinir)
-   Tüm container'ları ve volume'ları temizlemek için: `./vendor/bin/sail down -v --remove-orphans`

## Notlar

-   İlk çalıştırmada container'ların build edilmesi biraz zaman alabilir
-   Port çakışması yaşarsanız `.env` dosyasında `APP_PORT` değerini değiştirebilirsiniz
-   Veritabanı bağlantısı için `DB_HOST=mysql` kullanılmalıdır (container adı)
