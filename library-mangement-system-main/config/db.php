<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection.
 *
 * Uses prepared statements by default and throws exceptions on error.
 *
 * @return PDO
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}


