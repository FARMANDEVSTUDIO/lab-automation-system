<?php
declare(strict_types=1);

/**
 * PDO connection factory for the lab_automation MySQL database.
 * Usage: $pdo = Database::getConnection();
 *
 * @author Naveera
 */
class Database
{
    private static ?PDO $instance = null;

    // ---- Connection settings -------------------------------------------
    // Override via .env (DB_HOST, DB_NAME, DB_USER, DB_PASS) or edit below.
    private static function host(): string   { return env('DB_HOST', 'localhost'); }
    private static function dbname(): string { return env('DB_NAME', 'lab_automation'); }
    private static function user(): string   { return env('DB_USER', 'root'); }
    private static function pass(): string   { return env('DB_PASS', ''); }
    private const CHARSET = 'utf8mb4';

    private function __construct()
    {
        // Prevent direct instantiation — use Database::getConnection().
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::host(),
                self::dbname(),
                self::CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::user(), self::pass(), $options);
            } catch (PDOException $e) {
                error_log('[Database] Connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('A system error occurred. Please try again later or contact your administrator.');
            }
        }

        return self::$instance;
    }
}
