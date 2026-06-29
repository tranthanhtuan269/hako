@echo off
:: Chay file nay bang "Run as administrator" de gan hako.test vao hosts
set HOSTS=%SystemRoot%\System32\drivers\etc\hosts
findstr /I /C:"hako.test" "%HOSTS%" >nul
if %errorlevel%==0 (
    echo hako.test da co trong hosts.
) else (
    echo.>> "%HOSTS%"
    echo 127.0.0.1 hako.test>> "%HOSTS%"
    echo 127.0.0.1 www.hako.test>> "%HOSTS%"
    echo Da them hako.test vao hosts.
)
pause
