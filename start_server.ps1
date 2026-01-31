$phpPath = "C:\xampp\php\php.exe"

if (Test-Path $phpPath) {
    Write-Host "Starting server using XAMPP PHP at http://localhost:8000..."
    Start-Process $phpPath -ArgumentList "-S localhost:8000" -NoNewWindow
    Start-Process "http://localhost:8000"
} else {
    Write-Error "XAMPP PHP not found at $phpPath. Please verify your XAMPP installation path."
    exit 1
}
