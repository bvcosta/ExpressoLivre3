<?php
<<<<<<< HEAD

=======
>>>>>>> 701f12bb8385fe7ec1b483e3edc2762f77a65b7f
$file = urldecode($_GET['file']);
$download = ($_GET['download'] == 'yes');

if (file_exists($file) && $download) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    ob_clean();
    flush();
    readfile($file);
}

unlink($file);
exit;
