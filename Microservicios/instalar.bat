@echo off
set PATH=C:\xampp\php;%PATH%
for %%S in (vehiculos clientes reservas) do (
  echo ===== Instalando %%S =====
  pushd %%S
  call composer install
  popd
)
pause
