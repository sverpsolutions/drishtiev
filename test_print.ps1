$ie = New-Object -ComObject InternetExplorer.Application
$ie.Navigate("http://testncg.sverpsolutions.com/billing/print_invoice/598")
while($ie.ReadyState -ne 4) { Start-Sleep -Milliseconds 100 }
Start-Sleep -Seconds 1
$ie.ExecWB(6, 2)
Start-Sleep -Seconds 2
$ie.Quit()
