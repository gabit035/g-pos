/**
 * JavaScript para la interfaz de nueva venta (V2)
 * Versión modular con navegación por teclado y corrección de búsquedas
 * 
 * Dependencias:
 * - modules/utils.js
 * - modules/cart.js
 * - modules/customer-search.js
 * - modules/product-search.js
 * - modules/service-search.js
 */

(function($) {
    'use strict';

    // Datos globales compartidos entre módulos
    window.POS = window.wp_pos_data || {
        ajaxurl: '',
        nonce: '',
        customer_nonce: '',
        product_nonce: '',
        service_nonce: '',
        texts: {}
    };

    /**
     * Gestión de pestañas
     */
    function initTabs() {
        $('.wp-pos-tab').on('click', function() {
            const tabId = $(this).data('tab');
            
            $('.wp-pos-tab').removeClass('wp-pos-tab-active');
            $(this).addClass('wp-pos-tab-active');
            
            $('.wp-pos-tab-content').removeClass('wp-pos-tab-content-active');
            $('#wp-pos-tab-' + tabId).addClass('wp-pos-tab-content-active');
            
            // Posicionar el cursor en el campo de búsqueda correspondiente
            if (tabId === 'products') {
                $('#wp-pos-product-search').focus();
            } else if (tabId === 'services') {
                $('#wp-pos-service-search').focus();
            }
        });
    }

    /**
     * Inicializar funcionalidad de guardar venta
     */
    function initSaleSubmit() {
        $('#wp-pos-new-sale-form').on('submit', function(e) {
            e.preventDefault();
            
            const $submitBtn = $('#wp-pos-save-sale');
            const $form = $(this);
            
            // Verificar que haya items en el carrito
            const cartData = PosCart.getCartData();
            if (cartData.items.length === 0) {
                PosUtils.showMessage(POS.texts.empty_cart || 'No hay productos en el carrito', 'error');
                return false;
            }
            
            // Deshabilitar botón durante el proceso
            $submitBtn.prop('disabled', true).text(POS.texts.processing || 'Procesando...');
            
            // Preparar datos completos de la venta
            const saleData = {
                items: cartData.items,
                customer_id: $('#wp-pos-customer').val(),
                payment_method: $('#wp-pos-payment-method').val(),
                amount_received: cartData.amountReceived,
                total: cartData.total,
                subtotal: cartData.subtotal
            };
            
            // Asignar datos de venta al campo correcto que espera el backend
            $('#wp_pos_sale_data').val(JSON.stringify(saleData));
            
            // Enviar formulario normal (sin AJAX)
            $form[0].submit();
        });
    }

    /**
     * Inicializar navegación por teclado global y soporte táctil
     */
    function initKeyboardNavigation() {
        // Gestión global de teclas de acceso rápido
        $(document).on('keydown', function(e) {
            // ESC para cerrar cualquier modal visible
            if (e.keyCode === 27) {
                // Cerrar modal de clientes si está abierto
                const $modal = $('#wp-pos-customer-modal');
                if ($modal.hasClass('active')) {
                    $modal.removeClass('active');
                    return false;
                }
            }
            
            // Otras teclas de acceso rápido
            // Alt + P - Tab de productos
            if (e.altKey && e.keyCode === 80) {
                $('.wp-pos-tab[data-tab="products"]').click();
                return false;
            }
            
            // Alt + S - Tab de servicios
            if (e.altKey && e.keyCode === 83) {
                $('.wp-pos-tab[data-tab="services"]').click();
                return false;
            }
            
            // Alt + C - Abrir búsqueda de clientes
            if (e.altKey && e.keyCode === 67) {
                $('#wp-pos-customer-select').click();
                return false;
            }
            
            // Alt + G - Guardar venta
            if (e.altKey && e.keyCode === 71) {
                $('#wp-pos-save-sale').click();
                return false;
            }
            
            // Alt + M - Enfocar método de pago
            if (e.altKey && e.keyCode === 77) {
                $('#wp-pos-payment-method').focus();
                return false;
            }
            
            // Alt + R - Enfocar importe recibido
            if (e.altKey && e.keyCode === 82) {
                $('#wp-pos-amount-received').focus().select();
                return false;
            }
        });
        
        // Detectar si es dispositivo táctil
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
        
        if (isTouchDevice) {
            // Deshabilitar hover personalizado en dispositivos táctiles para evitar problemas
            $('body').addClass('touch-device');
            
            // Mejorar experiencia táctil para campos numéricos
            $('input[type="number"]').on('focus', function() {
                // En dispositivos táctiles, seleccionar todo el texto al enfocar campos numéricos
                setTimeout(() => {
                    this.select();
                }, 10);
            });
            
            // Añadir botones de incremento/decremento más grandes para pantallas táctiles
            // Añadirlos solo si no existen ya
            if ($('.wp-pos-touch-controls').length === 0) {
                $('.wp-pos-quantity-input').each(function() {
                    const $input = $(this);
                    const $container = $('<div class="wp-pos-touch-controls"></div>');
                    const $minus = $('<button type="button" class="wp-pos-touch-btn wp-pos-touch-minus">-</button>');
                    const $plus = $('<button type="button" class="wp-pos-touch-btn wp-pos-touch-plus">+</button>');
                    
                    $minus.on('click', function(e) {
                        e.preventDefault();
                        const currentVal = parseFloat($input.val());
                        if (!isNaN(currentVal) && currentVal > 1) {
                            $input.val(currentVal - 1).trigger('change');
                        }
                    });
                    
                    $plus.on('click', function(e) {
                        e.preventDefault();
                        const currentVal = parseFloat($input.val());
                        if (!isNaN(currentVal)) {
                            $input.val(currentVal + 1).trigger('change');
                        }
                    });
                    
                    $container.append($minus).append($plus);
                    $input.after($container);
                });
            }
        }
    }

    /**
     * Inicialización cuando el DOM está listo
     */
    $(document).ready(function() {
        console.log("Inicializando POS V2:", POS);
        
        // Inicializar componentes generales
        initTabs();
        initSaleSubmit();
        initKeyboardNavigation();
        
        // Inicializar módulos
        PosCart.init();
        PosCustomerSearch.init();
        PosProductSearch.init();
        PosServiceSearch.init();
        
        // Mostrar ayuda inicial sobre accesos rápidos por teclado
        PosUtils.showMessage('Accesos rápidos: Alt+P (Productos), Alt+S (Servicios), Alt+C (Clientes), Alt+M (Método de pago), Alt+R (Importe recibido), Alt+G (Guardar) y ESC', 'info', 7000);
    });

})(jQuery);