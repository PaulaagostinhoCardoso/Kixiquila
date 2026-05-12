<?php
require_once 'includes/data.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Expor dados para o JS
echo "<script>const PRODUCTS = " . json_encode($PRODUCTS) . ";</script>";
?>

<!-- ════════════════════════════════
     HERO: MODERN LUXURY
════════════════════════════════ -->
<section class="hero" id="hero">
    <div class="container hero__inner">
        <div class="hero__content">
           
            <h1 class="hero__title">
                Os Melhores<br/>
                <em>Produtos Angolanos</em><br/>
                ao teu alcance.
            </h1>
            <p class="hero__desc">
                Uma curadoria exclusiva do melhor que a nossa terra produz. 
                Qualidade artesanal, sabor autêntico e entrega personalizada.
            </p>
            <div class="hero__btns">
                <button class="btn btn--primary" onclick="scrollToSection('produtos')">
                    Explorar Colecção
                </button>
                <button class="btn btn--outline" onclick="scrollToSection('categorias')">
                    Ver Categorias
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
        <div class="hero__visual">
            <!-- 3 Blocos Flutuantes com algo no meio -->
            <div class="floating-card floating-card--1"><i data-lucide="award"></i> Qualidade Ouro</div>
            <div class="floating-card floating-card--2"><i data-lucide="coffee"></i> Café Premium</div>
            <div class="floating-card floating-card--3"><i data-lucide="map-pin"></i> 100% Angolano</div>
            
            <!-- Elemento Central Decorativo -->
            <div style="width: 300px; height: 300px; background: radial-gradient(circle, var(--clr-gold-dim) 0%, transparent 70%); border-radius: 50%; filter: blur(40px); opacity: 0.5;"></div>
        </div>
    </div>
    
</section>

<!-- ════════════════════════════════
     CATEGORIAS
════════════════════════════════ -->
<section class="section" id="categorias">
    <div class="container">
        <div class="section__header">
            <span class="section__subtitle">Descobre</span>
            <h2 class="section__title">Colecções</h2>
        </div>
        <div class="cats-grid" id="catsGrid">
            <?php foreach ($CATEGORIES as $cat): ?>
                <div class="cat-card <?php echo $cat['id'] === 'all' ? 'active' : ''; ?>" 
                     onclick="filterByCategory('<?php echo $cat['id']; ?>', this)">
                    <i data-lucide="<?php echo $cat['icon']; ?>" class="cat-card__icon"></i>
                    <span class="cat-card__name"><?php echo $cat['name']; ?></span>
                    <span class="cat-card__count"><?php echo $cat['count']; ?> itens</span>
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
        <div class="products__header" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:4rem;">
            <div>
                <span class="section__subtitle">Catálogo</span>
                <h2 class="section__title">Produtos Exclusivos</h2>
            </div>
            <div class="filters" id="filterBar">
                <button class="filter-btn active" onclick="filterByCategory('all', this)">Todos</button>
                <button class="filter-btn" onclick="filterByCategory('alimentacao', this)">Alimentação</button>
                <button class="filter-btn" onclick="filterByCategory('bebidas', this)">Bebidas</button>
                <button class="filter-btn" onclick="filterByCategory('artesanato', this)">Artesanato</button>
            </div>
        </div>

        <div class="products-grid" id="productsGrid">
            <?php foreach ($PRODUCTS as $p): ?>
                <div class="product-card" data-category="<?php echo $p['category']; ?>">
                    <div class="product-img-wrap" onclick="openModal(<?php echo $p['id']; ?>)">
                        <i data-lucide="<?php echo $p['icon']; ?>"></i>
                    </div>
                    
                    <div class="product-info">
                        <span class="product-category"><?php echo $p['category']; ?></span>
                        <h3 class="product-name"><?php echo $p['name']; ?></h3>
                        <span class="product-price"><?php echo formatPrice($p['price']); ?></span>
                        
                        <button class="add-btn" onclick="addToCart(<?php echo $p['id']; ?>)">
                            <i data-lucide="shopping-bag"></i> Adicionar à mala
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
