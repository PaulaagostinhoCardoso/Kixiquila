/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — app.js
   Lógica principal: carrinho, filtros, modal, pesquisa, etc.
═══════════════════════════════════════════════════════════ */

/* ─────────────── ESTADO GLOBAL ─────────────── */
let cart      = [];        // itens no carrinho
let wishlist  = [];        // produtos em lista de desejos
let activeCategory = 'all';
let activeBadge    = 'all';
let activeSort     = 'default';
let searchTerm     = '';

/* ─────────────── UTILITÁRIOS ─────────────── */

/**
 * Formata número em Kwanza angolano
 * Ex: 4500 → "4.500 Kz"
 */
function formatKz(value) {
  return value.toLocaleString('pt-AO') + ' Kz';
}

/**
 * Mostra uma mensagem toast temporária
 */
let toastTimer = null;
function showToast(message) {
  const el = document.getElementById('toast');
  el.textContent = message;
  el.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 3000);
}

/**
 * Anima o contador do carrinho
 */
function bumpCartCount() {
  const el = document.getElementById('cartCount');
  el.classList.remove('bump');
  void el.offsetWidth; // reflow para reiniciar animação
  el.classList.add('bump');
  setTimeout(() => el.classList.remove('bump'), 350);
}

/**
 * Scroll suave até secção
 */
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Copiar código de desconto
 */
function copyCode() {
  navigator.clipboard.writeText('ANGOLA15').then(() => {
    showToast('✅ Código "ANGOLA15" copiado!');
  }).catch(() => {
    showToast('📋 Código: ANGOLA15 (15% de desconto)');
  });
}

/* ─────────────── RENDER CATEGORIAS ─────────────── */
function renderCategories() {
  const grid = document.getElementById('catsGrid');
  grid.innerHTML = CATEGORIES.map(cat => `
    <div
      class="cat-card fade-in ${activeCategory === cat.id ? 'active' : ''}"
      onclick="selectCategory('${cat.id}')"
      role="button"
      aria-pressed="${activeCategory === cat.id}"
      tabindex="0"
    >
      <span class="cat-card__icon">${cat.icon}</span>
      <div class="cat-card__name">${cat.name}</div>
      <div class="cat-card__count">${cat.count} produtos</div>
    </div>
  `).join('');

  // Activar fade-in
  requestAnimationFrame(() => {
    document.querySelectorAll('.cat-card.fade-in').forEach((el, i) => {
      setTimeout(() => el.classList.add('visible'), i * 60);
    });
  });
}

function selectCategory(id) {
  activeCategory = id;
  activeBadge    = 'all';
  searchTerm     = '';
  document.getElementById('searchInput').value = '';
  document.getElementById('sortSelect').value  = 'default';
  activeSort = 'default';

  renderCategories();
  updateFilterBar();
  renderProducts();
  scrollToSection('produtos');
}

/* ─────────────── FILTER BAR ─────────────── */
const FILTERS = [
  { id: 'all',   label: '🏪 Todos'       },
  { id: 'promo', label: '🏷️ Promoções'   },
  { id: 'new',   label: '🆕 Novidades'   },
  { id: 'dest',  label: '⭐ Destaques'   },
];

function updateFilterBar() {
  const bar = document.getElementById('filterBar');
  bar.innerHTML = FILTERS.map(f => `
    <button
      class="filter-btn ${activeBadge === f.id ? 'active' : ''}"
      onclick="setFilter('${f.id}')"
      aria-pressed="${activeBadge === f.id}"
    >${f.label}</button>
  `).join('');
}

function setFilter(id) {
  activeBadge    = id;
  activeCategory = 'all';
  renderCategories();
  updateFilterBar();
  renderProducts();
}

/* ─────────────── FILTRAR + ORDENAR PRODUTOS ─────────────── */
function getFilteredProducts() {
  let list = [...PRODUCTS];

  // Pesquisa por texto
  if (searchTerm) {
    const q = searchTerm.toLowerCase();
    list = list.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.desc.toLowerCase().includes(q) ||
      p.origin.toLowerCase().includes(q)
    );
    return list;
  }

  // Filtro por categoria
  if (activeCategory !== 'all') {
    list = list.filter(p => p.category === activeCategory);
  }

  // Filtro por badge
  if (activeBadge === 'promo') list = list.filter(p => p.badge === 'promo');
  if (activeBadge === 'new')   list = list.filter(p => p.badge === 'new');
  if (activeBadge === 'dest')  list = list.filter(p => p.rating >= 4.8);

  // Ordenação
  if (activeSort === 'price-asc')  list.sort((a, b) => a.price - b.price);
  if (activeSort === 'price-desc') list.sort((a, b) => b.price - a.price);
  if (activeSort === 'name')       list.sort((a, b) => a.name.localeCompare(b.name));

  return list;
}

/* ─────────────── RENDER PRODUTOS ─────────────── */
function renderProducts() {
  const list   = getFilteredProducts();
  const grid   = document.getElementById('productsGrid');
  const empty  = document.getElementById('emptyState');
  const count  = document.getElementById('productsCount');

  // Actualizar contagem
  count.textContent = `${list.length} produto${list.length !== 1 ? 's' : ''} encontrado${list.length !== 1 ? 's' : ''}`;

  // Estado vazio
  if (list.length === 0) {
    grid.innerHTML  = '';
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';

  grid.innerHTML = list.map(p => {
    const wished = wishlist.includes(p.id);
    return `
      <article class="product-card fade-in" data-id="${p.id}">
        <!-- Badge -->
        ${p.badge ? `<span class="product-badge product-badge--${p.badge}">${p.badge === 'promo' ? 'Promoção' : 'Novo'}</span>` : ''}

        <!-- Wishlist -->
        <button
          class="product-wish ${wished ? 'liked' : ''}"
          onclick="toggleWish(event, ${p.id})"
          aria-label="${wished ? 'Remover da lista de desejos' : 'Adicionar à lista de desejos'}"
        >${wished ? '❤️' : '🤍'}</button>

        <!-- Imagem -->
        <div class="product-img" onclick="openModal(${p.id})">${p.icon}</div>

        <!-- Info -->
        <div class="product-info" onclick="openModal(${p.id})">
          <p class="product-cat">${getCatName(p.category)} · ${p.origin}</p>
          <h3 class="product-name">${p.name}</h3>
          <p class="product-desc">${p.desc}</p>

          <div class="product-footer">
            <div class="product-price">
              ${p.oldPrice ? `<span class="product-price__old">${formatKz(p.oldPrice)}</span>` : ''}
              <span class="product-price__current">${formatKz(p.price)}</span>
            </div>
            <button
              class="add-to-cart"
              onclick="addToCart(event, ${p.id})"
              aria-label="Adicionar ${p.name} ao carrinho"
              title="Adicionar ao carrinho"
            >+</button>
          </div>
        </div>
      </article>
    `;
  }).join('');

  // Fade-in escalonado
  requestAnimationFrame(() => {
    document.querySelectorAll('.product-card.fade-in').forEach((el, i) => {
      setTimeout(() => el.classList.add('visible'), i * 60);
    });
  });
}

function getCatName(catId) {
  return CATEGORIES.find(c => c.id === catId)?.name || catId;
}

function resetFilters() {
  activeCategory = 'all';
  activeBadge    = 'all';
  activeSort     = 'default';
  searchTerm     = '';
  document.getElementById('searchInput').value = '';
  document.getElementById('sortSelect').value  = 'default';
  renderCategories();
  updateFilterBar();
  renderProducts();
}

/* ─────────────── CARRINHO ─────────────── */

/**
 * Adicionar produto ao carrinho
 */
function addToCart(event, id) {
  if (event) event.stopPropagation();

  const product  = PRODUCTS.find(p => p.id === id);
  const existing = cart.find(item => item.id === id);

  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({ ...product, qty: 1 });
  }

  updateCartCount();
  bumpCartCount();
  renderCartItems();
  showToast(`✅ "${product.name}" adicionado ao carrinho!`);
}

/**
 * Remover produto do carrinho
 */
function removeFromCart(id) {
  const product = cart.find(item => item.id === id);
  cart = cart.filter(item => item.id !== id);
  updateCartCount();
  renderCartItems();
  if (product) showToast(`🗑️ "${product.name}" removido do carrinho.`);
}

/**
 * Alterar quantidade
 */
function changeQty(id, delta) {
  const item = cart.find(item => item.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) {
    removeFromCart(id);
  } else {
    updateCartCount();
    renderCartItems();
  }
}

/**
 * Esvaziar carrinho
 */
function clearCart() {
  if (cart.length === 0) return;
  cart = [];
  updateCartCount();
  renderCartItems();
  showToast('🗑️ Carrinho esvaziado.');
}

/**
 * Actualizar contadores
 */
function updateCartCount() {
  const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
  document.getElementById('cartCount').textContent = totalQty;
}

/**
 * Render itens no carrinho sidebar
 */
function renderCartItems() {
  const body = document.getElementById('cartBody');
  const foot = document.getElementById('cartFoot');

  if (cart.length === 0) {
    body.innerHTML = `
      <div class="cart-empty">
        <div class="cart-empty__icon">🛒</div>
        <h4>Carrinho vazio</h4>
        <p>Adiciona produtos para começar a compra!</p>
      </div>
    `;
    foot.style.display = 'none';
    return;
  }

  body.innerHTML = cart.map(item => `
    <div class="cart-item">
      <div class="cart-item__img">${item.icon}</div>
      <div class="cart-item__info">
        <div class="cart-item__name">${item.name}</div>
        <div class="cart-item__price">${formatKz(item.price * item.qty)}</div>
        <div class="cart-item__qty">
          <button class="qty-btn" onclick="changeQty(${item.id}, -1)" aria-label="Diminuir">−</button>
          <span class="qty-num">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty(${item.id}, +1)" aria-label="Aumentar">+</button>
        </div>
      </div>
      <button class="cart-item__remove" onclick="removeFromCart(${item.id})" aria-label="Remover">🗑️</button>
    </div>
  `).join('');

  const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  document.getElementById('cartSubtotal').textContent = formatKz(subtotal);
  document.getElementById('cartTotal').textContent    = formatKz(subtotal);
  foot.style.display = 'flex';
}

/* ─────────────── OPEN / CLOSE CART ─────────────── */
function openCart() {
  document.getElementById('cartSidebar').classList.add('open');
  document.getElementById('overlay').classList.add('open');
  renderCartItems();
}

function closeCart() {
  document.getElementById('cartSidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
}

/* ─────────────── CHECKOUT ─────────────── */
function checkout() {
  if (cart.length === 0) {
    showToast('⚠️ O teu carrinho está vazio!');
    return;
  }
  const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  cart = [];
  updateCartCount();
  closeCart();
  renderCartItems();
  showToast(`🎉 Compra de ${formatKz(total)} realizada com sucesso! Obrigado!`);
}

/* ─────────────── WISHLIST ─────────────── */
function toggleWish(event, id) {
  event.stopPropagation();
  const product = PRODUCTS.find(p => p.id === id);
  const idx = wishlist.indexOf(id);

  if (idx === -1) {
    wishlist.push(id);
    showToast(`❤️ "${product.name}" adicionado à lista de desejos!`);
  } else {
    wishlist.splice(idx, 1);
    showToast(`🤍 "${product.name}" removido da lista de desejos.`);
  }

  document.getElementById('wishCount').textContent = wishlist.length;
  renderProducts(); // re-render para actualizar botão
}

/* ─────────────── MODAL DETALHE ─────────────── */
function openModal(id) {
  const p = PRODUCTS.find(x => x.id === id);
  if (!p) return;

  const wished = wishlist.includes(p.id);

  document.getElementById('modalContent').innerHTML = `
    <div class="modal__img-area">${p.icon}</div>
    <div class="modal__details">
      <p class="modal__cat">${getCatName(p.category)} · Origem: ${p.origin}</p>
      <h2 class="modal__name">${p.name}</h2>
      <p class="modal__desc">${p.desc}</p>

      <div style="margin-bottom:.8rem; font-size:.85rem; color:#888;">
        ⭐ ${p.rating} · ${p.reviews} avaliações
      </div>

      <div class="modal__price">
        ${p.oldPrice ? `<span class="modal__old">${formatKz(p.oldPrice)}</span>` : ''}
        ${formatKz(p.price)}
      </div>

      <div class="modal__actions">
        <button class="btn btn--primary" onclick="addToCart(null, ${p.id}); closeModal();">
          🛒 Adicionar ao Carrinho
        </button>
        <button class="btn btn--ghost" onclick="toggleWish(event, ${p.id}); closeModal();">
          ${wished ? '❤️ Remover dos Desejos' : '🤍 Adicionar aos Desejos'}
        </button>
      </div>
    </div>
  `;

  document.getElementById('productModal').classList.add('open');
  document.getElementById('modalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('productModal').classList.remove('open');
  document.getElementById('modalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

/* ─────────────── PESQUISA ─────────────── */
document.getElementById('searchInput').addEventListener('input', function () {
  searchTerm     = this.value.trim();
  activeCategory = 'all';
  activeBadge    = 'all';
  renderCategories();
  updateFilterBar();
  renderProducts();
});

/* ─────────────── ORDENAÇÃO ─────────────── */
document.getElementById('sortSelect').addEventListener('change', function () {
  activeSort = this.value;
  renderProducts();
});

/* ─────────────── BOTÃO CARRINHO (HEADER) ─────────────── */
document.getElementById('cartToggle').addEventListener('click', openCart);
document.getElementById('cartClose').addEventListener('click', closeCart);

document.getElementById('overlay').addEventListener('click', closeCart);

/* ─────────────── MODAL ─────────────── */
document.getElementById('modalClose').addEventListener('click', closeModal);
document.getElementById('modalOverlay').addEventListener('click', closeModal);

// Fechar modal com Escape
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    closeModal();
    closeCart();
  }
});

/* ─────────────── HAMBURGER (MOBILE) ─────────────── */
const hamburger = document.getElementById('hamburger');
const nav       = document.getElementById('nav');

hamburger.addEventListener('click', function () {
  nav.classList.toggle('open');
  const open = nav.classList.contains('open');
  document.body.style.overflow = open ? 'hidden' : '';
});

// Fechar nav ao clicar num link
nav.querySelectorAll('.nav__link').forEach(link => {
  link.addEventListener('click', () => {
    nav.classList.remove('open');
    document.body.style.overflow = '';
  });
});

/* ─────────────── SCROLL: HEADER SHRINK ─────────────── */
window.addEventListener('scroll', function () {
  const header = document.getElementById('header');
  if (window.scrollY > 40) {
    header.style.boxShadow = '0 4px 30px rgba(0,0,0,.5)';
  } else {
    header.style.boxShadow = '0 2px 20px rgba(0,0,0,.4)';
  }
});

/* ─────────────── KEYBOARD: cat-card ─────────────── */
document.addEventListener('keydown', function (e) {
  if (e.key === 'Enter' && e.target.classList.contains('cat-card')) {
    e.target.click();
  }
});

/* ─────────────── INICIALIZAÇÃO ─────────────── */
function init() {
  renderCategories();
  updateFilterBar();
  renderProducts();
}

// Iniciar quando o DOM estiver pronto
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
