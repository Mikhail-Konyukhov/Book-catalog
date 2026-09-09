@echo off
rem Прогон тестов на отдельной базе infotech_test. Код возврата - от codeception.
setlocal

docker compose up -d db
if errorlevel 1 goto :fail_db

echo ожидание MySQL...
set tries=0
:waitdb
docker compose exec -T db mysqladmin ping -h 127.0.0.1 -uroot -psecret --silent >nul 2>&1
if not errorlevel 1 goto :dbok
set /a tries+=1
if %tries% geq 60 goto :fail_wait
timeout /t 1 /nobreak >nul
goto :waitdb
:dbok

docker compose run --rm php composer install --no-interaction --no-progress
if errorlevel 1 goto :fail_composer

rem fresh, а не migrate: сьюты идут с cleanup: false, и прерванный прогон
rem оставляет строки в infotech_test, на которых следующий падает дублями PK.
docker compose run --rm php php tests/Support/bin/yii migrate/fresh --interactive=0
if errorlevel 1 goto :fail_migrate

docker compose run --rm php vendor/bin/codecept run Unit,Functional
exit /b %errorlevel%

:fail_db
echo test: не удалось запустить контейнер БД 1>&2
exit /b 1
:fail_wait
echo test: MySQL не поднялся за 60 секунд 1>&2
exit /b 1
:fail_composer
echo test: composer install 1>&2
exit /b 1
:fail_migrate
echo test: миграции тестовой базы 1>&2
exit /b 1
