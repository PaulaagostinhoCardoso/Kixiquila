<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — config/database.php
   Configuração da ligação à base de dados
═══════════════════════════════════════════════════════════ */

define('DB_HOST', 'localhost');
define('DB_NAME', 'kixikila_market');
define('DB_USER', 'root');       // ← altera para o teu utilizador MySQL
define('DB_PASS', '');           // ← altera para a tua senha MySQL
define('DB_CHAR', 'utf8mb4');

/**
 * Devolve a ligação PDO (singleton)
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHAR
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro de ligação à base de dados.']);
            exit;
        }
    }

    return $pdo;
}
