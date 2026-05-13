-- ═══════════════════════════════════════════════════════════
--  KIXIKILA MARKET — Base de Dados MySQL
--  Execute este ficheiro no phpMyAdmin ou MySQL CLI:
--  mysql -u root -p < database.sql
-- ═══════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS kixikila_market
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kixikila_market;

-- ───────────────────────────────────────────
-- CATEGORIAS
-- ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id         VARCHAR(30)  NOT NULL PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  icon       VARCHAR(10)  NOT NULL,
  active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (id, name, icon) VALUES
  ('alimentacao', 'Alimentação',  '🥘'),
  ('bebidas',     'Bebidas',      '🍺'),
  ('artesanato',  'Artesanato',   '🪘'),
  ('agricultura', 'Agricultura',  '🌿'),
  ('cosmetica',   'Cosmética',    '💆'),
  ('textil',      'Têxtil',       '👗'),
  ('tecnologia',  'Tecnologia',   '📱');

-- ───────────────────────────────────────────
-- PRODUTOS
-- ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  category_id VARCHAR(30)  NOT NULL,
  icon        VARCHAR(10)  NOT NULL DEFAULT '📦',
  price       INT          NOT NULL,         -- em Kwanza (Kz)
  old_price   INT          DEFAULT NULL,
  badge       ENUM('promo','new')  DEFAULT NULL,
  description TEXT         NOT NULL,
  origin      VARCHAR(100) NOT NULL,
  rating      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  reviews     INT          NOT NULL DEFAULT 0,
  stock       INT          NOT NULL DEFAULT 100,
  active      TINYINT(1)   NOT NULL DEFAULT 1,
  image_url   VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO products (name, category_id, icon, price, old_price, badge, description, origin, rating, reviews, stock) VALUES
  ('Café Angolano Premium',       'alimentacao', '☕',  4500, 5500, 'promo', 'Café 100% arábica cultivado nas terras altas do Huambo, torrado artesanalmente. Aroma intenso, sabor encorpado e acidez equilibrada. Um dos melhores cafés de África.', 'Huambo', 4.9, 128, 80),
  ('Mel Puro do Planalto',        'alimentacao', '🍯',  6800, NULL,  'new',  'Mel 100% natural e orgânico, colhido nos apiários das terras altas angolanas. Rico em vitamina C, antioxidantes e propriedades medicinais.', 'Bié', 4.8, 74, 60),
  ('Cerveja Cuca Original',       'bebidas',     '🍺',  1200, NULL,  NULL,   'A cerveja mais icónica de Angola. Produzida desde 1952, com um sabor refrescante e inconfundível que atravessa gerações.', 'Luanda', 4.7, 312, 200),
  ('Sumo de Múcua Natural',       'bebidas',     '🧃',   950, NULL,  'new',  'Sumo extraído do fruto do baobá angolano. Super rico em vitamina C, cálcio e fibra. 100% natural, sem conservantes.', 'Malanje', 4.6, 56, 120),
  ('Máscara Tradicional Cokwe',   'artesanato',  '🎭', 35000, NULL,  NULL,   'Máscara esculpida à mão por artesãos Cokwe do Moxico. Cada peça é única, feita em madeira local com pigmentos naturais.', 'Moxico', 5.0, 22, 10),
  ('Cesta Artesanal de Palmeira', 'artesanato',  '🧺',  8500, 10000, 'promo','Cesta tradicional tecida à mão com folhas de palmeira. Ideal para decoração, mercado ou presente. Durável e biodegradável.', 'Benguela', 4.5, 88, 35),
  ('Óleo de Palma Vermelho',      'agricultura', '🫙',  3200, NULL,  NULL,   'Óleo de palma extraído a frio, 100% natural. Essencial na culinária angolana para a moamba de galinha e outros pratos tradicionais.', 'Cabinda', 4.7, 145, 90),
  ('Farinha de Mandioca (Fuba)',  'alimentacao', '🌾',  2400, NULL,  NULL,   'Farinha de mandioca de alta qualidade para preparar o funge, prato base da gastronomia angolana. Textura fina, sem grumos.', 'Uíge', 4.6, 203, 150),
  ('Capulana Tradicional',        'textil',      '🧣',  7500, 9000,  'promo','Pano capulana com padrões tradicionais angolanos em cores vibrantes. 100% algodão, 2m × 1.1m.', 'Luanda', 4.8, 167, 50),
  ('Manteiga de Karité Pura',     'cosmetica',   '🧴',  5500, NULL,  'new',  'Manteiga de karité não refinada, produzida no Kuando Kubango. Hidratante profundo para pele e cabelo. Sem aditivos químicos.', 'Kuando Kubango', 4.9, 91, 70),
  ('Ngola Smartphone 4G',         'tecnologia',  '📱', 85000, 95000, 'promo','Smartphone assembado em Angola. Ecrã 6.5" HD+, câmara tripla 48MP, bateria 5000mAh, 4G LTE, 128GB. Garantia de 1 ano.', 'Luanda', 4.3, 44, 25),
  ('Pimenta Angolana Moída',      'alimentacao', '🌶️', 1800, NULL,  NULL,   'Pimenta seca e moída de variedades locais, cultivadas no interior de Angola. Picante, aromática e essencial para o jindungo.', 'Huíla', 4.5, 178, 200),
  ('Sabonete Artesanal de Coco',  'cosmetica',   '🧼',  1500, 2000,  'promo','Sabonete feito à mão com óleo de coco angolano. Limpa suavemente, hidrata e deixa a pele macia. Sem parabenos nem sulfatos.', 'Namibe', 4.7, 134, 100),
  ('Jinguba Torrada e Salgada',   'alimentacao', '🥜',  2200, NULL,  NULL,   'Amendoim angolano (jinguba) torrado e salgado artesanalmente. O snack favorito de todo angolano! Crocante e rico em proteínas.', 'Cuando Cubango', 4.8, 287, 180),
  ('Tecido Bazin Bordado',        'textil',      '👘', 15000, NULL,  'new',  'Tecido bazin premium com bordados feitos em Angola. Ideal para trajes de cerimónia e casamentos tradicionais.', 'Luanda', 4.9, 38, 20),
  ('Vinho de Palma (Marufo)',     'bebidas',     '🥥',   800, NULL,  NULL,   'Vinho de palma tradicional angolano, colhido das palmeiras locais. Bebida refrescante com sabor levemente ácido e doce.', 'Bengo', 4.4, 62, 80),
  ('Sementes de Moringa',         'agricultura', '🌿',  2800, NULL,  'new',  'Sementes de moringa angolana, a "árvore da vida". Ricas em vitaminas A, C, E, cálcio e proteínas.', 'Malanje', 4.6, 49, 90),
  ('Bombom de Cajú',              'alimentacao', '🍬',  3500, 4000,  'promo','Bombons artesanais feitos com cajú angolano caramelizado. 100% angolano, perfeito para presentes e sobremesas especiais.', 'Zaire', 4.8, 93, 60),
  ('Estatueta de Ébano',          'artesanato',  '🗿', 22000, NULL,  NULL,   'Estatueta esculpida à mão em madeira de ébano. Representa figuras tradicionais angolanas. Cada peça é única e assinada.', 'Lunda Norte', 4.9, 17, 8),
  ('Ginguba Frita com Mel',       'alimentacao', '🍯',  2900, NULL,  'new',  'Amendoim frito caramelizado com mel angolano puro. Combinação irresistível de doce e salgado. Produzido artesanalmente em Luanda.', 'Luanda', 4.7, 71, 110);

-- ───────────────────────────────────────────
-- UTILIZADORES
-- ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL,
  email         VARCHAR(200) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(20)  DEFAULT NULL,
  address       TEXT         DEFAULT NULL,
  role          ENUM('client','admin') NOT NULL DEFAULT 'client',
  active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin padrão: email=admin@kixikila.ao  senha=Admin@1234
INSERT INTO users (name, email, password_hash, role) VALUES
  ('Administrador', 'admin@kixikila.ao',
   '$2y$12$Vc5vFwnHFj5K5MXLB1SWz.7B1Rl5IajV3M2J8w1YqGYG5D2iSGnS',
   'admin');

-- ───────────────────────────────────────────
-- ENCOMENDAS
-- ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id              INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id         INT          DEFAULT NULL,   -- NULL = guest
  guest_name      VARCHAR(150) DEFAULT NULL,
  guest_email     VARCHAR(200) DEFAULT NULL,
  guest_phone     VARCHAR(20)  DEFAULT NULL,
  delivery_address TEXT        NOT NULL,
  promo_code      VARCHAR(30)  DEFAULT NULL,
  discount_pct    TINYINT      DEFAULT 0,
  subtotal        INT          NOT NULL,
  total           INT          NOT NULL,
  status          ENUM('pending','confirmed','shipped','delivered','cancelled')
                               NOT NULL DEFAULT 'pending',
  notes           TEXT         DEFAULT NULL,
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ───────────────────────────────────────────
-- ITENS DA ENCOMENDA
-- ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT NOT NULL,
  qty        INT NOT NULL DEFAULT 1,
  unit_price INT NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ───────────────────────────────────────────
-- CÓDIGOS PROMO
-- ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS promo_codes (
  id          INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(30) NOT NULL UNIQUE,
  discount    TINYINT     NOT NULL DEFAULT 10,  -- percentagem
  active      TINYINT(1)  NOT NULL DEFAULT 1,
  expires_at  DATE        DEFAULT NULL,
  created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO promo_codes (code, discount, expires_at) VALUES
  ('ANGOLA15', 15, '2025-12-31');
