@echo off
title Domaci rozpocet - ukonceni
echo Ukoncuji Domaci rozpocet...

taskkill /FI "WINDOWTITLE eq DomaciRozpocetServer*" /T /F >nul 2>&1

if %ERRORLEVEL%==0 (
    echo Aplikace byla uspesne ukoncena. Databaze i doklady zustavaji ulozene.
) else (
    echo Aplikace pravdepodobne uz nebezela.
)

echo.
echo Toto okno se za chvili samo zavre...
timeout /t 3 /nobreak >nul
