<?php
/**
 * Simple Database Connection (PDO)
 * 
 * NOTE:
 * - Sesuaikan username & password MySQL kamu kalau tidak pakai default XAMPP.
 * - Default XAMPP: user = root, password = '' (kosong).
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'helpdesk_db'; // sesuai nama DB di SQL kamu
        $username = 'root';
        $password = ''; // ubah kalau MySQL kamu pakai password

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Error handling simple (bisa kamu ganti jadi log file nanti)
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}
