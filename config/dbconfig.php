<?php
    $host = "localhost";
    $user = "root";
    $pwd = "";
    $dbname = "ubish";
    $charset = "utf8mb4";
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        $pdo = new PDO($dsn, $user, $pwd, $options);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
?>