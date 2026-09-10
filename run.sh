#!/bin/sh
# Поднимает приложение целиком: БД, зависимости, миграции, веб-сервер.
# Ненулевой код возврата — на любом шаге, включая недоступный сайт в конце.
set -u

APP_PORT="${APP_PORT:-8080}"
die() { echo "run: $1" >&2; exit 1; }

docker compose up -d db || die "не удалось запустить контейнер БД"

echo "ожидание MySQL..."
i=0
until docker compose exec -T db mysqladmin ping -h 127.0.0.1 -uroot -psecret --silent >/dev/null 2>&1; do
    i=$((i + 1))
    [ "$i" -ge 60 ] && die "MySQL не поднялся за 60 секунд"
    sleep 1
done

docker compose run --rm php composer install --no-interaction --no-progress || die "composer install"
docker compose run --rm php php yii migrate --interactive=0 || die "миграции"
docker compose up -d php || die "не удалось запустить контейнер приложения"

echo "ожидание http://localhost:$APP_PORT/ ..."
i=0
until curl -fsS "http://localhost:$APP_PORT/" >/dev/null 2>&1; do
    i=$((i + 1))
    [ "$i" -ge 30 ] && die "приложение не отвечает на http://localhost:$APP_PORT/"
    sleep 1
done

# Отдельная проверка pretty URL: корень отвечает двумястами и при сломанном
# urlManager, а этот путь - нет.
curl -fsS "http://localhost:$APP_PORT/book/index" 2>/dev/null | grep -q '<h1>Книги'     || die "маршрут /book/index не отдаёт список книг - проверь urlManager"

echo "готово: http://localhost:$APP_PORT/"
