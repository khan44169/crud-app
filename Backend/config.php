<?php
// config.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = 'localhost';
    $dbname = 'user_api';
    $username = 'root';
    $password = '';

    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');

    return $conn;
}
