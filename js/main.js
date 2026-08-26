/* =============================================
   XUPING Joyería — Scripts Tienda Principal
   ============================================= */

// ─── TOAST NOTIFICATIONS ─────────────────────────────────
function showToast(message, type = 'default', duration = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const icons = { default: '💎', success: '✅', error: '❌' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${icons[type] || '💎'}</span><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ─── ACTUALIZAR BADGE DEL CARRITO ────────────────────────
function updateCartBadge(count) {
    const badges = document.querySelectorAll('#cart-badge, .cart-badge');
    badges.forEach(badge => {
        badge.textContent = count;
        if (count > 0) {
            badge.classList.remove('hidden');
            badge.style.animation = 'none';
            requestAnimationFrame(() => {
                badge.style.animation = 'scaleIn 0.3s ease';
            });
        } else {
            badge.classList.add('hidden');
        }
    });
}

// ─── AGREGAR AL CARRITO ──────────────────────────────────
function addToCart(productoId, btn) {
    if (!btn || btn.disabled) return;

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Agregando...';

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('producto_id', productoId);

    fetch('api/carrito.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = '✅ Agregado!';
                btn.classList.add('added');
                updateCartBadge(data.cart_count);
                showToast(data.message || '¡Producto agregado al carrito!', 'success');

                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('added');
                    btn.disabled = false;
                }, 2000);
            } else {
                showToast(data.message || 'No se pudo agregar', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(() => {
            showToast('Error de conexión. Intenta de nuevo.', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

// ─── ACTUALIZAR CANTIDAD (carrito.php) ───────────────────
function updateQty(itemId, newQty, precio) {
    if (newQty < 1) {
        removeItem(itemId);
        return;
    }
    if (newQty > 99) return;

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('item_id', itemId);
    formData.append('cantidad', newQty);

    fetch('api/carrito.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const qtyEl = document.getElementById(`qty-${itemId}`);
                if (qtyEl) qtyEl.textContent = newQty;

                const subtotalEl = document.getElementById(`item-subtotal-${itemId}`);
                if (subtotalEl) {
                    const precio = PRODUCT_PRICES[itemId] || 0;
                    subtotalEl.innerHTML = `Q${(precio * newQty).toFixed(2)} <span style="font-size: 0.75rem; color: var(--text-dim); font-weight:400;">(Q${precio.toFixed(2)} c/u)</span>`;
                }

                const totalEl = document.getElementById('cart-total-display');
                if (totalEl) totalEl.textContent = `Q${data.cart_total}`;

                updateCartBadge(data.cart_count);

                // Actualizar botones de qty
                const minusBtn = document.getElementById(`qty-minus-${itemId}`);
                const plusBtn = document.getElementById(`qty-plus-${itemId}`);
                if (minusBtn) minusBtn.setAttribute('onclick', `updateQty(${itemId}, ${newQty - 1}, ${precio})`);
                if (plusBtn) plusBtn.setAttribute('onclick', `updateQty(${itemId}, ${newQty + 1}, ${precio})`);
            }
        })
        .catch(() => showToast('Error al actualizar cantidad', 'error'));
}

// ─── ELIMINAR ITEM DEL CARRITO ───────────────────────────
function removeItem(itemId) {
    const itemEl = document.getElementById(`cart-item-${itemId}`);
    if (itemEl) {
        itemEl.style.opacity = '0.5';
        itemEl.style.transform = 'translateX(-20px)';
    }

    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('item_id', itemId);

    fetch('api/carrito.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (itemEl) itemEl.remove();
                updateCartBadge(data.cart_count);

                const totalEl = document.getElementById('cart-total-display');
                if (totalEl) totalEl.textContent = `Q${data.cart_total}`;

                showToast(data.message || 'Producto eliminado', 'default');

                const remaining = document.querySelectorAll('.cart-item');
                if (remaining.length === 0) {
                    setTimeout(() => location.reload(), 500);
                }
            }
        })
        .catch(() => showToast('Error al eliminar', 'error'));
}

// ─── VACIAR CARRITO ───────────────────────────────────────
function clearCart() {
    if (!confirm('¿Deseas vaciar todo el carrito?')) return;

    const formData = new FormData();
    formData.append('action', 'clear');

    fetch('api/carrito.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(0);
                location.reload();
            }
        })
        .catch(() => showToast('Error al vaciar el carrito', 'error'));
}

// ─── SELECCIÓN DE MÉTODO DE PAGO (checkout.php) ─────────
function selectPayment(method) {
    // Quitar clase 'selected' de todas las opciones
    document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));

    // Aplicar al seleccionado
    const selected = document.getElementById(`opt-${method}`);
    if (selected) selected.classList.add('selected');

    // Mostrar/ocultar campos
    const cardFields = document.getElementById('card-fields');
    const visaBox = document.getElementById('visa-link-box');

    if (cardFields) cardFields.classList.toggle('visible', method === 'tarjeta');
    if (visaBox) visaBox.classList.toggle('visible', method === 'visa_link');
}

// ─── FORMATEAR NÚMERO DE TARJETA ─────────────────────────
function formatCardNumber(input) {
    let val = input.value.replace(/\D/g, '').substring(0, 16);
    input.value = val.replace(/(\d{4})(?=\d)/g, '$1 ');
}

function formatExpiry(input) {
    let val = input.value.replace(/\D/g, '').substring(0, 4);
    if (val.length >= 2) val = val.substring(0, 2) + '/' + val.substring(2);
    input.value = val;
}

// ─── FORMULARIO DE CHECKOUT ──────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const checkoutForm = document.getElementById('checkout-form');
    if (!checkoutForm) return;

    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitCheckout();
    });
});

function submitCheckout() {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    // Validar campos obligatorios
    const nombre = document.getElementById('nombre')?.value.trim();
    const email  = document.getElementById('email')?.value.trim();
    if (!nombre || !email) {
        showToast('Por favor completa tu nombre y email', 'error');
        return;
    }

    const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailReg.test(email)) {
        showToast('El email no es válido', 'error');
        return;
    }

    // Validar tarjeta si está seleccionada
    const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value;
    if (metodo === 'tarjeta') {
        const cardNum = document.getElementById('card_number')?.value.replace(/\s/g, '');
        if (!cardNum || cardNum.length < 13) {
            showToast('Número de tarjeta inválido', 'error');
            return;
        }
        const cvv = document.getElementById('card_cvv')?.value;
        if (!cvv || cvv.length < 3) {
            showToast('CVV inválido', 'error');
            return;
        }
        const expiry = document.getElementById('card_expiry')?.value;
        if (!expiry || !expiry.includes('/')) {
            showToast('Fecha de vencimiento inválida', 'error');
            return;
        }
    }

    // Mostrar modal de procesamiento
    const modal = document.getElementById('processing-modal');
    if (modal) modal.style.display = 'flex';

    const btn = document.getElementById('btn-pay');
    if (btn) btn.disabled = true;

    const formData = new FormData(form);
    formData.append('action', 'pay');

    // Simular delay de red para Visa Link
    const delay = metodo === 'visa_link' ? 2500 : 1800;

    setTimeout(() => {
        fetch('api/pago.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (modal) modal.style.display = 'none';
                if (data.success) {
                    window.location.href = `pago_exitoso.php?pedido=${data.pedido_id}&ref=${data.referencia}`;
                } else {
                    showToast(data.message || 'Error al procesar el pago', 'error');
                    if (btn) btn.disabled = false;
                }
            })
            .catch(() => {
                if (modal) modal.style.display = 'none';
                showToast('Error de conexión. Intenta de nuevo.', 'error');
                if (btn) btn.disabled = false;
            });
    }, delay);
}
