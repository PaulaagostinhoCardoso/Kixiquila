/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — app.js (LUXURY VERSION)
═══════════════════════════════════════════════════════════ */

let cart = [];
let wishlist = [];
let activeCategory = 'all';
let searchTerm = '';
let toastTimer = null;

/**
 * Utilitários
 */
function formatKz(value) {
    return new Intl.NumberFormat('pt-AO').format(value) + ' Kz';
}

/**
 * Renderiza ícone (Lucide ou Emoji)
 */
function renderIcon(icon, size = 18) {
    if (!icon) return '<span>📦</span>';
    const isLucide = /^[a-z0-9-]+$/.test(icon) && icon.length > 3;
    if (isLucide) {
        return `<i data-lucide="${icon}" style="width:${size}px; height:${size}px;"></i>`;
    }
    return `<span style="font-size:${size}px; display:inline-block; line-height:1;">${icon}</span>`;
}

/**
 * Toast Notifications
 */
function showToast(message, icon = 'check-circle') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    toast.classList.remove('active');
    clearTimeout(toastTimer);

    setTimeout(() => {
        toast.innerHTML = `<i data-lucide="${icon}"></i> <span>${message}</span>`;
        toast.classList.add('active');
        if (window.lucide) lucide.createIcons();

        toastTimer = setTimeout(() => {
            toast.classList.remove('active');
        }, 3000);
    }, 50);
}

/**
 * Scroll Suave
 */
function scrollToSection(id) {
    const el = document.getElementById(id);
    if (el) {
        window.scrollTo({
            top: el.offsetTop - 80,
            behavior: 'smooth'
        });
    }
}

/**
 * Filtros por Categoria
 */
function filterByCategory(id, el) {
    activeCategory = id;

    // UI feedback
    document.querySelectorAll('.cat-card, .filter-btn').forEach(card => {
        card.classList.remove('active');
    });
    if (el) el.classList.add('active');

    applyFilters();
}

/**
 * Aplica Filtros (Categoria + Pesquisa)
 */
function applyFilters() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;

    const cards = grid.querySelectorAll('.product-card');
    const q = searchTerm.toLowerCase();

    cards.forEach(card => {
        const cat = card.getAttribute('data-category');
        const name = card.querySelector('.product-name')?.textContent.toLowerCase() || '';

        const matchesCat = (activeCategory === 'all' || cat === activeCategory);
        const matchesSearch = (searchTerm === '' || name.includes(q));

        if (matchesCat && matchesSearch) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Carrinho - Lógica
 */
function addToCart(id) {
    if (typeof PRODUCTS === 'undefined') return;
    const product = PRODUCTS.find(p => p.id === id);
    if (!product) return;

    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ ...product, qty: 1 });
    }

    updateCartUI();
    showToast(`"${product.name}" na tua mala!`, 'shopping-bag');
}

function updateCartUI() {
    const count = cart.reduce((sum, item) => sum + item.qty, 0);
    const countEl = document.getElementById('cartCount');
    if (countEl) countEl.textContent = count;

    const body = document.getElementById('cartBody');
    const foot = document.getElementById('cartFoot');

    if (!body) return;

    if (cart.length === 0) {
        body.innerHTML = `
            <div class="cart-empty" style="text-align:center; color:rgba(255,255,255,0.2); padding:4rem 2rem;">
                <i data-lucide="shopping-cart" style="width:48px; height:48px; margin-bottom:1rem; opacity:0.1;"></i>
                <p style="font-size:0.9rem;">A tua mala está vazia.</p>
            </div>
        `;
        if (foot) foot.style.display = 'none';
    } else {
        body.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item__img">${renderIcon(item.icon, 24)}</div>
                <div class="cart-item__info">
                    <h4 class="cart-item__name">${item.name}</h4>
                    <p class="cart-item__price">${formatKz(item.price)} x ${item.qty}</p>
                    <div class="cart-item__qty-controls">
                        <button onclick="changeQty(${item.id}, -1)" class="qty-btn">${renderIcon('minus', 12)}</button>
                        <span>${item.qty}</span>
                        <button onclick="changeQty(${item.id}, 1)" class="qty-btn">${renderIcon('plus', 12)}</button>
                    </div>
                </div>
                <button class="cart-item__remove" onclick="removeFromCart(${item.id})">${renderIcon('trash-2', 18)}</button>
            </div>
        `).join('');

        const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const totalEl = document.getElementById('cartTotal');
        if (totalEl) totalEl.textContent = formatKz(total);
        if (foot) foot.style.display = 'block';
    }
    if (window.lucide) lucide.createIcons();
}

function changeQty(id, delta) {
    const item = cart.find(item => item.id === id);
    if (!item) return;

    item.qty += delta;
    if (item.qty <= 0) {
        removeFromCart(id);
    } else {
        updateCartUI();
    }
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartUI();
}

function clearCart() {
    if (confirm('Esvaziar toda a mala?')) {
        cart = [];
        updateCartUI();
        showToast('Mala vazia.', 'trash-2');
    }
}

/**
 * Checkout - Fluxo
 */
function checkout() {
    if (cart.length === 0) {
        showToast('A tua mala está vazia!', 'shopping-cart');
        return;
    }
    
    // Reset para o passo 1
    nextStep(1);
    
    const modal = document.getElementById('checkoutModal');
    const overlay = document.getElementById('modalOverlay');
    const cartSidebar = document.getElementById('cartSidebar');
    const mainOverlay = document.getElementById('overlay');

    // Fechar carrinho se estiver aberto
    if (cartSidebar) cartSidebar.classList.remove('active');
    if (mainOverlay) mainOverlay.classList.remove('active');

    if (modal) modal.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
}

function closeCheckout() {
    const modal = document.getElementById('checkoutModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) modal.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function nextStep(step) {
    // Esconder todos os passos
    document.querySelectorAll('.checkout-step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.progress-step').forEach(el => el.classList.remove('active'));

    // Mostrar passo actual
    const stepEl = document.getElementById(`step${step}`);
    if (stepEl) stepEl.classList.add('active');

    // Actualizar barra de progresso
    for (let i = 1; i <= step; i++) {
        const prog = document.querySelector(`.progress-step[data-step="${i}"]`);
        if (prog) prog.classList.add('active');
    }

    if (window.lucide) lucide.createIcons();
}

function prevStep(step) {
    nextStep(step);
}

function selectPayment(el) {
    document.querySelectorAll('.payment-card').forEach(card => card.classList.remove('active'));
    el.classList.add('active');
}

async function finalizePurchase() {
    const btn = document.querySelector('#step2 .main-action');
    const originalText = btn.innerHTML;
    
    // Efeito de loading
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Processando...';
    if (window.lucide) lucide.createIcons();

    // Simular delay de rede
    await new Promise(resolve => setTimeout(resolve, 2000));

    // Gerar tempo de entrega aleatório (entre 2h e 48h)
    const hours = Math.floor(Math.random() * 46) + 2;
    const timeText = hours < 24 ? `${hours} Horas` : `1 Dia e ${hours - 24} Horas`;
    const deliveryTimeEl = document.getElementById('deliveryTime');
    if (deliveryTimeEl) deliveryTimeEl.textContent = timeText;

    // Limpar carrinho
    cart = [];
    updateCartUI();

    // Ir para sucesso
    nextStep(3);
    showToast('Compra finalizada com sucesso!', 'check-circle');
}

/**
 * Modal - Detalhes
 */
function openModal(id) {
    if (typeof PRODUCTS === 'undefined') return;
    const p = PRODUCTS.find(x => x.id === id);
    if (!p) return;

    const modal = document.getElementById('productModal');
    const content = document.getElementById('modalContent');
    const overlay = document.getElementById('modalOverlay');

    if (!modal || !content) {
        showToast("Detalhes em breve...", "info");
        return;
    }

    content.innerHTML = `
        <div class="modal__grid">
            <div class="modal__visual">
                ${renderIcon(p.icon, 80)}
            </div>
            <div class="modal__info">
                <span class="modal__cat">${p.category}</span>
                <h2 class="modal__title">${p.name}</h2>
                <p class="modal__price">${formatKz(p.price)}</p>
                <p class="modal__desc">${p.desc || 'Um produto premium seleccionado com rigor para garantir a melhor experiência e autenticidade angolana.'}</p>
                <div class="modal__actions">
                    <button class="btn btn--primary" onclick="addToCart(${p.id}); closeModal();">
                        <i data-lucide="shopping-bag"></i> Adicionar à mala
                    </button>
                </div>
            </div>
        </div>
    `;

    modal.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
}

function closeModal() {
    const modal = document.getElementById('productModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) modal.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

/**
 * Inicialização
 */
document.addEventListener('DOMContentLoaded', () => {
    // Sidebar logic
    const cartToggle = document.getElementById('cartToggle');
    const cartClose = document.getElementById('cartClose');
    const overlay = document.getElementById('overlay');
    const cartSidebar = document.getElementById('cartSidebar');

    if (cartToggle) cartToggle.onclick = () => {
        cartSidebar.classList.add('active');
        overlay.classList.add('active');
    };

    if (cartClose) cartClose.onclick = () => {
        cartSidebar.classList.remove('active');
        overlay.classList.remove('active');
    };

    if (overlay) overlay.onclick = () => {
        cartSidebar.classList.remove('active');
        overlay.classList.remove('active');
    };

    // Search logic
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchTerm = e.target.value.trim();
            applyFilters();
        });
    }

    // Modal Close logic
    const modalClose = document.getElementById('modalClose');
    const modalOverlay = document.getElementById('modalOverlay');
    if (modalClose) modalClose.onclick = closeModal;
    if (modalOverlay) modalOverlay.onclick = closeModal;

    if (window.lucide) lucide.createIcons();
});

/**
 * AUTHENTICATION
 */
let authMode = 'login';

function openAuthModal(mode = 'login') {
    authMode = mode;
    const modal = document.getElementById('authModal');
    const overlay = document.getElementById('modalOverlay');

    document.getElementById('authTitle').textContent = mode === 'login' ? 'Login' : 'Criar Conta';
    document.getElementById('authDesc').textContent = mode === 'login'
        ? 'Bem-vindo de volta! Introduz os teus dados.'
        : 'Junta-te à elite. Começa a tua jornada hoje.';

    document.getElementById('registerFields').style.display = mode === 'login' ? 'none' : 'flex';
    document.getElementById('authForm').querySelector('button[type="submit"]').textContent = mode === 'login' ? 'Entrar' : 'Registar';
    document.getElementById('authSwitch').innerHTML = mode === 'login'
        ? 'Ainda não tens conta? <a href="#" onclick="toggleAuthMode(event)" style="color:var(--clr-gold);">Cria uma aqui</a>'
        : 'Já tens conta? <a href="#" onclick="toggleAuthMode(event)" style="color:var(--clr-gold);">Faz login aqui</a>';

    modal.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) modal.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function toggleAuthMode(e) {
    if (e) e.preventDefault();
    openAuthModal(authMode === 'login' ? 'register' : 'login');
}

function toggleAdminKey(role) {
    const field = document.getElementById('adminKeyField');
    if (field) {
        field.style.display = role === 'admin' ? 'flex' : 'none';
    }
}

async function submitAuth(e) {
    if (e) e.preventDefault();

    const email = document.getElementById('authEmail').value;
    const pass = document.getElementById('authPass').value;
    const name = document.getElementById('authName').value;
    const phone = document.getElementById('authPhone').value;
    const role = document.getElementById('authRole')?.value || 'client';
    const adminKey = document.getElementById('authAdminKey')?.value || '';

    const action = authMode === 'login' ? 'login' : 'register';
    const body = { email, password: pass };

    if (authMode === 'register') {
        body.name = name;
        body.phone = phone;
        body.role = role;
        if (role === 'admin') {
            body.admin_key = adminKey;
        }
    }

    try {
        const res = await fetch(`api/auth.php?action=${action}`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'check-circle');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Erro na autenticação', 'alert-circle');
        }
    } catch (err) {
        showToast('Erro de ligação ao servidor', 'wifi-off');
    }
}

async function handleLogout() {
    if (!confirm('Desejas terminar a sessão?')) return;

    try {
        const res = await fetch('api/auth.php?action=logout', {
            method: 'POST',
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            showToast('Sessão terminada', 'log-out');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (err) {
        location.reload();
    }
}

/**
 * ADMIN STOREFRONT ACTIONS
 */
function openAdminModal(type) {
    const modal = document.getElementById('adminModal');
    const body = document.getElementById('adminModalBody');
    const overlay = document.getElementById('modalOverlay');

    if (type === 'category') {
        body.innerHTML = `
            <h2 class="modal__title">Nova Categoria</h2>
            <p class="modal__desc">Cria uma nova colecção para a loja.</p>
            <form onsubmit="submitAdminCategory(event)" style="display:flex; flex-direction:column; gap:1.5rem;">
                <div class="form-group" style="text-align:left;">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Nome da Categoria</label>
                    <input type="text" id="admCatName" required class="btn btn--outline" style="width:100%; text-transform:none;">
                </div>
                <div class="form-group" style="text-align:left;">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Ícone (Emoji)</label>
                    <input type="text" id="admCatIcon" placeholder="📦" class="btn btn--outline" style="width:100%; text-transform:none;">
                </div>
                <button type="submit" class="btn btn--primary" style="width:100%; margin-top:1rem;">Guardar Categoria</button>
            </form>
        `;
    } else {
        const catOptions = CATEGORIES.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        body.innerHTML = `
            <h2 class="modal__title">Novo Produto</h2>
            <p class="modal__desc">Adiciona um item ao catálogo.</p>
            <form onsubmit="submitAdminProduct(event)" style="display:flex; flex-direction:column; gap:1rem; text-align:left;">
                <div class="form-group">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Nome</label>
                    <input type="text" id="admProdName" required class="btn btn--outline" style="width:100%; text-transform:none;">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Categoria</label>
                        <select id="admProdCat" class="btn btn--outline" style="width:100%; text-transform:none; padding:0.8rem;">${catOptions}</select>
                    </div>
                    <div class="form-group">
                        <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Preço (Kz)</label>
                        <input type="number" id="admProdPrice" required class="btn btn--outline" style="width:100%; text-transform:none;">
                    </div>
                </div>
                <div class="form-group">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Ícone / Emoji</label>
                    <input type="text" id="admProdIcon" placeholder="📦" class="btn btn--outline" style="width:100%; text-transform:none;">
                </div>
                <div class="form-group">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase;">Descrição</label>
                    <textarea id="admProdDesc" required class="btn btn--outline" style="width:100%; text-transform:none; min-height:80px; font-family:inherit; padding:0.8rem;"></textarea>
                </div>
                <button type="submit" class="btn btn--primary" style="width:100%; margin-top:1rem; padding:1rem;">Publicar Produto</button>
            </form>
        `;
    }

    modal.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAdminModal() {
    const modal = document.getElementById('adminModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) modal.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

async function adminDeleteProduct(id, name) {
    if (!confirm(`Tens a certeza que desejas eliminar definitivamente o produto "${name}"?`)) return;

    try {
        const res = await fetch(`api/admin.php?action=products&id=${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            showToast('Produto eliminado com sucesso', 'trash-2');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Erro ao eliminar produto', 'alert-circle');
        }
    } catch (err) {
        showToast('Erro de ligação', 'wifi-off');
    }
}

async function submitAdminCategory(e) {
    e.preventDefault();
    const name = document.getElementById('admCatName').value;
    const icon = document.getElementById('admCatIcon').value;

    try {
        const res = await fetch('api/admin.php?action=categories', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, icon })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Categoria criada!', 'check');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error, 'alert-circle');
        }
    } catch (err) { showToast('Erro de ligação', 'wifi-off'); }
}

async function submitAdminProduct(e) {
    e.preventDefault();
    const body = {
        name: document.getElementById('admProdName').value,
        category_id: document.getElementById('admProdCat').value,
        price: document.getElementById('admProdPrice').value,
        icon: document.getElementById('admProdIcon').value || '📦',
        description: document.getElementById('admProdDesc').value,
        origin: 'Angola',
        stock: 10
    };

    try {
        const res = await fetch('api/admin.php?action=products', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            showToast('Produto publicado!', 'check');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error, 'alert-circle');
        }
    } catch (err) { showToast('Erro de ligação', 'wifi-off'); }
}
