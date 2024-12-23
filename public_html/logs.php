<?php
function logs($log_string): void {
    date_default_timezone_set('Europe/Lisbon');
    
    $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : 
        (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 
        $_SERVER['REMOTE_ADDR']);

    $log_string = date("d-m-Y") . " " . date("H:i:s") . " - " . $ip . " - " . $log_string . "\n";

    $log_file = 'C:/saw/logs/logs.txt';
    $handle = fopen($log_file, 'a') or die('Cannot open file: ' . $log_file);

    fwrite($handle, $log_string);

    fclose($handle);
}

?>
