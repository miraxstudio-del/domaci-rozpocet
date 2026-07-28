@echo off
setlocal enabledelayedexpansion
title Domaci rozpocet - spousteni
cd /d "%~dp0"

set "PHP_EXE=app\php\php.exe"
set "PHP_INI=app\php\php.ini"

if not exist "%PHP_EXE%" (
    echo.
    echo [CHYBA] Nepodarilo se najit prilozene PHP ve slozce "app\php".
    echo Pro bezne pouziti stahnete a rozbalte prenosny balicek z GitHub Releases:
    echo https://github.com/miraxstudio-del/domaci-rozpocet/releases
    echo Pokud sestavujete aplikaci ze zdrojoveho kodu, postupujte podle docs\RELEASING.md.
    echo Take zkontrolujte, zda antivirus neblokuje soubor app\php\php.exe.
    echo.
    pause
    exit /b 1
)

rem Zjistime prvni volny port v rozsahu 8090-8099 (aby nekolidoval napr. s Tomcatem z XAMPP na 8080)
set "PORT="
for /L %%P in (8090,1,8099) do (
    if not defined PORT (
        netstat -ano | findstr /C:"127.0.0.1:%%P " >nul
        if errorlevel 1 (
            set "PORT=%%P"
        )
    )
)

if not defined PORT (
    echo.
    echo [CHYBA] Vsechny zkusene porty 8090-8099 jsou obsazene.
    echo Zavrete jine aplikace bezici na techto portech a zkuste to znovu.
    echo.
    pause
    exit /b 1
)

rem Pokud uz server na tomto portu bezi (spusteno drive), jen otevreme prohlizec
netstat -ano | findstr /C:"127.0.0.1:%PORT% " >nul
if not errorlevel 1 (
    start "" "http://127.0.0.1:%PORT%/"
    exit /b 0
)

echo.
echo Spoustim Domaci rozpocet na adrese http://127.0.0.1:%PORT%/ ...
echo (Server naslouchá pouze na tomto pocitaci, nikam do internetu se nepripojuje.)
echo.

start "DomaciRozpocetServer" /min "%PHP_EXE%" -c "%PHP_INI%" -S 127.0.0.1:%PORT% -t "app\public"

timeout /t 2 /nobreak >nul
start "" "http://127.0.0.1:%PORT%/"

echo Aplikace bezi na adrese http://127.0.0.1:%PORT%/
echo Pro ukonceni aplikace spustte soubor STOP.bat.
echo.
echo Toto okno se za chvili samo zavre...
timeout /t 5 /nobreak >nul
endlocal
