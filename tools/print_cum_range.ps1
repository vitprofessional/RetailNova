$p='c:\xampp\htdocs\RetailNova\resources\views\customScript.blade.php'
$lines = Get-Content $p
$cum = 0
$start = 170
$end = 205
for ($i=0; $i -lt $lines.Count; $i++) {
    $ln = $lines[$i]
    $opens = ($ln.ToCharArray() | Where-Object {$_ -eq '{'}).Count
    $closes = ($ln.ToCharArray() | Where-Object {$_ -eq '}'}).Count
    $cum += $opens - $closes
    if ($i+1 -ge $start -and $i+1 -le $end) {
        Write-Output "Line $($i+1): cum=$cum | opens=$opens closes=$closes | $ln"
    }
}
Write-Output "Final cum=$cum"
