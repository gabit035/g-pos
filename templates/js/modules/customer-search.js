/**
 * Módulo de búsqueda de clientes
 * Dependencias: utils.js
 */

const PosCustomerSearch = (function($) {
    'use strict';

    let selectedIndex = -1;

    // Inicializar módulo
    function init() {
        const $modal = $('#wp-pos-customer-modal');
        const $modalClose = $modal.find('.wp-pos-modal-close');
        const $searchInput = $('#wp-pos-customer-search');
        const $searchResults = $('#wp-pos-customer-results');
        const $customerButton = $('#wp-pos-customer-select');

        // Abrir modal
        $customerButton.on('click', function() {
            $modal.addClass('active');
            $searchInput.val('').focus();
        });

        // Cerrar modal con botón
        $modalClose.on('click', function() {
            $modal.removeClass('active');
        });
        
        // Cerrar modal haciendo clic en la parte exterior
        $modal.on('click', function(e) {
            if ($(e.target).is($modal)) {
                $modal.removeClass('active');
            }
        });
        
        // Cerrar modal con ESC desde cualquier lugar dentro del modal
        $modal.on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC
                $modal.removeClass('active');
                e.preventDefault();
                e.stopPropagation();
            }
        });

        // Búsqueda de clientes
        $searchInput.on('keyup', function(e) {
            // No buscar cuando se navega con teclas de flecha
            if (e.keyCode === 38 || e.keyCode === 40 || e.keyCode === 13 || e.keyCode === 27) {
                return;
            }
            
            const term = $(this).val().trim();
            
            if (term.length < 2) {
                $searchResults.hide();
                return;
            }
            
            $searchResults.html(`<div class="wp-pos-search-loading"><span class="dashicons dashicons-update-alt"></span> ${POS.texts.searching || 'Buscando...'}</div>`);
            $searchResults.show();

            // Realizar búsqueda con parámetros correctos
            $.ajax({
                url: POS.ajaxurl,
                method: 'GET', // La función original espera una solicitud GET
                data: {
                    action: 'wp_pos_search_customers',
                    search: term, // Usa search en lugar de query
                    nonce: POS.customer_nonce,
                    page: 1,
                    per_page: 20
                },
                success: function(response) {
                    console.log("Respuesta clientes completa:", response);
                    // Log detallado para ver la estructura exacta
                    if (response.success && response.data) {
                        console.log("Primer cliente (muestra de campos):", response.data[0]);
                    }
                    
                    if (response.success) {
                        // Manejar diferentes formatos de respuesta posibles
                        let customers = [];
                        
                        if (Array.isArray(response.data)) {
                            customers = response.data;
                        } else if (response.data && response.data.customers) {
                            customers = response.data.customers;
                        } else if (response.data && response.data.data) {
                            customers = Array.isArray(response.data.data) ? response.data.data : [response.data.data];
                        }
                        
                        showCustomerResults(customers);
                        selectedIndex = -1; // Resetear el índice seleccionado
                    } else {
                        $searchResults.html(`<div class="wp-pos-search-error">${POS.texts.error || 'Error en la búsqueda'}</div>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error búsqueda clientes:", error, xhr.responseText);
                    $searchResults.html(`<div class="wp-pos-search-error">${POS.texts.error || 'Error en la búsqueda'}</div>`);
                }
            });
        });
        
        // Navegación por teclado en los resultados
        $searchInput.on('keydown', function(e) {
            const items = $('.wp-pos-search-item', $searchResults);
            
            // Si no hay resultados visibles, no hacemos nada
            if (!$searchResults.is(':visible') || items.length === 0) {
                return;
            }

            switch(e.keyCode) {
                case 40: // Flecha abajo
                    e.preventDefault();
                    selectedIndex = (selectedIndex < items.length - 1) ? selectedIndex + 1 : 0;
                    PosUtils.highlightItem(items, selectedIndex, $searchResults);
                    break;
                case 38: // Flecha arriba
                    e.preventDefault();
                    selectedIndex = (selectedIndex > 0) ? selectedIndex - 1 : items.length - 1;
                    PosUtils.highlightItem(items, selectedIndex, $searchResults);
                    break;
                case 13: // Enter
                    e.preventDefault();
                    if (selectedIndex >= 0 && selectedIndex < items.length) {
                        items.eq(selectedIndex).click();
                    }
                    break;
                case 27: // Escape
                    e.preventDefault();
                    $modal.removeClass('active');
                    break;
            }
        });

        // Seleccionar cliente
        $(document).on('click', '.wp-pos-customer-select', function() {
            const customerId = $(this).data('id');
            const customerName = $(this).data('name');
            
            $('#wp-pos-customer').val(customerId);
            $('#wp-pos-customer-display').val(customerName);
            
            $modal.removeClass('active');
        });
    }

    // Mostrar resultados de clientes
    function showCustomerResults(customers) {
        const $searchResults = $('#wp-pos-customer-results');
        
        if (!customers || customers.length === 0) {
            $searchResults.html(`<div class="wp-pos-search-empty">${POS.texts.no_results || 'No se encontraron resultados'}</div>`);
            return;
        }

        let html = '<div class="wp-pos-search-items">';
        
        customers.forEach(function(customer) {
            // Comprobar todos los posibles campos para el nombre del cliente
            const customerName = getCustomerDisplayName(customer);
            
            // Recopilar metadatos
            const meta = [];
            if (customer.email || customer.user_email) meta.push(customer.email || customer.user_email);
            if (customer.phone || customer.billing_phone || customer.meta && customer.meta.billing_phone) {
                meta.push(customer.phone || customer.billing_phone || 
                         (customer.meta && customer.meta.billing_phone ? customer.meta.billing_phone : ''));
            }
            
            html += `<div class="wp-pos-search-item wp-pos-customer-select" 
                data-id="${customer.id || customer.ID}" 
                data-name="${customerName}">
                <div class="wp-pos-search-item-title">${customerName}</div>
                <div class="wp-pos-search-item-meta">${meta.join(' | ')}</div>
            </div>`;
        });
        
        html += '</div>';
        $searchResults.html(html);
    }
    
    /**
     * Obtiene el nombre de visualización del cliente considerando varios formatos posibles
     */
    function getCustomerDisplayName(customer) {
        // Registrar en consola para debugging
        console.log('Objeto cliente completo:', customer);
        
        // Verificar en orden de prioridad diferentes posibles campos
        if (customer.display_name) return customer.display_name;
        if (customer.name) return customer.name;
        if (customer.first_name && customer.last_name) return `${customer.first_name} ${customer.last_name}`;
        if (customer.billing_first_name && customer.billing_last_name) return `${customer.billing_first_name} ${customer.billing_last_name}`;
        
        // Revisar meta fields si existen
        if (customer.meta) {
            if (customer.meta.billing_first_name && customer.meta.billing_last_name) {
                return `${customer.meta.billing_first_name} ${customer.meta.billing_last_name}`;
            }
            if (customer.meta.first_name && customer.meta.last_name) {
                return `${customer.meta.first_name} ${customer.meta.last_name}`;
            }
        }
        
        // Intentar con campos WooCommerce y WordPress
        if (customer.user_nicename) return customer.user_nicename;
        if (customer.user_login) return customer.user_login;
        
        // Si llegamos aquí es porque no se encontró ningún nombre
        return 'Cliente sin nombre';
    }

    return {
        init: init
    };
})(jQuery);