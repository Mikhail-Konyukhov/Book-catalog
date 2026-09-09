#!/bin/sh
# Прогон тестов на отдельной базе infotech_test. Код возврата — от codeception.
set -u

die() { echo "test: $1" >&2; exit 1; }

docker compose up -d db || die "не удалось запустить контейнер БД"

echo "ожидание MySQL..."
i=0
until docker compose exec -T db mysqladmin ping -h 127.0.0.1 -uroot -psecret --silent >/dev/null 2>&1; do
    i=$((i + 1))
    [ "$i" -ge 60 ] && die "MySQL не поднялся за 60 секунд"
    sleep 1
done

docker compose run --rm php composer install --no-interaction --no-progress || die "composer install"
# fresh, а не migrate: сьюты идут с cleanup: false, и прерванный прогон
# оставляет строки в infotech_test, на которых следующий падает дублями PK.
docker compose run --rm php php tests/Support/bin/yii migrate/fresh --interactive=0 || die "миграции тестовой базы"
docker compose run --rm php vendor/bin/codecept run Unit,Functional
