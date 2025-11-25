$p='c:\xampp\htdocs\RetailNova\resources\views\customScript.blade.php'
$lines = Get-Content $p
$cum = 0
for ($i=0; $i -lt $lines.Count; $i++) {
    $ln = $lines[$i]
    $opens = ($ln.ToCharArray() | Where-Object {$_ -eq '{'}).Count
    $closes = ($ln.ToCharArray() | Where-Object {$_ -eq '}'}).Count
    $cum += $opens - $closes
    if ($cum -lt 0) {
        Write-Output "Negative at line: $($i+1) cum=$cum"
        break
    }
}
Write-Output "Final cum=$cum"
