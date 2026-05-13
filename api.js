/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — js/api.js
   Camada de integração: front-end ↔ API PHP

   COMO USAR: adiciona este ficheiro ao index.html ANTES do app.js
   <script src="js/api.js"></script>
   <script src="js/app.js"></script>
═══════════════════════════════════════════════════════════ */

/* ── URL base da API — ajusta se necessário ── */
const API_BASE = 'http://localhost/kixikila-backend/api';

/* ── Utilizador autenticado (estado global) ── */
let currentUser = null;

/* ─────────────────────────────────────────────────────────
   HELPER: chamada à API
───────────────────────────────────────────────────────── */
async function apiCall(endpoint, method = 'GET', body = null) {
  try {
    const opts = {
      method,
      credentials: 'include',   // envia cookies de sessão
      headers: { 'Content-Type': 'application/json' },
    };
    if (body) opts.body = JSON.stringify(body);

    const res  = await fetch(API_BASE + endpoint, opts);
    const json = await res.json();
    return json;
  } catch (err) {
    console.error('[API] Erro:', err);
    return { success: false, error: 'Erro de ligação ao servidor.' };
  }
}

/* ═══════════════════════════════════════════════════════════
   PRODUTOS — carrega da API em vez do data.js estático
═══════════════════════════════════════════════════════════ */

/**
 * Carrega produtos da API com filtros
 * Substitui o array PRODUCTS estático do data.js
 */
async function loadProductsFromAPI(params = {}) {
  const qs = new URLSearchParams(params).toString();
  const r  = await apiCall(`/products/index.php${qs ? '?' + qs : ''}`);
  if (r.success) return r.data;
  console.error('[API] Erro ao carregar produtos:', r.error);
  return { products: [], total: 0 };
}

/**
 * Carrega categorias da API
 */
async function loadCategoriesFromAPI() {
  const r = await apiCall('/products/index.php?categories');
  return r.success ? r.data : [];
}

/* ═══════════════════════════════════════════════════════════
   CHECKOUT — envia carrinho para a API
═══════════════════════════════════════════════════════════ */

/**
 * Checkout completo: valida promo + cria encomenda
 * Chama esta função em vez da checkout() do app.js
 */
async function checkoutWithAPI() {
  if (cart.length === 0) {
    showToast('⚠️ O teu carrinho está vazio!');
    return;
  }

  // Mostrar formulário de checkout
  showCheckoutForm();
}

function showCheckoutForm() {
  const overlay = document.getElementById('modalOverlay');
  const modal   = document.getElementById('productModal');
  const content = document.getElementById('modalContent');

  const user = currentUser;

  content.innerHTML = `
    <div style="padding:1.5rem">
      <h2 style="margin-bottom:1.2rem">📦 Finalizar Encomenda</h2>

      <div style="display:grid;gap:.8rem">
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#777;display:block;margin-bottom:.3rem">Nome Completo *</label>
          <input id="co_name" type="text" value="${user?.name || ''}"
            style="width:100%;padding:.55rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.9rem" placeholder="O teu nome"/>
        </div>
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#777;display:block;margin-bottom:.3rem">Email *</label>
          <input id="co_email" type="email" value="${user?.email || ''}"
            style="width:100%;padding:.55rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.9rem" placeholder="email@exemplo.com"/>
        </div>
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#777;display:block;margin-bottom:.3rem">Telefone *</label>
          <input id="co_phone" type="tel"
            style="width:100%;padding:.55rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.9rem" placeholder="+244 9XX XXX XXX"/>
        </div>
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#777;display:block;margin-bottom:.3rem">Endereço de Entrega *</label>
          <textarea id="co_address" rows="2"
            style="width:100%;padding:.55rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.9rem;resize:none"
            placeholder="Bairro, Rua, N.º, Referência…"></textarea>
        </div>
        <div style="display:flex;gap:.5rem">
          <input id="co_promo" type="text" placeholder="Código promo (ex: ANGOLA15)"
            style="flex:1;padding:.55rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.9rem;text-transform:uppercase"/>
          <button onclick="applyPromoCode()"
            style="padding:.55rem 1rem;background:#F4A800;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">
            Aplicar
          </button>
        </div>
        <div id="promoMsg" style="font-size:.82rem;color:green;display:none"></div>
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#777;display:block;margin-bottom:.3rem">Notas (opcional)</label>
          <input id="co_notes" type="text"
            style="width:100%;padding:.55rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.9rem" placeholder="Instruções especiais…"/>
        </div>
      </div>

      <div id="co_summary" style="margin-top:1.2rem;background:#FFF8EE;border-radius:8px;padding:1rem">
        <strong>Resumo:</strong>
        ${cart.map(i => `<div style="display:flex;justify-content:space-between;font-size:.85rem;margin-top:.4rem">
          <span>${i.icon} ${i.name} ×${i.qty}</span>
          <span>${formatKz(i.price * i.qty)}</span>
        </div>`).join('')}
        <hr style="margin:.8rem 0;border-color:#eee"/>
        <div style="display:flex;justify-content:space-between;font-weight:700">
          <span>Total</span>
          <span id="co_total">${formatKz(cart.reduce((s,i) => s + i.price * i.qty, 0))}</span>
        </div>
      </div>

      <button onclick="submitOrder()"
        style="width:100%;margin-top:1rem;padding:.8rem;background:#C8102E;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer">
        ✅ Confirmar Encomenda
      </button>
    </div>
  `;

  modal.classList.add('open');
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

let appliedDiscount = 0;

async function applyPromoCode() {
  const code = document.getElementById('co_promo').value.trim().toUpperCase();
  if (!code) return;

  const r = await apiCall('/orders/index.php?action=validate-promo', 'POST', { code });
  const msg = document.getElementById('promoMsg');
  msg.style.display = 'block';

  if (r.success) {
    appliedDiscount = r.data.discount;
    msg.style.color = 'green';
    msg.textContent = `✅ ${r.message}`;
    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const total    = subtotal - Math.floor(subtotal * appliedDiscount / 100);
    document.getElementById('co_total').textContent = formatKz(total);
  } else {
    appliedDiscount = 0;
    msg.style.color = 'red';
    msg.textContent = '❌ ' + r.error;
  }
}

async function submitOrder() {
  const name    = document.getElementById('co_name').value.trim();
  const email   = document.getElementById('co_email').value.trim();
  const phone   = document.getElementById('co_phone').value.trim();
  const address = document.getElementById('co_address').value.trim();
  const promo   = document.getElementById('co_promo').value.trim().toUpperCase();
  const notes   = document.getElementById('co_notes').value.trim();

  if (!name || !email || !phone || !address) {
    showToast('⚠️ Preenche todos os campos obrigatórios.');
    return;
  }

  const payload = {
    name, email, phone, address, notes,
    promo_code: promo,
    items: cart.map(i => ({ id: i.id, qty: i.qty })),
  };

  const r = await apiCall('/orders/index.php?action=checkout', 'POST', payload);

  if (r.success) {
    cart = [];
    updateCartCount();
    closeModal();
    closeCart();
    renderCartItems();
    showToast(`🎉 ${r.message}`);
  } else {
    showToast('❌ ' + r.error);
  }
}

/* ═══════════════════════════════════════════════════════════
   AUTENTICAÇÃO — login / registo inline
═══════════════════════════════════════════════════════════ */

/**
 * Verificar sessão ao carregar a página
 */
async function checkSession() {
  const r = await apiCall('/auth/index.php?action=me');
  if (r.success) {
    currentUser = r.data;
    updateAuthUI();
  }
}

function updateAuthUI() {
  // Actualiza o botão de wishlist com o nome do utilizador
  const btn = document.getElementById('wishlistBtn');
  if (!btn) return;
  if (currentUser) {
    btn.title = `Olá, ${currentUser.name}`;
  }
}

/**
 * Mostrar modal de login/registo
 */
function showAuthModal(tab = 'login') {
  const content = document.getElementById('modalContent');
  const modal   = document.getElementById('productModal');
  const overlay = document.getElementById('modalOverlay');

  content.innerHTML = `
    <div style="padding:1.5rem;max-width:380px;margin:0 auto">
      <div style="display:flex;gap:.5rem;margin-bottom:1.2rem">
        <button id="tabLogin"    onclick="switchAuthTab('login')"
          style="flex:1;padding:.6rem;border:none;border-radius:6px;cursor:pointer;font-weight:600;background:${tab==='login'?'#C8102E':'#eee'};color:${tab==='login'?'#fff':'#333'}">
          Entrar
        </button>
        <button id="tabRegister" onclick="switchAuthTab('register')"
          style="flex:1;padding:.6rem;border:none;border-radius:6px;cursor:pointer;font-weight:600;background:${tab==='register'?'#C8102E':'#eee'};color:${tab==='register'?'#fff':'#333'}">
          Criar Conta
        </button>
      </div>

      <div id="authLogin" style="display:${tab==='login'?'flex':'none'};flex-direction:column;gap:.7rem">
        <input id="l_email" type="email" placeholder="Email"
          style="padding:.6rem .8rem;border:1px solid #ddd;border-radius:6px"/>
        <input id="l_pass"  type="password" placeholder="Senha"
          style="padding:.6rem .8rem;border:1px solid #ddd;border-radius:6px"/>
        <button onclick="doLogin()"
          style="padding:.7rem;background:#C8102E;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:700">
          Entrar
        </button>
      </div>

      <div id="authRegister" style="display:${tab==='register'?'flex':'none'};flex-direction:column;gap:.7rem">
        <input id="r_name"  type="text"     placeholder="Nome completo"
          style="padding:.6rem .8rem;border:1px solid #ddd;border-radius:6px"/>
        <input id="r_email" type="email"    placeholder="Email"
          style="padding:.6rem .8rem;border:1px solid #ddd;border-radius:6px"/>
        <input id="r_phone" type="tel"      placeholder="Telefone (+244…)"
          style="padding:.6rem .8rem;border:1px solid #ddd;border-radius:6px"/>
        <input id="r_pass"  type="password" placeholder="Senha (mín. 8 caracteres)"
          style="padding:.6rem .8rem;border:1px solid #ddd;border-radius:6px"/>
        <button onclick="doRegister()"
          style="padding:.7rem;background:#C8102E;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:700">
          Criar Conta
        </button>
      </div>
    </div>
  `;

  modal.classList.add('open');
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function switchAuthTab(tab) {
  document.getElementById('authLogin').style.display    = tab === 'login'    ? 'flex' : 'none';
  document.getElementById('authRegister').style.display = tab === 'register' ? 'flex' : 'none';
  document.getElementById('tabLogin').style.background    = tab === 'login'    ? '#C8102E' : '#eee';
  document.getElementById('tabLogin').style.color         = tab === 'login'    ? '#fff' : '#333';
  document.getElementById('tabRegister').style.background = tab === 'register' ? '#C8102E' : '#eee';
  document.getElementById('tabRegister').style.color      = tab === 'register' ? '#fff' : '#333';
}

async function doLogin() {
  const email = document.getElementById('l_email').value.trim();
  const pass  = document.getElementById('l_pass').value;
  if (!email || !pass) { showToast('⚠️ Preenche email e senha.'); return; }

  const r = await apiCall('/auth/index.php?action=login', 'POST', { email, password: pass });
  if (r.success) {
    currentUser = r.data;
    updateAuthUI();
    closeModal();
    showToast(`✅ Bem-vindo, ${currentUser.name}!`);
  } else {
    showToast('❌ ' + r.error);
  }
}

async function doRegister() {
  const name  = document.getElementById('r_name').value.trim();
  const email = document.getElementById('r_email').value.trim();
  const phone = document.getElementById('r_phone').value.trim();
  const pass  = document.getElementById('r_pass').value;

  if (!name || !email || !pass) { showToast('⚠️ Preenche os campos obrigatórios.'); return; }

  const r = await apiCall('/auth/index.php?action=register', 'POST', { name, email, phone, password: pass });
  if (r.success) {
    currentUser = r.data;
    updateAuthUI();
    closeModal();
    showToast(`🎉 Conta criada! Bem-vindo, ${currentUser.name}!`);
  } else {
    showToast('❌ ' + r.error);
  }
}

async function doLogout() {
  await apiCall('/auth/index.php?action=logout', 'POST');
  currentUser = null;
  updateAuthUI();
  showToast('👋 Sessão terminada.');
}

/* ═══════════════════════════════════════════════════════════
   INICIALIZAÇÃO
═══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  checkSession();

  // Sobrepor o botão wishlist para abrir login se não autenticado
  const wishBtn = document.getElementById('wishlistBtn');
  if (wishBtn) {
    wishBtn.addEventListener('click', (e) => {
      if (!currentUser) {
        e.stopImmediatePropagation();
        showToast('🔐 Faz login para usar a lista de desejos!');
        setTimeout(() => showAuthModal('login'), 500);
      }
    }, true);
  }

  // Botão "Finalizar Compra" usa agora a API
  // O checkout() do app.js é substituído por checkoutWithAPI()
  window.checkout = checkoutWithAPI;
});
