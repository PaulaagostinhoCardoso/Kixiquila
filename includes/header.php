<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kixikila Market — Premium Angolan Products</title>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header" id="header">
    <div class="container header__inner">
        <a href="index.php" class="logo">
            <i data-lucide="crown"></i>
            Kixikila<em>Market</em>
        </a>
        
        <nav class="nav">
            <a href="#hero" class="nav__link active">Início</a>
            <a href="#categorias" class="nav__link">Coleção</a>
            <a href="#produtos" class="nav__link">Produto</a>
            <a href="#" class="nav__link">Sobre</a>
        </nav>
        
        <div class="header__actions">
            <div class="search-bar">
                <i data-lucide="search" size="18"></i>
                <input type="text" id="searchInput" placeholder="Procurar...">
            </div>
            
            <button class="cart-btn" id="cartToggle">
                <i data-lucide="shopping-bag"></i>
                <span id="cartCount">0</span>
            </button>

            <?php if ($user): ?>
                <div class="user-menu" style="display:flex; align-items:center; gap:1rem; margin-left:1rem;">
                    <span style="font-size:0.8rem; font-weight:700;">Olá, <?php echo explode(' ', $user['name'])[0]; ?></span>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin.html" class="btn btn--outline btn--sm" style="padding:0.4rem 0.8rem;">Admin</a>
                    <?php endif; ?>
                    <button onclick="handleLogout()" class="icon-btn" title="Sair" style="background:none; border:none; color:var(--clr-text-dim); cursor:pointer;">
                        <i data-lucide="log-out" size="18"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="auth-btns" style="display:flex; gap:0.5rem; margin-left:1rem;">
                    <button onclick="openAuthModal('login')" class="btn btn--outline btn--sm" style="padding:0.4rem 0.8rem;">Login</button>
                    <button onclick="openAuthModal('register')" class="btn btn--primary btn--sm" style="padding:0.4rem 0.8rem;">Registar</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="cartSidebar">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:3rem;">
        <h3 class="sidebar__title">Tua Mala</h3>
        <button id="cartClose" style="color:#fff"><i data-lucide="x" size="32"></i></button>
    </div>
    
    <div class="cart-items" id="cartBody">
        <!-- JS -->
    </div>
    
    <div class="sidebar__footer" id="cartFoot" style="display:none;">
        <div class="total-row">
            <span>Total</span>
            <span id="cartTotal">0 Kz</span>
        </div>
        <button class="btn btn--primary" style="width:100%">Finalizar Compra</button>
    </div>
</aside>
