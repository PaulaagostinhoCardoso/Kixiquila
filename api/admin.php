<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — api/admin.php
   Endpoints de gestão (apenas para Admins)
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../helpers.php';

if (function_exists('setCORS')) setCORS();
$user = requireAdmin(); // Garante que apenas admins acedem

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

match (true) {
    $action === 'stats'      => handleStats(),
    $action === 'products'   => handleProducts($method),
    $action === 'categories' => handleCategories($method),
    $action === 'orders'     => handleOrders($method),
    $action === 'users'      => handleUsers(),
    default => error('Acção não reconhecida.', 404),
};

/* ─────────────────────────────────────────────────────────
   ESTATÍSTICAS
───────────────────────────────────────────────────────── */
function handleStats(): never {
    $db = getDB();
    
    $stats = [
        'total_products' => $db->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn(),
        'total_orders'   => $db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
        'total_clients'  => $db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn(),
        'total_revenue'  => $db->query('SELECT SUM(total) FROM orders WHERE status != "cancelled"')->fetchColumn() ?: 0,
        
        'top_products'   => $db->query('
            SELECT p.name, p.icon, SUM(oi.qty) as sold
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            GROUP BY p.id
            ORDER BY sold DESC
            LIMIT 5
        ')->fetchAll(),
        
        'recent_orders'  => $db->query('
            SELECT o.id, o.total, o.status, o.created_at, u.name as client_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ')->fetchAll()
    ];
    
    success($stats);
}

/* ─────────────────────────────────────────────────────────
   PRODUTOS (CRUD)
───────────────────────────────────────────────────────── */
function handleProducts(string $method): never {
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);

    if ($method === 'GET') {
        $res = $db->query('
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            ORDER BY p.id DESC
        ')->fetchAll();
        success($res);
    }

    if ($method === 'POST') {
        $b = getBody();
        $stmt = $db->prepare('
            INSERT INTO products (name, category_id, icon, price, old_price, badge, description, origin, stock)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $b['name'] ?? '', 
            $b['category_id'] ?? '', 
            $b['icon'] ?? '📦', 
            $b['price'] ?? 0, 
            ($b['old_price'] ?? null) ?: null, 
            ($b['badge'] ?? null) ?: null, 
            $b['description'] ?? '', 
            $b['origin'] ?? 'Angola', 
            $b['stock'] ?? 0
        ]);
        success(null, 'Produto criado!');
    }

    if ($method === 'PUT') {
        if (!$id) error('ID necessário.');
        $b = getBody();
        $stmt = $db->prepare('
            UPDATE products 
            SET name=?, category_id=?, icon=?, price=?, old_price=?, badge=?, description=?, origin=?, stock=?
            WHERE id=?
        ');
        $stmt->execute([
            $b['name'], $b['category_id'], $b['icon'], $b['price'], 
            $b['old_price'] ?: null, $b['badge'] ?: null, 
            $b['description'], $b['origin'], $b['stock'] ?? 0, $id
        ]);
        success(null, 'Produto actualizado!');
    }

    if ($method === 'DELETE') {
        if (!$id) error('ID necessário.');
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        success(null, 'Produto eliminado definitivamente.');
    }

    error('Método não permitido.', 405);
}

/* ─────────────────────────────────────────────────────────
   CATEGORIAS
───────────────────────────────────────────────────────── */
function handleCategories(string $method): never {
    $db = getDB();
    $id = $_GET['id'] ?? ''; // ID da categoria é string (ex: 'alimentacao')

    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
        success($res);
    }

    if ($method === 'POST') {
        $b = getBody();
        $name = $b['name'] ?? '';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        $stmt = $db->prepare('INSERT INTO categories (id, name, icon) VALUES (?, ?, ?)');
        $stmt->execute([$slug, $name, $b['icon'] ?? '📦']);
        success(null, 'Categoria criada!');
    }

    if ($method === 'PUT') {
        if (!$id) error('ID necessário.');
        $b = getBody();
        
        // Se enviarmos 'active', mudamos apenas o estado (fechar/abrir)
        if (isset($b['active'])) {
            $db->prepare('UPDATE categories SET active = ? WHERE id = ?')->execute([(int)$b['active'], $id]);
            success(null, $b['active'] ? 'Categoria aberta!' : 'Categoria fechada!');
        }
        
        $db->prepare('UPDATE categories SET name = ?, icon = ? WHERE id = ?')->execute([$b['name'], $b['icon'], $id]);
        success(null, 'Categoria actualizada!');
    }

    if ($method === 'DELETE') {
        if (!$id) error('ID necessário.');
        // Verifica se há produtos vinculados
        $count = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            error('Não podes eliminar uma categoria que tenha produtos associados. Tenta "fechar" a categoria em vez de eliminar.');
        }
        
        $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        success(null, 'Categoria eliminada.');
    }

    error('Método não permitido.', 405);
}

/* ─────────────────────────────────────────────────────────
   ENCOMENDAS
───────────────────────────────────────────────────────── */
function handleOrders(string $method): never {
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);

    if ($method === 'GET') {
        $res = $db->query('
            SELECT o.*, u.name as client_name, u.email as client_email, u.phone as client_phone,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ')->fetchAll();
        success($res);
    }

    if ($method === 'PUT') {
        if (!$id) error('ID necessário.');
        $b = getBody();
        if (!isset($b['status'])) error('Status necessário.');
        
        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$b['status'], $id]);
        success(null, 'Status actualizado!');
    }

    error('Método não permitido.', 405);
}

/* ─────────────────────────────────────────────────────────
   UTILIZADORES
───────────────────────────────────────────────────────── */
function handleUsers(): never {
    $db = getDB();
    $res = $db->query('SELECT id, name, email, phone, role, active, created_at FROM users ORDER BY id DESC')->fetchAll();
    success($res);
}
