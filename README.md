# 🛍️ Kixikila Market — Back-End PHP

Back-end completo em PHP puro + MySQL para o projecto Kixikila Market.

---

## 📁 Estrutura do Back-End

```
kixikila-backend/
│
├── database.sql              ← Script SQL (cria BD + tabelas + dados)
├── admin.html                ← Painel de Administração
├── api.js                    ← Cola o front-end à API (copia para js/)
├── .htaccess                 ← Rotas Apache
│
├── config/
│   ├── database.php          ← Ligação PDO ao MySQL
│   └── helpers.php           ← Funções partilhadas (CORS, JSON, sessão)
│
└── api/
    ├── auth/index.php        ← Login, Registo, Logout, /me
    ├── products/index.php    ← Listar, Filtrar, Pesquisar produtos
    ├── orders/index.php      ← Checkout, Encomendas, Promo
    └── admin/index.php       ← CRUD produtos, gerir encomendas e users
```

---

## 🚀 Instalação (XAMPP)

### Passo 1 — Copiar os ficheiros
```
C:\xampp\htdocs\
├── kixikila-market\      ← o teu front-end existente
└── kixikila-backend\     ← esta pasta (back-end)
```

### Passo 2 — Criar a base de dados
1. Abre o **phpMyAdmin**: http://localhost/phpmyadmin
2. Clica em **"Novo"** → cria a base de dados `kixikila_market`
3. Selecciona a BD, vai ao separador **SQL**
4. Cola o conteúdo de `database.sql` e clica **Executar**

### Passo 3 — Configurar ligação MySQL
Abre `config/database.php` e ajusta:
```php
define('DB_USER', 'root');   // utilizador MySQL (XAMPP: root)
define('DB_PASS', '');       // senha MySQL (XAMPP: vazio por defeito)
```

### Passo 4 — Activar mod_rewrite no Apache
1. Abre `C:\xampp\apache\conf\httpd.conf`
2. Procura `#LoadModule rewrite_module` e remove o `#`
3. Procura `AllowOverride None` (perto de `htdocs`) e muda para `AllowOverride All`
4. Reinicia o Apache no XAMPP Control Panel

### Passo 5 — Ligar o front-end à API
No teu `index.html`, **adiciona** este script **antes** do `app.js`:
```html
<!-- Cola o ficheiro api.js na pasta js/ do teu front-end -->
<script src="js/api.js"></script>
<script src="js/app.js"></script>
```

Copia `api.js` para a pasta `js/` do teu front-end.

### Passo 6 — Testar
- **API produtos:** http://localhost/kixikila-backend/api/products/index.php
- **Painel Admin:** http://localhost/kixikila-backend/admin.html
  - Email: `admin@kixikila.ao`
  - Senha: `Admin@1234`

---

## 🔌 Endpoints da API

### Autenticação — `/api/auth/index.php`

| Método | Action     | Descrição                    |
|--------|------------|------------------------------|
| POST   | `register` | Criar conta                  |
| POST   | `login`    | Fazer login                  |
| POST   | `logout`   | Terminar sessão               |
| GET    | `me`       | Dados do utilizador actual   |

**Exemplo — Login:**
```json
POST /api/auth/index.php?action=login
{ "email": "user@exemplo.com", "password": "minhasenha" }
```

---

### Produtos — `/api/products/index.php`

| Método | Parâmetros          | Descrição                    |
|--------|---------------------|------------------------------|
| GET    | *(sem params)*      | Listar todos os produtos      |
| GET    | `?categories`       | Listar categorias com contagem|
| GET    | `?id=5`             | Detalhe de produto            |
| GET    | `?category=bebidas` | Filtrar por categoria         |
| GET    | `?badge=promo`      | Filtrar por badge             |
| GET    | `?search=café`      | Pesquisa por texto            |
| GET    | `?sort=price-asc`   | Ordenar (price-asc/desc/name) |
| GET    | `?page=1&limit=10`  | Paginação                    |

---

### Encomendas — `/api/orders/index.php`

| Método | Action            | Descrição                      |
|--------|-------------------|--------------------------------|
| POST   | `checkout`        | Criar encomenda (requer itens) |
| POST   | `validate-promo`  | Validar código promo           |
| GET    | *(sem params)*    | Minhas encomendas (auth)        |
| GET    | `?id=N`           | Detalhe de encomenda           |

**Exemplo — Checkout:**
```json
POST /api/orders/index.php?action=checkout
{
  "name":    "João Silva",
  "email":   "joao@email.com",
  "phone":   "+244 923 000 000",
  "address": "Luanda, Ingombota, Rua 5 N.º 10",
  "promo_code": "ANGOLA15",
  "items": [
    { "id": 1, "qty": 2 },
    { "id": 3, "qty": 1 }
  ]
}
```

---

### Admin — `/api/admin/index.php` *(só admin)*

| Método | Action           | Descrição                       |
|--------|------------------|---------------------------------|
| GET    | `stats`          | Estatísticas do dashboard       |
| GET    | `products`       | Listar todos os produtos        |
| POST   | `products`       | Criar produto                   |
| PUT    | `products&id=N`  | Editar produto                  |
| DELETE | `products&id=N`  | Desactivar produto              |
| GET    | `orders`         | Listar todas as encomendas      |
| PUT    | `orders&id=N`    | Actualizar status da encomenda  |
| GET    | `users`          | Listar utilizadores             |

---

## 🔒 Segurança implementada

- ✅ Senhas com **bcrypt** (cost=12) — nunca em texto puro
- ✅ **PDO Prepared Statements** — protecção contra SQL Injection
- ✅ **Sessões PHP seguras** (httponly, samesite)
- ✅ Sanitização de inputs com `htmlspecialchars` + `strip_tags`
- ✅ Verificação de **roles** (client / admin) em cada endpoint
- ✅ **Soft delete** em produtos (não apaga dados históricos)
- ✅ Validação de stock antes de confirmar encomenda
- ✅ **CORS** configurado (restringe origens em produção)

---

## 🎨 Painel de Administração

Acede em: **http://localhost/kixikila-backend/admin.html**

Funcionalidades:
- 📊 Dashboard com estatísticas em tempo real
- 📦 CRUD completo de produtos (criar, editar, desactivar)
- 🛒 Gerir encomendas e actualizar status
- 👤 Listar utilizadores registados

---

## 🛠️ Próximas melhorias sugeridas

- Upload de imagens reais para produtos
- Envio de email de confirmação (PHPMailer)
- Autenticação com JWT para SPA/mobile
- Painel de análise de vendas por período
- Integração com gateway de pagamento angolano (Multicaixa Express)

---

*Feito com ❤️ em Luanda, Angola 🇦🇴*
