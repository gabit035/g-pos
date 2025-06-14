/**
 * Módulo de carrito para nueva venta
 * Dependencias: utils.js
 */

const PosCart = (function($) {
    'use strict';
    
    // Estado del carrito
    let cart = {
        items: [],
        subtotal: 0,
        total: 0,
        amountReceived: 0,
        change: 0
    };
    
    /**
     * Añadir un item al carrito
     */
    function addItem(item) {
        // Validar que el item tenga los datos necesarios
        if (!item.id || !item.name || isNaN(parseFloat(item.price))) {
            console.error('Error: Item inválido', item);
            return false;
        }
        
        // Asegurarnos que tenga cantidad
        item.quantity = item.quantity || 1;
        
        // Añadir al carrito
        cart.items.push(item);
        
        // Actualizar interfaz
        updateCart();
        
        console.log("Añadido al carrito:", item);
        console.log("Carrito actual:", cart);
        
        return true;
    }
    
    /**
     * Eliminar un item del carrito por su índice
     */
    function removeItem(index) {
        if (index >= 0 && index < cart.items.length) {
            cart.items.splice(index, 1);
            updateCart();
            return true;
        }
        return false;
    }
    
    /**
     * Actualizar cantidades
     */
    function updateQuantity(index, quantity) {
        if (index >= 0 && index < cart.items.length) {
            cart.items[index].quantity = parseInt(quantity) || 1;
            updateCart();
            return true;
        }
        return false;
    }
    
    /**
     * Vaciar el carrito
     */
    function emptyCart() {
        cart.items = [];
        updateCart();
    }
    
    /**
     * Actualizar la visualización del carrito
     */
    function updateCart() {
        // Mostrar en el DOM (simplificado)
        const $cartItems = $('#wp-pos-cart-items');
        let html = '';
        
        if (cart.items.length === 0) {
            $cartItems.html('<tr><td colspan="5" class="wp-pos-empty-cart">' + 
                (POS.texts.empty_cart || 'No hay productos en la venta') + '</td></tr>');
        } else {
            // Agrupar los elementos por tipo (productos y servicios)
            let hasProducts = false;
            let hasServices = false;
            
            // Generar HTML para cada sección
            let productsHtml = '';
            let servicesHtml = '';
            
            cart.items.forEach((item, index) => {
                const isService = item.type === 'service';
                const itemHtml = `<tr class="${isService ? 'wp-pos-service-item' : 'wp-pos-product-item'}">
                    <td>
                        <div class="wp-pos-item-info">
                            <span class="wp-pos-item-icon">
                                <span class="dashicons ${isService ? 'dashicons-admin-tools' : 'dashicons-products'}"></span>
                            </span>
                            <span class="wp-pos-item-name">${item.name}</span>
                        </div>
                    </td>
                    <td>
                        <input type="number" class="wp-pos-item-quantity" 
                        data-index="${index}" value="${item.quantity}" min="1">
                    </td>
                    <td>${PosUtils.formatPrice(item.price)}</td>
                    <td>${PosUtils.formatPrice(item.price * item.quantity)}</td>
                    <td>
                        <button type="button" class="wp-pos-remove-item" data-index="${index}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>`;
                
                if (isService) {
                    servicesHtml += itemHtml;
                    hasServices = true;
                } else {
                    productsHtml += itemHtml;
                    hasProducts = true;
                }
            });
            
            // Combinar HTML con encabezados de sección si es necesario
            if (hasProducts) {
                html += `<tr class="wp-pos-section-header products-header">
                    <td colspan="5"><span class="dashicons dashicons-products"></span> ${POS.texts.products_section || 'Productos'}</td>
                </tr>`;
                html += productsHtml;
            }
            
            if (hasServices) {
                html += `<tr class="wp-pos-section-header services-header">
                    <td colspan="5"><span class="dashicons dashicons-admin-tools"></span> ${POS.texts.services_section || 'Servicios'}</td>
                </tr>`;
                html += servicesHtml;
            }
            
            $cartItems.html(html);
        }
        
        // Calcular totales
        cart.subtotal = cart.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        cart.total = cart.subtotal;
        
        // Calcular cambio basado en el importe recibido
        calcularCambio();
        
        // Actualizar visualización
        $('#wp-pos-subtotal').text(PosUtils.formatPrice(cart.subtotal));
        $('#wp-pos-total').text(PosUtils.formatPrice(cart.total));
        // Usar .val() para el campo input de cambio
        $('#wp-pos-change').val(PosUtils.formatPrice(cart.change));
    }
    
    /**
     * Calcular el cambio/vuelto basado en el importe recibido
     */
    function calcularCambio() {
        const importeRecibido = parseFloat(cart.amountReceived) || 0;
        cart.change = importeRecibido - cart.total;
        
        // Si el cambio es negativo (falta dinero), mostrarlo como 0
        if (cart.change < 0) {
            cart.change = 0;
        }
    }
    
    /**
     * Actualizar el importe recibido
     */
    function updateAmountReceived(amount) {
        cart.amountReceived = parseFloat(amount) || 0;
        calcularCambio();
        
        // Actualizar solo los campos relevantes sin redibujar todo el carrito
        // Usamos .val() para campos input, no .text()
        $('#wp-pos-change').val(PosUtils.formatPrice(cart.change));
    }
    
    /**
     * Obtener los datos del carrito
     */
    function getCartData() {
        return {
            items: cart.items,
            subtotal: cart.subtotal,
            total: cart.total,
            amountReceived: cart.amountReceived,
            change: cart.change
        };
    }
    
    /**
     * Inicializar el módulo
     */
    function init() {
        // Eventos para eliminación de productos del carrito
        $(document).on('click', '.wp-pos-remove-item', function() {
            const index = $(this).data('index');
            removeItem(index);
        });
        
        // Eventos para cambio de cantidad
        $(document).on('change', '.wp-pos-item-quantity', function() {
            const index = $(this).data('index');
            const quantity = $(this).val();
            updateQuantity(index, quantity);
        });
        
        // Evento para cambio en el importe recibido
        $(document).on('input', '#wp-pos-amount-received', function() {
            const amount = $(this).val();
            updateAmountReceived(amount);
        });
    }
    
    // Módulo público
    return {
        init: init,
        addItem: addItem,
        removeItem: removeItem,
        updateQuantity: updateQuantity,
        updateAmountReceived: updateAmountReceived,
        emptyCart: emptyCart,
        getCartData: getCartData
    };
    
})(jQuery);