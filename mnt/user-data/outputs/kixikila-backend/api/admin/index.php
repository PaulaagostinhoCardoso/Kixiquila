<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — api/admin/index.php
   Painel de Administração — apenas role='admin'

   GET    /admin?action=stats           → dashboard stats
   GET    /admin?action=products        → listar todos os produtos
   POST   /admin?action=products        → criar produto
   PUT    /admin?action=products&id=N   → editar produto
   DELETE /admin?action=products&id=N   → apagar produto
   GET    /admin?action=orders          → listar encomendas
   PUT    /admin?action=orders&id=N     → actualizar status
   GET    /admin?action=users           → listar utilizadores
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCORS();
startSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Todos os endpoints do admin exigem autenticação de admin
requireAdmin();

match (true) {
    /* ── Dashboard ── */
    $method === 'GET'    && $action === 'stats'    => getDashboardStats(),

    /* ── Produtos ── */
    $method === 'GET'    && $action === 'products' => adminListProducts(),
    $method === 'POST'   && $action === 'products' => adminCreateProduct(),
    $method === 'PUT'    && $action === 'products' && $id => adminUpdateProduct($id),
    $method === 'DELETE' && $action === 'products' && $id => adminDeleteProduct($id),

    /* ── Encomendas ── */
    $method === 'GET'    && $action === 'orders'   => adminListOrders(),
    $method === 'PUT'    && $action === 'orders' && $id => adminUpdateOrder($id),

    /* ── Utilizadores ── */
    $method === 'GET'    && $action === 'users'    => adminListUsers(),

    default => error('Endpoint admin não encontrado.', 404),
};

/* ═══════════════════════════════════════════════════════════
   DASHBOARD — ESTATÍSTICAS
═══════════════════════════════════════════════════════════ */
function getDashboardStats(): never {
    $db = getDB();

    $stats = [];

    // Total de produtos activos
    $stats['total_products']  = (int)$db->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn();

    // Total de encomendas
    $stats['total_orders']    = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();

    // Encomendas pendentes
    $stats['pending_orders']  = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();

    // Total de utilizadores clientes
    $stats['total_clients']   = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();

    // Receita total (encomendas confirmadas/entregues)
    $stats['total_revenue']   = (int)$db->query(
        "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('confirmed','shipped','delivered')"
    )->fetchColumn();

    // Encomendas por status
    $rows = $db->query(
        "SELECT status, COUNT(*) AS qty FROM orders GROUP BY status"
    )->fetchAll();
    $stats['orders_by_status'] = array_column($rows, 'qty', 'status');

    // Top 5 produtos mais vendidos
    $stats['top_products'] = $db->query(
        "SELECT p.name, p.icon, SUM(oi.qty) AS sold
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         GROUP BY p.id
         ORDER BY sold DESC
         LIMIT 5"
    )->fetchAll();

    // Últimas 5 encomendas
    $stats['recent_orders'] = $db->query(
        "SELECT o.id, o.total, o.status, o.created_at,
                COALESCE(u.name, o.guest_name) AS client_name
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC
         LIMIT 5"
    )->fetchAll();

    success($stats);
}

/* ═══════════════════════════════════════════════════════════
   ADMIN — PRODUTOS
═══════════════════════════════════════════════════════════ */
function adminListProducts(): never {
    $db = getDB();
    $sql = "SELECT p.*, c.name AS category_name
            FROM products p
            JOIN categories c ON c.id = p.category_id
            ORDER BY p.id DESC";
    $products = $db->query($sql)->fetchAll();

    foreach ($products as &$p) {
        $p['id']        = (int)$p['id'];
        $p['price']     = (int)$p['price'];
        $p['old_price'] = $p['old_price'] !== null ? (int)$p['old_price'] : null;
        $p['rating']    = (float)$p['rating'];
        $p['reviews']   = (int)$p['reviews'];
        $p['stock']     = (int)$p['stock'];
        $p['active']    = (bool)$p['active'];
    }

    success($products);
}

function adminCreateProduct(): never {
    $body = getBody();

    $name     = clean($body['name']        ?? '');
    $catId    = clean($body['category_id'] ?? '');
    $icon     = clean($body['icon']        ?? '📦');
    $price    = (int)($body['price']       ?? 0);
    $oldPrice = isset($body['old_price']) && $body['old_price'] !== '' ? (int)$body['old_price'] : null;
    $badge    = in_array($body['badge'] ?? '', ['promo','new']) ? $body['badge'] : null;
    $desc     = clean($body['description'] ?? '');
    $origin   = clean($body['origin']      ?? '');
    $stock    = max(0, (int)($body['stock'] ?? 0));

    if (!$name)   error('Nome do produto é obrigatório.');
    if (!$catId)  error('Categoria é obrigatória.');
    if ($price <= 0) error('Preço deve ser maior que zero.');
    if (!$desc)   error('Descrição é obrigatória.');
    if (!$origin) error('Origem é obrigatória.');

    // Verificar categoria
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM categories WHERE id = ?');
    $stmt->execute([$catId]);
    if (!$stmt->fetch()) error('Categoria não encontrada.', 404);

    $stmt = $db->prepare(
        "INSERT INTO products
           (name, category_id, icon, price, old_price, badge, description, origin, stock)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $catId, $icon, $price, $oldPrice, $badge, $desc, $origin, $stock]);

    success(['id' => (int)$db->lastInsertId()], 'Produto criado com sucesso!', 201);
}

function adminUpdateProduct(int $id): never {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) error('Produto não encontrado.', 404);

    $body   = getBody();
    $fields = [];
    $params = [];

    $allowed = [
        'name'        => 'string',
        'category_id' => 'string',
        'icon'        => 'string',
        'price'       => 'int',
        'old_price'   => 'int_null',
        'badge'       => 'badge',
        'description' => 'string',
        'origin'      => 'string',
        'stock'       => 'int',
        'active'      => 'bool',
        'rating'      => 'float',
    ];

    foreach ($allowed as $field => $type) {
        if (!array_key_exists($field, $body)) continue;

        $val = $body[$field];
        switch ($type) {
            case 'string':   $params[] = clean((string)$val); break;
            case 'int':      $params[] = (int)$val;           break;
            case 'int_null': $params[] = ($val === '' || $val === null) ? null : (int)$val; break;
            case 'float':    $params[] = (float)$val;         break;
            case 'bool':     $params[] = $val ? 1 : 0;        break;
            case 'badge':    $params[] = in_array($val, ['promo','new']) ? $val : null; break;
        }
        $fields[] = "`$field` = ?";
    }

    if (empty($fields)) error('Nenhum campo para actualizar.');

    $params[] = $id;
    $db->prepare("UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

    success(null, 'Produto actualizado com sucesso!');
}

function adminDeleteProduct(int $id): never {
    $db   = getDB();
    $stmt = $db->prepare('SELECT name FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) error('Produto não encontrado.', 404);

    // Soft delete (não apaga da BD, só desactiva)
    $db->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([$id]);

    success(null, "Produto \"{$p['name']}\" desactivado.");
}

/* ═══════════════════════════════════════════════════════════
   ADMIN — ENCOMENDAS
═══════════════════════════════════════════════════════════ */
function adminListOrders(): never {
    $db = getDB();

    // Filtro de status
    $where  = [];
    $params = [];
    if (!empty($_GET['status'])) {
        $where[]  = 'o.status = ?';
        $params[] = clean($_GET['status']);
    }
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $orders = $db->prepare(
        "SELECT o.id, o.total, o.subtotal, o.discount_pct, o.status,
                o.delivery_address, o.promo_code, o.created_at,
                COALESCE(u.name, o.guest_name)   AS client_name,
                COALESCE(u.email, o.guest_email)  AS client_email,
                COALESCE(u.phone, o.guest_phone)  AS client_phone,
                COUNT(oi.id) AS item_count
         FROM orders o
         LEFT JOIN users u  ON u.id  = o.user_id
         LEFT JOIN order_items oi ON oi.order_id = o.id
         $whereSQL
         GROUP BY o.id
         ORDER BY o.created_at DESC"
    );
    $orders->execute($params);
    $rows = $orders->fetchAll();

    foreach ($rows as &$o) {
        $o['id']           = (int)$o['id'];
        $o['total']        = (int)$o['total'];
        $o['subtotal']     = (int)$o['subtotal'];
        $o['discount_pct'] = (int)$o['discount_pct'];
        $o['item_count']   = (int)$o['item_count'];
    }

    success($rows);
}

function adminUpdateOrder(int $id): never {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, status FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) error('Encomenda não encontrada.', 404);

    $body   = getBody();
    $status = clean($body['status'] ?? '');
    $allowed_statuses = ['pending','confirmed','shipped','delivered','cancelled'];

    if (!in_array($status, $allowed_statuses)) {
        error('Status inválido. Valores: ' . implode(', ', $allowed_statuses));
    }

    $db->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
    success(null, "Encomenda #{$id} actualizada para: $status");
}

/* ═══════════════════════════════════════════════════════════
   ADMIN — UTILIZADORES
═══════════════════════════════════════════════════════════ */
function adminListUsers(): never {
    $db   = getDB();
    $rows = $db->query(
        "SELECT id, name, email, phone, role, active, created_at
         FROM users
         ORDER BY created_at DESC"
    )->fetchAll();

    foreach ($rows as &$u) {
        $u['id']     = (int)$u['id'];
        $u['active'] = (bool)$u['active'];
    }

    success($rows);
}
