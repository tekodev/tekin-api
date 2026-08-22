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
# GÜVENLİK/Trivy: linux-libc-dev'i (kernel-header, serversideup base'inde gelen build artığı) KALDIR —
# container KENDİ kernel'ini çalıştırmaz -> runtime'da GEREKSİZ. Trivy'de recurring kernel-CVE üretiyor
# (CVE-2026-53399/-64600...) = whack-a-mole; kaldırmak kalıcı bitirir + imajı küçültür. kolayzeka'da doğrulandı.
USER root
# GUVENLIK/Trivy (2): taban imajdaki OS paketlerini guvenlik yamalariyla YUKSELT. serversideup base'i
# donemsel yayinlanir -> arada Debian advisory cikar (ornek: util-linux CVE-2026-53612/13/14/15 = mount
# TOCTOU + SUID mount ile nosuid/noexec bypass; 9 pakete yayilmis 36 fixable HIGH). Yamalari build'de
# almazsak Trivy blocking-gate'i HER deploy'u durdurur; tek tek .trivyignore = whack-a-mole (yukaridaki
# linux-libc-dev dersinin aynisi). Sessiz guvenlik regresyonu olmasin diye set -eu ile GURULTULU fail eder.
RUN set -eu; \
    apt-get update >/dev/null; \
    apt-get purge -y --auto-remove linux-libc-dev >/dev/null 2>&1 || true; \
    DEBIAN_FRONTEND=noninteractive apt-get upgrade -y >/dev/null; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*
USER www-data
# Container açılışında prod optimizasyonları (env geldikten SONRA). Migration YOK: DB restore şemayı sağlar.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    AUTORUN_LARAVEL_CONFIG_CACHE=true \
    AUTORUN_LARAVEL_ROUTE_CACHE=true \
    AUTORUN_LARAVEL_VIEW_CACHE=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000
