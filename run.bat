@echo off
rem Поднимает приложение целиком: БД, зависимости, миграции, веб-сервер.
rem Ненулевой код возврата - на любом шаге, включая недоступный сайт в конце.
setlocal
if "%APP_PORT%"=="" set APP_PORT=8080

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

docker compose run --rm php php yii migrate --interactive=0
if errorlevel 1 goto :fail_migrate

docker compose up -d php
if errorlevel 1 goto :fail_app

echo ожидание http://localhost:%APP_PORT%/ ...
set tries=0
:waithttp
curl -fsS http://localhost:%APP_PORT%/ >nul 2>&1
if not errorlevel 1 goto :httpok
set /a tries+=1
if %tries% geq 30 goto :fail_http
timeout /t 1 /nobreak >nul
goto :waithttp
:httpok

rem Отдельная проверка pretty URL: без router.php встроенный сервер PHP отдаёт
rem на такой путь 404, а корень при этом продолжает отвечать двумястами.
curl -fsS http://localhost:%APP_PORT%/book/index >nul 2>&1
if errorlevel 1 goto :fail_route

echo готово: http://localhost:%APP_PORT%/
exit /b 0

:fail_db
echo run: не удалось запустить контейнер БД 1>&2
exit /b 1
:fail_wait
echo run: MySQL не поднялся за 60 секунд 1>&2
exit /b 1
:fail_composer
echo run: composer install 1>&2
exit /b 1
:fail_migrate
echo run: миграции 1>&2
exit /b 1
:fail_app
echo run: не удалось запустить контейнер приложения 1>&2
exit /b 1
:fail_route
echo run: маршрут /book/index не отдаёт список книг - проверь urlManager 1>&2
exit /b 1
:fail_http
echo run: приложение не отвечает на http://localhost:%APP_PORT%/ 1>&2
exit /b 1
