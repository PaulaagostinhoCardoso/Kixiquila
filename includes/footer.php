<!-- ════════════════════════════════
     FOOTER
════════════════════════════════ -->
<footer class="section" style="background:#050505; border-top: 1px solid var(--clr-border);">
    <div class="container">
        <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap: 4rem; margin-bottom: 5rem;">
            <div>
                <a href="#" class="logo" style="margin-bottom:2rem;">Kixikila<em>Market</em></a>
                <p style="color:var(--clr-text-dim); max-width:400px; line-height:1.8;">
                    Elevando a tradição angolana ao nível global. 
                    Produtos seleccionados com rigor para quem valoriza a excelência e a autenticidade.
                </p>
            </div>
            <div>
                <h4 style="font-family:var(--font-bold); font-size:1.2rem; margin-bottom:2rem; color:var(--clr-gold); text-transform:uppercase; letter-spacing:2px;">Kixikila</h4>
                <ul style="list-style:none; color:var(--clr-text-dim);">
                    <li style="margin-bottom:1rem;"><a href="#hero" class="footer-link">Início</a></li>
                    <li style="margin-bottom:1rem;"><a href="#categorias" class="footer-link">Coleções</a></li>
                    <li style="margin-bottom:1rem;"><a href="#produtos" class="footer-link">Catálogo</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-family:var(--font-bold); font-size:1.2rem; margin-bottom:2rem; color:var(--clr-gold); text-transform:uppercase; letter-spacing:2px;">Contacto</h4>
                <p style="color:var(--clr-text-dim); margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;"><i data-lucide="map-pin" size="14"></i> Luanda, Angola</p>
                <p style="color:var(--clr-text-dim); font-weight:700; display:flex; align-items:center; gap:0.5rem;"><i data-lucide="mail" size="14"></i> info@kixikila.ao</p>
            </div>
        </div>
        
        <div style="padding-top:2rem; border-top:1px solid var(--clr-border); text-align:center; color:rgba(255,255,255,0.2); font-size:0.8rem;">
            <p>&copy; 2026 Kixikila Market. Criado com Excelência.</p>
        </div>
    </div>
</footer>

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay"></div>

<!-- Product Modal -->
<div class="modal" id="productModal">
    <button class="modal__close" id="modalClose"><i data-lucide="x"></i></button>
    <div class="modal__body" id="modalContent">
        <!-- JS -->
    </div>
</div>

<!-- Auth Modal (Login/Register) -->
<div class="modal" id="authModal">
    <button class="modal__close" onclick="closeAuthModal()"><i data-lucide="x"></i></button>
    <div class="modal__info" style="padding:4rem; text-align:center;">
        <h2 id="authTitle" class="modal__title">Login</h2>
        <p id="authDesc" class="modal__desc">Bem-vindo de volta! Introduz os teus dados.</p>
        
        <form id="authForm" onsubmit="submitAuth(event)" style="display:flex; flex-direction:column; gap:1rem; max-width:400px; margin:0 auto;">
            <div id="registerFields" style="display:none; flex-direction:column; gap:1rem;">
                <div class="form-group" style="text-align:left;">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Nome Completo</label>
                    <input type="text" id="authName" class="btn btn--outline" placeholder="Teu nome" style="width:100%; text-transform:none; font-weight:400; background:rgba(255,255,255,0.03); padding:0.8rem;">
                </div>
                <div class="form-group" style="text-align:left;">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Telefone</label>
                    <input type="text" id="authPhone" class="btn btn--outline" placeholder="Ex: 9xx xxx xxx" style="width:100%; text-transform:none; font-weight:400; background:rgba(255,255,255,0.03); padding:0.8rem;">
                </div>
                <div class="form-group" style="text-align:left;">
                    <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Tipo de Conta</label>
                    <select id="authRole" class="btn btn--outline" onchange="toggleAdminKey(this.value)" style="width:100%; text-transform:none; font-weight:400; background:rgba(255,255,255,0.03); padding:0.8rem; cursor:pointer;">
                        <option value="client" selected>Cliente</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div id="adminKeyField" class="form-group" style="display:none; text-align:left;">
                    <label style="color:#ff4d4d; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Chave de Administrador</label>
                    <input type="password" id="authAdminKey" class="btn btn--outline" placeholder="Chave secreta" style="width:100%; text-transform:none; font-weight:400; border-color:#ff4d4d; background:rgba(255,77,77,0.05); padding:0.8rem;">
                </div>
            </div>
            
            <div class="form-group" style="text-align:left;">
                <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Email</label>
                <input type="email" id="authEmail" required class="btn btn--outline" placeholder="email@exemplo.ao" style="width:100%; text-transform:none; font-weight:400; background:rgba(255,255,255,0.03); padding:0.8rem;">
            </div>
            <div class="form-group" style="text-align:left;">
                <label style="color:var(--clr-gold); font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Senha</label>
                <input type="password" id="authPass" required class="btn btn--outline" placeholder="mínimo 8 caracteres" style="width:100%; text-transform:none; font-weight:400; background:rgba(255,255,255,0.03); padding:0.8rem;">
            </div>
            
            <button type="submit" class="btn btn--primary" style="width:100%; margin-top:1.5rem; padding:1.2rem; font-size:1rem;">Entrar</button>
            <p id="authSwitch" style="font-size:0.85rem; color:var(--clr-text-dim); margin-top:1rem;">
                Ainda não tens conta? <a href="#" onclick="toggleAuthMode(event)" style="color:var(--clr-gold); font-weight:700;">Cria uma aqui</a>
            </p>
        </form>
    </div>
</div>

<!-- Admin Modal (Add Product/Category) -->
<div class="modal" id="adminModal">
    <button class="modal__close" onclick="closeAdminModal()"><i data-lucide="x"></i></button>
    <div class="modal__body" id="adminModalBody">
        <!-- JS -->
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal" id="checkoutModal">
    <button class="modal__close" onclick="closeCheckout()"><i data-lucide="x"></i></button>
    <div class="modal__info checkout-container">
        
        <!-- Progress Bar -->
        <div class="checkout-progress">
            <div class="progress-step active" data-step="1">
                <span class="step-num">1</span>
                <label>Entrega</label>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="2">
                <span class="step-num">2</span>
                <label>Pagamento</label>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="3">
                <span class="step-num">3</span>
                <label>Sucesso</label>
            </div>
        </div>

        <!-- Step 1: Delivery -->
        <div class="checkout-step active" id="step1">
            <h2 class="modal__title">Detalhes de Entrega</h2>
            <p class="modal__desc">Diz-nos onde devemos entregar os teus produtos premium.</p>
            
            <form onsubmit="nextStep(2); return false;" class="checkout-form">
                <div class="form-group full-width">
                    <label>Nome Completo</label>
                    <input type="text" required class="btn btn--outline" placeholder="Como te chamas?">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="tel" required class="btn btn--outline" placeholder="9xx xxx xxx">
                </div>
                <div class="form-group">
                    <label>Província</label>
                    <select required class="btn btn--outline">
                        <option value="Luanda">Luanda</option>
                        <option value="Benguela">Benguela</option>
                        <option value="Huambo">Huambo</option>
                        <option value="Huíla">Huíla</option>
                        <option value="Outra">Outra Província</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Endereço Detalhado</label>
                    <textarea required class="btn btn--outline" placeholder="Rua, Bairro, Nº da Casa..." style="min-height:80px;"></textarea>
                </div>
                <div class="full-width" style="margin-top:1rem;">
                    <button type="submit" class="btn btn--primary" style="width:100%;">Continuar para Pagamento</button>
                </div>
            </form>
        </div>

        <!-- Step 2: Payment -->
        <div class="checkout-step" id="step2">
            <h2 class="modal__title">Método de Pagamento</h2>
            <p class="modal__desc">Escolhe a forma mais conveniente para ti.</p>
            
            <div class="payment-methods">
                <div class="payment-card active" onclick="selectPayment(this)">
                    <div class="payment-card__icon"><i data-lucide="smartphone"></i></div>
                    <div class="payment-card__info">
                        <h4>MultiCaixa Express</h4>
                        <p>Pagamento instantâneo via App</p>
                    </div>
                    <div class="payment-card__check"><i data-lucide="check"></i></div>
                </div>
                <div class="payment-card" onclick="selectPayment(this)">
                    <div class="payment-card__icon"><i data-lucide="landmark"></i></div>
                    <div class="payment-card__info">
                        <h4>Transferência Bancária</h4>
                        <p>IBAN será fornecido no final</p>
                    </div>
                    <div class="payment-card__check"><i data-lucide="check"></i></div>
                </div>
                <div class="payment-card" onclick="selectPayment(this)">
                    <div class="payment-card__icon"><i data-lucide="banknote"></i></div>
                    <div class="payment-card__info">
                        <h4>Pagamento na Entrega</h4>
                        <p>Disponível apenas em Luanda</p>
                    </div>
                    <div class="payment-card__check"><i data-lucide="check"></i></div>
                </div>
            </div>

            <div class="checkout-actions">
                <button onclick="prevStep(1)" class="btn btn--outline">Voltar</button>
                <button onclick="finalizePurchase()" class="btn btn--primary main-action">Finalizar Compra</button>
            </div>
        </div>

        <!-- Step 3: Success -->
        <div class="checkout-step" id="step3">
            <div class="success-screen">
                <div class="success-icon">
                    <i data-lucide="check-circle"></i>
                </div>
                <h2 class="modal__title">Encomenda Confirmada!</h2>
                <p class="modal__desc">Obrigado por escolheres o Kixikila Market. A tua encomenda está a ser processada.</p>
                
                <div class="delivery-eta">
                    <div class="eta-box">
                        <i data-lucide="truck"></i>
                        <div class="eta-info">
                            <span>Tempo Estimado de Entrega</span>
                            <h3 id="deliveryTime">24 Horas</h3>
                        </div>
                    </div>
                </div>

                <button onclick="closeCheckout(); location.reload();" class="btn btn--primary" style="width:100%; margin-top:2rem;">Voltar à Loja</button>
            </div>
        </div>

    </div>
</div>

<!-- Toast Container -->
<div id="toast">
    <!-- JS -->
</div>

<!-- Scripts -->
<script src="js/app.js"></script>
</body>
</html>
