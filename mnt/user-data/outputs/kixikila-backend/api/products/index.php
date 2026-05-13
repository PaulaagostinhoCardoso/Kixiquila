<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — api/products/index.php
   GET  /products            → listar (com filtros)
   GET  /products?id=N       → detalhe de um produto
   GET  /products?categories → listar categorias
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCORS();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

match (true) {
    $method === 'GET' && isset($_GET['categories']) => listCategories(),
    $method === 'GET' && isset($_GET['id'])         => getProduct((int)$_GET['id']),
    $method === 'GET'                               => listProducts(),
    default => error('Método não permitido.', 405),
};

/* ─────────────────────────────────────────────────────────
   LISTAR CATEGORIAS COM CONTAGEM DE PRODUTOS ACTIVOS
───────────────────────────────────────────────────────── */
function listCategories(): never {
    $db  = getDB();
    $sql = "SELECT c.id, c.name, c.icon,
                   COUNT(p.id) AS count
            FROM categories c
            LEFT JOIN products p
              ON p.category_id = c.id AND p.active = 1
            GROUP BY c.id
            ORDER BY c.name";
    $cats = $db->query($sql)->fetchAll();
    success($cats);
}

/* ─────────────────────────────────────────────────────────
   LISTAR PRODUTOS (filtros + pesquisa + ordenação)
───────────────────────────────────────────────────────── */
function listProducts(): never {
    $db     = getDB();
    $params = [];
    $where  = ['p.active = 1'];

    // Filtro: categoria
    if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
        $where[]  = 'p.category_id = ?';
        $params[] = clean($_GET['category']);
    }

    // Filtro: badge (promo | new)
    if (!empty($_GET['badge']) && $_GET['badge'] !== 'all') {
        if ($_GET['badge'] === 'dest') {
            $where[] = 'p.rating >= 4.8';
        } else {
            $where[]  = 'p.badge = ?';
            $params[] = clean($_GET['badge']);
        }
    }

    // Pesquisa por texto
    if (!empty($_GET['search'])) {
        $q        = '%' . $_GET['search'] . '%';
        $where[]  = '(p.name LIKE ? OR p.description LIKE ? OR p.origin LIKE ?)';
        $params   = array_merge($params, [$q, $q, $q]);
    }

    // Preço mínimo / máximo
    if (!empty($_GET['min_price'])) {
        $where[]  = 'p.price >= ?';
        $params[] = (int)$_GET['min_price'];
    }
    if (!empty($_GET['max_price'])) {
        $where[]  = 'p.price <= ?';
        $params[] = (int)$_GET['max_price'];
    }

    // Ordenação
    $sortMap = [
        'price-asc'  => 'p.price ASC',
        'price-desc' => 'p.price DESC',
        'name'       => 'p.name ASC',
        'rating'     => 'p.rating DESC',
        'newest'     => 'p.created_at DESC',
    ];
    $sort = $sortMap[$_GET['sort'] ?? ''] ?? 'p.id ASC';

    // Paginação
    $page  = max(1, (int)($_GET['page']  ?? 1));
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $whereSQL = implode(' AND ', $where);

    // Contar total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Buscar produtos
    $sql = "SELECT p.id, p.name, c.name AS category_name, p.category_id,
                   p.icon, p.price, p.old_price, p.badge,
                   p.description, p.origin, p.rating, p.reviews,
                   p.stock, p.image_url, p.created_at
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE $whereSQL
            ORDER BY $sort
            LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Formatar tipos numéricos
    foreach ($products as &$p) {
        $p['id']        = (int)$p['id'];
        $p['price']     = (int)$p['price'];
        $p['old_price'] = $p['old_price'] !== null ? (int)$p['old_price'] : null;
        $p['rating']    = (float)$p['rating'];
        $p['reviews']   = (int)$p['reviews'];
        $p['stock']     = (int)$p['stock'];
    }

    success([
        'products'    => $products,
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => (int)ceil($total / $limit),
    ]);
}

/* ─────────────────────────────────────────────────────────
   DETALHE DE UM PRODUTO
───────────────────────────────────────────────────────── */
function getProduct(int $id): never {
    if ($id <= 0) error('ID de produto inválido.');

    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.id = ? AND p.active = 1"
    );
    $stmt->execute([$id]);
    $p = $stmt->fetch();

    if (!$p) error('Produto não encontrado.', 404);

    $p['id']        = (int)$p['id'];
    $p['price']     = (int)$p['price'];
    $p['old_price'] = $p['old_price'] !== null ? (int)$p['old_price'] : null;
    $p['rating']    = (float)$p['rating'];
    $p['reviews']   = (int)$p['reviews'];
    $p['stock']     = (int)$p['stock'];

    success($p);
}
