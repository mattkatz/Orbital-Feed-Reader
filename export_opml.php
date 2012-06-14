<?php
// Set the headers so the file downloads
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="export-file.csv "');
echo "a,b,c";
exit;
?>
