<?php
/* ═══════════════════════════════════════════════════════════
   KIXIKILA MARKET — functions.php
   Funções auxiliares
═══════════════════════════════════════════════════════════ */

/**
 * Formata o preço para Kwanza (Kz)
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' Kz';
}

/**
 * Retorna o ícone Lucide correspondente (para uso em data-lucide)
 */
function getIcon($name) {
    return $name ?? 'package';
}
?>
