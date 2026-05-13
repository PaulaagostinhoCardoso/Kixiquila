<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — config/helpers.php
   Funções utilitárias partilhadas por toda a API
═══════════════════════════════════════════════════════════ */

/* ─── CORS — permite que o front-end comunique com a API ─── */
function setCORS(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');

    // Responde imediatamente ao pre-flight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/* ─── Resposta JSON padronizada ─── */
function respond(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function success(mixed $data = null, string $message = 'OK', int $code = 200): never {
    respond(['success' => true, 'message' => $message, 'data' => $data], $code);
}

function error(string $message, int $code = 400, mixed $extra = null): never {
    $body = ['success' => false, 'error' => $message];
    if ($extra !== null) $body['details'] = $extra;
    respond($body, $code);
}

/* ─── Ler corpo JSON do request ─── */
function getBody(): array {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/* ─── Sanitizar strings ─── */
function clean(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

/* ─── Sessão segura ─── */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7,   // 7 dias
            'path'     => '/',
            'secure'   => false,        // muda para true em HTTPS/produção
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/* ─── Utilizador autenticado ─── */
function authUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

/* ─── Requer autenticação ─── */
function requireAuth(): array {
    $user = authUser();
    if (!$user) error('Autenticação necessária. Faz login primeiro.', 401);
    return $user;
}

/* ─── Requer administrador ─── */
function requireAdmin(): array {
    $user = requireAuth();
    if ($user['role'] !== 'admin') error('Acesso negado. Apenas administradores.', 403);
    return $user;
}
