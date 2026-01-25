@echo off
setlocal enabledelayedexpansion

REM Loop through drive letters to find OSAS folder
set FOUND_PATH=
for %%D in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist "%%D:\xampp\htdocs\OSAS-SIS" (
        set FOUND_PATH=%%D:\xampp\htdocs\OSAS-SIS
        goto :FOUND
    )
)

:NOTFOUND
echo The folder "\xampp\htdocs\OSAS-SIS" was not found on any drive.
pause
exit /b

:FOUND
cd /d "%FOUND_PATH%"
echo Found OSAS path: %FOUND_PATH%

REM Loop through drive letters to find Git Bash shortcut
set GITBASH_PATH=
for %%D in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist "%%D:\Users\Other\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Git\Git Bash.lnk" (
        set GITBASH_PATH=%%D:\Users\Other\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Git\Git Bash.lnk
        goto :FOUNDGIT
    )
)

:NOTFOUNDGIT
echo Git Bash shortcut was not found on any drive.
pause
exit /b

:FOUNDGIT
echo Found Git Bash shortcut: %GITBASH_PATH%
echo Opening Git Bash and running npm run dev...

REM Use 'start' to open Git Bash shortcut and run npm
start "" "%GITBASH_PATH%" -c "cd '%FOUND_PATH%' && npm run dev"

REM Minimize all windows
powershell -command "(New-Object -ComObject Shell.Application).MinimizeAll()"

endlocal