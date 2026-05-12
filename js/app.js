/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — app.js (LUXURY VERSION)
═══════════════════════════════════════════════════════════ */

let cart = [];
let wishlist = [];
let activeCategory = 'all';
let searchTerm = '';
let toastTimer = null;

function formatKz(value) {
    return new Intl.NumberFormat('pt-AO').format(value) + ' Kz';
}

/**
 * Toast Notifications - Melhorado para esconder corretamente
 */
function showToast(message, icon = 'check-circle') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    // Resetar se já estiver ativo
    toast.classList.remove('active');
    clearTimeout(toastTimer);

    // Pequeno delay para reiniciar a animação se necessário
    setTimeout(() => {
        toast.innerHTML = `<i data-lucide="${icon}"></i> <span>${message}</span>`;
        toast.classList.add('active');
        lucide.createIcons();

        toastTimer = setTimeout(() => {
            toast.classList.remove('active');
        }, 3000);
    }, 50);
}

function scrollToSection(id) {
    const el = document.getElementById(id);
    if (el) {
        window.scrollTo({
            top: el.offsetTop - 80,
            behavior: 'smooth'
        });
    }
}

function filterByCategory(id, el) {
    activeCategory = id;
    
    document.querySelectorAll('.cat-card, .filter-btn').forEach(card => {
        card.classList.remove('active');
    });
    if (el) el.classList.add('active');

    const grid = document.getElementById('productsGrid');
    const cards = grid.querySelectorAll('.product-card');

    cards.forEach(card => {
        const cat = card.getAttribute('data-category');
        if (activeCategory === 'all' || cat === activeCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

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

    if (cart.length === 0) {
        body.innerHTML = '<p style="text-align:center; color:rgba(255,255,255,0.2); padding-top:4rem; font-size:0.9rem;">A tua mala está vazia.</p>';
        if (foot) foot.style.display = 'none';
    } else {
        body.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item__img"><i data-lucide="${item.icon}"></i></div>
                <div class="cart-item__info">
                    <h4 class="cart-item__name">${item.name}</h4>
                    <p class="cart-item__price">${formatKz(item.price)} x ${item.qty}</p>
                </div>
                <button class="cart-item__remove" onclick="removeFromCart(${item.id})"><i data-lucide="trash-2" size="18"></i></button>
            </div>
        `).join('');
        
        const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const totalEl = document.getElementById('cartTotal');
        if (totalEl) totalEl.textContent = formatKz(total);
        if (foot) foot.style.display = 'block';
    }
    lucide.createIcons();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartUI();
}

function openModal(id) {
    showToast("Detalhes em breve...", "info");
}

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

    lucide.createIcons();
});
