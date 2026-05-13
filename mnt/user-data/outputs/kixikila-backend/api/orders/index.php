<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — api/orders/index.php
   POST /orders              → criar encomenda (checkout)
   GET  /orders              → minhas encomendas (auth)
   GET  /orders?id=N         → detalhe de encomenda (auth)
   POST /orders?promo=CODE   → validar código promo
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCORS();
startSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

match (true) {
    $method === 'POST' && $action === 'checkout'     => createOrder(),
    $method === 'POST' && $action === 'validate-promo' => validatePromo(),
    $method === 'GET'  && isset($_GET['id'])         => getOrder((int)$_GET['id']),
    $method === 'GET'                                => listMyOrders(),
    default => error('Endpoint não encontrado.', 404),
};

/* ─────────────────────────────────────────────────────────
   CRIAR ENCOMENDA (CHECKOUT)
───────────────────────────────────────────────────────── */
function createOrder(): never {
    $body    = getBody();
    $user    = authUser();   // pode ser null (guest)

    /* ── Dados do cliente ── */
    $name    = clean($body['name']    ?? ($user['name']  ?? ''));
    $email   = clean($body['email']   ?? ($user['email'] ?? ''));
    $phone   = clean($body['phone']   ?? '');
    $address = clean($body['address'] ?? '');
    $notes   = clean($body['notes']   ?? '');
    $promo   = strtoupper(clean($body['promo_code'] ?? ''));

    if (!$name)    error('Nome é obrigatório.');
    if (!$email)   error('Email é obrigatório.');
    if (!$phone)   error('Telefone é obrigatório.');
    if (!$address) error('Endereço de entrega é obrigatório.');

    /* ── Carrinho vindo do front-end ── */
    $items = $body['items'] ?? [];
    if (empty($items)) error('O carrinho está vazio.');

    $db = getDB();

    /* ── Validar produtos e calcular subtotal ── */
    $subtotal   = 0;
    $orderItems = [];

    foreach ($items as $item) {
        $productId = (int)($item['id']  ?? 0);
        $qty       = max(1, (int)($item['qty'] ?? 1));

        $stmt = $db->prepare(
            'SELECT id, name, price, stock, active FROM products WHERE id = ?'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product)           error("Produto ID $productId não encontrado.", 404);
        if (!$product['active']) error("Produto \"{$product['name']}\" não está disponível.");
        if ($product['stock'] < $qty) {
            error("Stock insuficiente para \"{$product['name']}\". Disponível: {$product['stock']}.");
        }

        $subtotal += $product['price'] * $qty;
        $orderItems[] = [
            'id'         => $product['id'],
            'name'       => $product['name'],
            'price'      => $product['price'],
            'qty'        => $qty,
        ];
    }

    /* ── Código promo ── */
    $discountPct = 0;
    if ($promo) {
        $stmt = $db->prepare(
            "SELECT discount FROM promo_codes
             WHERE code = ? AND active = 1
               AND (expires_at IS NULL OR expires_at >= CURDATE())"
        );
        $stmt->execute([$promo]);
        $promoRow = $stmt->fetch();
        if ($promoRow) {
            $discountPct = (int)$promoRow['discount'];
        }
        // Se código inválido não cancela a encomenda — apenas sem desconto
    }

    $discount = (int)($subtotal * $discountPct / 100);
    $total    = $subtotal - $discount;

    /* ── Inserir encomenda ── */
    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO orders
               (user_id, guest_name, guest_email, guest_phone,
                delivery_address, promo_code, discount_pct,
                subtotal, total, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $user ? $user['id'] : null,
            !$user ? $name  : null,
            !$user ? $email : null,
            !$user ? $phone : null,
            $address,
            $promo ?: null,
            $discountPct,
            $subtotal,
            $total,
            $notes ?: null,
        ]);
        $orderId = (int)$db->lastInsertId();

        /* ── Inserir itens e descontar stock ── */
        $insertItem = $db->prepare(
            'INSERT INTO order_items (order_id, product_id, qty, unit_price) VALUES (?, ?, ?, ?)'
        );
        $updateStock = $db->prepare(
            'UPDATE products SET stock = stock - ? WHERE id = ?'
        );

        foreach ($orderItems as $oi) {
            $insertItem->execute([$orderId, $oi['id'], $oi['qty'], $oi['price']]);
            $updateStock->execute([$oi['qty'], $oi['id']]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        error('Erro ao processar encomenda. Tenta novamente.', 500);
    }

    success([
        'order_id'     => $orderId,
        'subtotal'     => $subtotal,
        'discount_pct' => $discountPct,
        'discount_kz'  => $discount,
        'total'        => $total,
        'status'       => 'pending',
    ], "Encomenda #{$orderId} criada com sucesso! Aguarda confirmação por email.", 201);
}

/* ─────────────────────────────────────────────────────────
   VALIDAR CÓDIGO PROMO
───────────────────────────────────────────────────────── */
function validatePromo(): never {
    $body = getBody();
    $code = strtoupper(clean($body['code'] ?? ''));

    if (!$code) error('Código promocional vazio.');

    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT code, discount FROM promo_codes
         WHERE code = ? AND active = 1
           AND (expires_at IS NULL OR expires_at >= CURDATE())"
    );
    $stmt->execute([$code]);
    $row = $stmt->fetch();

    if (!$row) error('Código inválido ou expirado.', 404);

    success([
        'code'     => $row['code'],
        'discount' => (int)$row['discount'],
    ], "Código válido! Tens {$row['discount']}% de desconto.");
}

/* ─────────────────────────────────────────────────────────
   LISTAR ENCOMENDAS DO UTILIZADOR AUTENTICADO
───────────────────────────────────────────────────────── */
function listMyOrders(): never {
    $user = requireAuth();
    $db   = getDB();

    $stmt = $db->prepare(
        "SELECT o.id, o.subtotal, o.discount_pct, o.total,
                o.status, o.delivery_address, o.created_at,
                COUNT(oi.id) AS item_count
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         WHERE o.user_id = ?
         GROUP BY o.id
         ORDER BY o.created_at DESC"
    );
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$o) {
        $o['id']           = (int)$o['id'];
        $o['subtotal']     = (int)$o['subtotal'];
        $o['total']        = (int)$o['total'];
        $o['discount_pct'] = (int)$o['discount_pct'];
        $o['item_count']   = (int)$o['item_count'];
    }

    success($orders);
}

/* ─────────────────────────────────────────────────────────
   DETALHE DE UMA ENCOMENDA
───────────────────────────────────────────────────────── */
function getOrder(int $id): never {
    $user = requireAuth();
    $db   = getDB();

    $stmt = $db->prepare(
        "SELECT o.* FROM orders o
         WHERE o.id = ?
           AND (o.user_id = ? OR ? = 'admin')"
    );
    $stmt->execute([$id, $user['id'], $user['role']]);
    $order = $stmt->fetch();

    if (!$order) error('Encomenda não encontrada.', 404);

    // Itens da encomenda
    $stmt = $db->prepare(
        "SELECT oi.qty, oi.unit_price,
                p.id AS product_id, p.name, p.icon, p.origin
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?"
    );
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();

    foreach ($items as &$i) {
        $i['qty']        = (int)$i['qty'];
        $i['unit_price'] = (int)$i['unit_price'];
        $i['product_id'] = (int)$i['product_id'];
        $i['subtotal']   = $i['qty'] * $i['unit_price'];
    }

    $order['id']           = (int)$order['id'];
    $order['subtotal']     = (int)$order['subtotal'];
    $order['total']        = (int)$order['total'];
    $order['discount_pct'] = (int)$order['discount_pct'];
    $order['items']        = $items;

    success($order);
}
