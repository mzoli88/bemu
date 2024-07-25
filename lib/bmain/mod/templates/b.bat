@echo off

for /f %%q in ("%~dp0.") do set modul_azon=%%~nxq

cd ..
cd ..

IF /i "%1"=="hws:mig" GOTO Mig
IF /i "%1"=="hws:m" GOTO Model
IF /i "%1"=="hws:c" GOTO Ctrl
GOTO NoParam

:NoParam
  call "b.bat" %1 %2 %3 %4 %5
  GOTO End
:Mig
  call "b.bat" artisan hws:mig %modul_azon% %2 %3 %4 %5
  GOTO End
:Model
  call "b.bat" artisan hws:m %modul_azon% %2 %3 %4 %5
  GOTO End
:Ctrl
  call "b.bat" artisan hws:c %modul_azon% %2 %3 %4 %5
  GOTO End
:End

cd mod/%modul_azon%