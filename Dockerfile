# lara-s-cms — Laravel 12 CMS/API · php-fpm + nginx (serversideup, non-root, prod)
# Asset'ler pre-built (public/js, public/css committed) → npm build YOK.

# ---- vendor: composer bağımlılıkları (dev'siz, optimize autoload) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY . .
# --ignore-platform-reqs: composer:2 imajında gd/vs yok ama runtime (serversideup) hepsini içerir.
# Lock commit'li → 'install' kilitli sürümleri kurar (yeniden çözmez), platform check'i atlamak güvenli.
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts --ignore-platform-reqs

# ---- runtime: php-fpm + nginx (root'suz, Laravel eklentileri hazır, port 8080) ----
FROM serversideup/php:8.3-fpm-nginx AS run
# Uygulama + vendor (www-data'ya ait)
COPY --chown=www-data:www-data --from=vendor /app /var/www/html
# GÜVENLİK (webshell defense-in-depth): serversideup /storage/*.php'yi zaten engelliyor (php handler'dan
# ÖNCE, doğru location sırasında). Aynı bloğu /uploads'u da kapsayacak şekilde genişlet — nginx .htaccess'i
# yoksaydığı için şart. Yüklenen dosya .php olsa bile 403 döner, çalışmaz.
# Assertion: template değişip sed no-op olursa build FAIL etsin (sessiz güvenlik regresyonu YOK).
RUN sed -i 's#\^/storage/#^/(storage|uploads)/#g' \
      /etc/nginx/site-opts.d/http.conf.template \
      /etc/nginx/site-opts.d/https.conf.template \
 && grep -q '(storage|uploads)' /etc/nginx/site-opts.d/http.conf.template \
 && grep -q '(storage|uploads)' /etc/nginx/site-opts.d/https.conf.template
# Container açılışında prod optimizasyonları (env geldikten SONRA). Migration YOK: DB restore şemayı sağlar.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    AUTORUN_LARAVEL_CONFIG_CACHE=true \
    AUTORUN_LARAVEL_ROUTE_CACHE=true \
    AUTORUN_LARAVEL_VIEW_CACHE=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true
