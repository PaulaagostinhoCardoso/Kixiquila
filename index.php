<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — index.php
   Página principal dinâmica com integração de Base de Dados
═══════════════════════════════════════════════════════════ */

require_once 'database.php';
require_once 'helpers.php';
require_once 'includes/functions.php';

startSession();
$user = authUser();

$db = getDB();

// 1. Procurar Categorias
$categories = $db->query('SELECT * FROM categories WHERE active = 1 ORDER BY name ASC')->fetchAll();

// 2. Procurar Produtos
$products = $db->query('
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.active = 1 AND c.active = 1
    ORDER BY p.id DESC
')->fetchAll();

// 3. Preparar dados para o JS (para manter compatibilidade com app.js)
// Adaptamos os nomes das chaves para baterem com o que o app.js espera
$jsProducts = array_map(function($p) {
    return [
        'id'       => (int)$p['id'],
        'name'     => $p['name'],
        'category' => $p['category_id'],
        'icon'     => $p['icon'],
        'price'    => (int)$p['price'],
        'oldPrice' => $p['old_price'] ? (int)$p['old_price'] : null,
        'badge'    => $p['badge'],
        'desc'     => $p['description'],
        'origin'   => $p['origin'],
        'rating'   => (float)$p['rating'],
        'reviews'  => (int)$p['reviews']
    ];
}, $products);

?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kixikila Market — Angola</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap" rel="stylesheet" />
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="css/style.css" />
    
    <script>
        // Injectamos os dados da BD para o JS
        const PRODUCTS = <?php echo json_encode($jsProducts, JSON_UNESCAPED_UNICODE); ?>;
        const CATEGORIES = <?php echo json_encode($categories, JSON_UNESCAPED_UNICODE); ?>;
    </script>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <?php if (!$user): ?>
    <!-- ════════════════════════════════
         HERO
    ════════════════════════════════ -->
    <section class="hero" id="hero">
        <div class="hero__inner">
            <div class="hero__content">
                <span class="section__subtitle">🇦🇴 Feito em Angola</span>
                <h1 class="hero__title">
                    Os Melhores<br/>
                    <em>Produtos Angolanos</em><br/>
                    Ao Teu Alcance
                </h1>
                <p class="hero__desc">
                    Do Café do Huambo ao Mel do Planalto — descobre o melhor
                    da nossa terra, entregue com excelência em toda Angola.
                </p>
                <div class="hero__btns">
                    <button class="btn btn--primary" onclick="scrollToSection('produtos')">
                        <i data-lucide="shopping-bag"></i> Explorar Catálogo
                    </button>
                    <button class="btn btn--outline" onclick="scrollToSection('categorias')">
                        Ver Colecções
                    </button>
                </div>
                <div class="hero__stats">
                    <div class="stat">
                        <h4>500+</h4>
                        <p>Produtos</p>
                    </div>
                    <div class="stat">
                        <h4>18</h4>
                        <p>Províncias</p>
                    </div>
                    <div class="stat">
                        <h4>24h</h4>
                        <p>Entrega</p>
                    </div>
                </div>
            </div>
            
            <div class="hero__visual" aria-hidden="true">
                <div class="floating-card floating-card--1"><i data-lucide="coffee"></i> Café Premium</div>
                <div class="floating-card floating-card--2"><i data-lucide="droplets"></i> Mel Puro</div>
                <div class="floating-card floating-card--3"><i data-lucide="crown"></i> Qualidade</div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ════════════════════════════════
         CATEGORIAS
    ════════════════════════════════ -->
    <section class="section" id="categorias">
        <div class="container">
            <div class="section__header">
                <div>
                    <span class="section__subtitle">Coleção</span>
                    <h2 class="section__title">Navega por Categoria</h2>
                </div>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <button class="btn btn--outline btn--sm" onclick="openAdminModal('category')">
                        <i data-lucide="plus"></i> Add Categoria
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="cats-grid" id="catsGrid">
                <div class="cat-card active" onclick="filterByCategory('all', this)">
                    <span class="cat-card__icon"><i data-lucide="layout-grid"></i></span>
                    <span class="cat-card__name">Todos</span>
                    <span class="cat-card__count"><?php echo count($products); ?> item</span>
                </div>
                
                <?php foreach ($categories as $cat): ?>
                    <div class="cat-card" onclick="filterByCategory('<?php echo $cat['id']; ?>', this)">
                        <span class="cat-card__icon"><?php echo $cat['icon']; ?></span>
                        <span class="cat-card__name"><?php echo $cat['name']; ?></span>
                        <?php 
                            // Contar produtos nesta categoria
                            $count = count(array_filter($products, fn($p) => $p['category_id'] === $cat['id']));
                        ?>
                        <span class="cat-card__count"><?php echo $count; ?> item</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════
         PRODUTOS
    ════════════════════════════════ -->
    <section class="section" id="produtos">
        <div class="container">
            <div class="section__header">
                <div>
                    <span class="section__subtitle">O Nosso Catálogo</span>
                    <h2 class="section__title">Produto Disponível</h2>
                </div>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <button class="btn btn--primary btn--sm" onclick="openAdminModal('product')">
                        <i data-lucide="plus"></i> Add Produto
                    </button>
                <?php endif; ?>
            </div>

            <div class="products-grid" id="productsGrid">
                <?php foreach ($jsProducts as $p): ?>
                    <div class="product-card" data-category="<?php echo $p['category']; ?>">
                        <div class="product-img-wrap" onclick="openModal(<?php echo $p['id']; ?>)">
                            <?php if (!empty($p['image'])): ?>
                                <img src="<?php echo $p['image']; ?>" alt="<?php echo $p['name']; ?>" class="product-img">
                            <?php else: ?>
                                <div class="product-placeholder">
                                    <span>📦</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($p['badge']): ?>
                                <span class="product-badge badge--<?php echo $p['badge']; ?>">
                                    <?php echo $p['badge'] === 'promo' ? 'Promoção' : 'Novo'; ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($user && $user['role'] === 'admin'): ?>
                                <button class="admin-delete-btn" onclick="event.stopPropagation(); adminDeleteProduct(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>')" title="Eliminar Produto">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?php echo $p['category']; ?></span>
                            <h3 class="product-name"><?php echo $p['name']; ?></h3>
                            <span class="product-price"><?php echo formatPrice($p['price']); ?></span>
                            
                            <button class="add-btn" onclick="addToCart(<?php echo $p['id']; ?>)">
                                <i data-lucide="plus"></i> Adicionar à Mala
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
</html>
