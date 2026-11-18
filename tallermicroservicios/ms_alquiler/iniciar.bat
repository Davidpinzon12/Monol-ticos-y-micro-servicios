@echo off
set PATH=C:\xampp\php;%PATH%
start "Servicio vehiculos 8001" cmd /k "cd /d %~dp0vehiculos && php -S 127.0.0.1:8001 -t public"
start "Servicio clientes 8002" cmd /k "cd /d %~dp0clientes && php -S 127.0.0.1:8002 -t public"
start "Servicio reservas 8003" cmd /k "cd /d %~dp0reservas && php -S 127.0.0.1:8003 -t public"
