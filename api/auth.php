<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — api/auth.php
   Endpoints: POST /register  POST /login  POST /logout  GET /me
   (Adaptado para a nova estrutura de pastas)
   Execute com ?action=login, ?action=register, etc.
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../helpers.php';

// Configurações Globais
if (function_exists('setCORS')) setCORS();
if (function_exists('startSession')) startSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Roteamento Simples
match (true) {
    $method === 'POST' && $action === 'register' => handleRegister(),
    $method === 'POST' && $action === 'login'    => handleLogin(),
    $method === 'POST' && $action === 'logout'   => handleLogout(),
    $method === 'GET'  && $action === 'me'       => handleMe(),
    default => error('Endpoint não encontrado.', 404),
};

/* ─────────────────────────────────────────────────────────
   REGISTAR
───────────────────────────────────────────────────────── */
function handleRegister(): never {
    $body  = getBody();
    $name  = clean($body['name']  ?? '');
    $email = clean($body['email'] ?? '');
    $pass  = $body['password']    ?? '';
    $phone = clean($body['phone'] ?? '');
    $role  = clean($body['role']  ?? 'client');
    $key   = $body['admin_key']   ?? '';

    // Validações
    if (!$name)                        error('Nome é obrigatório.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error('Email inválido.');
    if (strlen($pass) < 8)             error('A senha deve ter pelo menos 8 caracteres.');

    // Verificação de Admin (Simples para demonstração)
    if ($role === 'admin') {
        if ($key !== 'KIXIKILA_ADMIN_2025') {
            error('Chave de Administrador inválida.');
        }
    } else {
        $role = 'client';
    }

    $db = getDB();

    // Email já existe?
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) error('Este email já está registado.', 409);

    // Criar utilizador
    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare(
        'INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $hash, $phone ?: null, $role]);
    $userId = $db->lastInsertId();

    // Login automático após registo
    $_SESSION['user'] = [
        'id'    => (int)$userId,
        'name'  => $name,
        'email' => $email,
        'role'  => $role,
    ];

    success([
        'id'    => (int)$userId,
        'name'  => $name,
        'email' => $email,
        'role'  => 'client',
    ], 'Conta criada com sucesso!', 201);
}

/* ─────────────────────────────────────────────────────────
   LOGIN
───────────────────────────────────────────────────────── */
function handleLogin(): never {
    $body  = getBody();
    $email = clean($body['email'] ?? '');
    $pass  = $body['password']    ?? '';

    if (!$email || !$pass) error('Email e senha são obrigatórios.');

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT id, name, email, password_hash, role, active FROM users WHERE email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        error('Credenciais inválidas.', 401);
    }
    if (!$user['active']) {
        error('Conta desactivada. Contacta o suporte.', 403);
    }

    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];

    success($_SESSION['user'], 'Login realizado com sucesso!');
}

/* ─────────────────────────────────────────────────────────
   LOGOUT
───────────────────────────────────────────────────────── */
function handleLogout(): never {
    $_SESSION = [];
    session_destroy();
    success(null, 'Sessão terminada.');
}

/* ─────────────────────────────────────────────────────────
   DADOS DO UTILIZADOR ACTUAL
───────────────────────────────────────────────────────── */
function handleMe(): never {
    $user = authUser();
    if (!$user) error('Não autenticado.', 401);

    // Actualizar dados frescos da BD
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = ?'
    );
    $stmt->execute([$user['id']]);
    $fresh = $stmt->fetch();

    if (!$fresh) error('Utilizador não encontrado.', 404);

    $fresh['id'] = (int)$fresh['id'];
    success($fresh);
}
