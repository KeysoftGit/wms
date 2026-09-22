@echo off
set "download_folder=%USERPROFILE%\Downloads"
set "printer_path=\\%COMPUTERNAME%\TSC_BARCODE"

echo --------------------------------------------------
echo  TSC AUTOMATIC PRINTING ACTIVE
echo  Monitoring: %download_folder%
echo  Printer: %printer_path%
echo --------------------------------------------------
echo  Leave this window open while printing barcodes.
echo  It will auto-print and delete barcodes.prn files.
echo --------------------------------------------------

:loop
:: Look for any file starting with 'barcodes' and ending in '.prn'
if exist "%download_folder%\barcodes*.prn" (
    for %%f in ("%download_folder%\barcodes*.prn") do (
        echo [LOG] %time% - Found: %%~nxf
        echo [LOG] Sending to printer...
        copy /b "%%f" "%printer_path%" >nul
        if errorlevel 1 (
            echo [ERROR] Failed to print. Check printer connection/path.
            pause
        ) else (
            echo [LOG] Printing complete. Cleaning up...
            del "%%f"
        )
        echo --------------------------------------------------
    )
)
:: Wait 1 second before checking again
timeout /t 1 >nul
goto loop
