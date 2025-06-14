/**
 * JavaScript para el mu00f3dulo de Cierres
 */

(function($) {
    'use strict';
    
    // Objeto principal del mu00f3dulo
    var WP_POS_Closures = {
        /**
         * Inicializar el mu00f3dulo
         */
        init: function() {
            this.setupTabs();
            this.setupRegisters();
            this.setupHistory();
            this.setupReports();
        },
        
        /**
         * Configurar navegaciu00f3n por pestu00f1as
         */
        setupTabs: function() {
            $('.wp-pos-closures-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var tab = $(this).data('tab');
                
                // Activar pestu00f1a seleccionada
                $('.wp-pos-closures-tabs .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Mostrar contenido de la pestu00f1a seleccionada
                $('.wp-pos-tab-content').removeClass('wp-pos-tab-active');
                $('#' + tab).addClass('wp-pos-tab-active');
                
                // Actualizar URL con el fragmento de la pestu00f1a
                if (history.pushState) {
                    history.pushState(null, null, '#' + tab);
                } else {
                    location.hash = '#' + tab;
                }
            });
            
            // Activar pestu00f1a desde la URL si existe
            if (location.hash) {
                var tab = location.hash.substring(1);
                $('.wp-pos-closures-tabs .nav-tab[data-tab="' + tab + '"]').trigger('click');
            }
        },
        
        /**
         * Configurar funcionalidad de cajas registradoras
         */
        setupRegisters: function() {
            var self = this;
            
            // Cargar cajas al iniciar
            this.loadRegisters();
            
            // Agregar caja registradora
            $('#add-register').on('click', function() {
                self.showRegisterForm();
            });
            
            // Delegaciu00f3n de eventos para botones de acciu00f3n
            $('#registers-list-body').on('click', '.register-edit', function() {
                var registerId = $(this).data('id');
                var name = $(this).data('name');
                var location = $(this).data('location');
                self.showRegisterForm(registerId, name, location);
            });
            
            $('#registers-list-body').on('click', '.register-delete', function() {
                var registerId = $(this).data('id');
                if (confirm(wp_pos_closures.messages.confirm_delete)) {
                    self.deleteRegister(registerId);
                }
            });
        },
        
        /**
         * Cargar cajas registradoras
         */
        loadRegisters: function() {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'GET',
                data: {
                    action: 'wp_pos_closures_get_registers',
                    nonce: wp_pos_closures.nonce
                },
                success: function(response) {
                    if (response.success && response.data.registers && response.data.registers.length > 0) {
                        var html = '';
                        
                        $.each(response.data.registers, function(index, register) {
                            var statusClass = register.status === 'open' ? 'register-open' : 'register-closed';
                            var statusText = register.status === 'open' ? 'Abierta' : 'Cerrada';
                            
                            html += '<tr>' +
                                '<td>' + register.name + '</td>' +
                                '<td>' + (register.location || '-') + '</td>' +
                                '<td><span class="' + statusClass + '">' + statusText + '</span></td>' +
                                '<td class="actions">' +
                                    '<button class="button register-edit" data-id="' + register.id + '" data-name="' + register.name + '" data-location="' + (register.location || '') + '">Editar</button> ' +
                                    '<button class="button register-delete" data-id="' + register.id + '">Eliminar</button>' +
                                '</td>' +
                            '</tr>';
                        });
                        
                        $('#registers-list-body').html(html);
                        
                        // Actualizar selectores de cajas en otras pestu00f1as
                        self.populateRegisterSelects(response.data.registers);
                    } else {
                        $('#registers-list-body').html('<tr class="no-items"><td colspan="4">No hay cajas registradoras configuradas.</td></tr>');
                    }
                },
                error: function() {
                    alert(wp_pos_closures.messages.error);
                }
            });
        },
        
        /**
         * Mostrar formulario de caja registradora
         */
        showRegisterForm: function(id, name, location) {
            var title = id ? 'Editar Caja Registradora' : 'Agregar Caja Registradora';
            var self = this;
            
            // Crear HTML del formulario
            var html = '<div class="wp-pos-form">' +
                '<div class="wp-pos-form-field">' +
                    '<label for="register-name">Nombre:</label>' +
                    '<input type="text" id="register-name" value="' + (name || '') + '" required>' +
                '</div>' +
                '<div class="wp-pos-form-field">' +
                    '<label for="register-location">Ubicaciu00f3n:</label>' +
                    '<input type="text" id="register-location" value="' + (location || '') + '">' +
                '</div>' +
            '</div>';
            
            // Mostrar diu00e1logo
            $('<div id="register-dialog"></div>').html(html).dialog({
                title: title,
                modal: true,
                width: 400,
                buttons: {
                    'Guardar': function() {
                        var $this = $(this);
                        var registerName = $('#register-name').val();
                        var registerLocation = $('#register-location').val();
                        
                        if (!registerName) {
                            alert('El nombre de la caja es obligatorio.');
                            return;
                        }
                        
                        if (id) {
                            self.updateRegister(id, registerName, registerLocation, $this);
                        } else {
                            self.addRegister(registerName, registerLocation, $this);
                        }
                    },
                    'Cancelar': function() {
                        $(this).dialog('close');
                    }
                },
                close: function() {
                    $(this).dialog('destroy').remove();
                }
            });
        },
        
        /**
         * Agregar caja registradora
         */
        addRegister: function(name, location, dialog) {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_pos_closures_add_register',
                    nonce: wp_pos_closures.nonce,
                    name: name,
                    location: location
                },
                success: function(response) {
                    if (response.success) {
                        dialog.dialog('close');
                        self.loadRegisters();
                    } else {
                        alert(response.data.message || wp_pos_closures.messages.error);
                    }
                },
                error: function() {
                    alert(wp_pos_closures.messages.error);
                }
            });
        },
        
        /**
         * Actualizar caja registradora
         */
        updateRegister: function(id, name, location, dialog) {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_pos_closures_update_register',
                    nonce: wp_pos_closures.nonce,
                    register_id: id,
                    name: name,
                    location: location
                },
                success: function(response) {
                    if (response.success) {
                        dialog.dialog('close');
                        self.loadRegisters();
                    } else {
                        alert(response.data.message || wp_pos_closures.messages.error);
                    }
                },
                error: function() {
                    alert(wp_pos_closures.messages.error);
                }
            });
        },
        
        /**
         * Eliminar caja registradora
         */
        deleteRegister: function(id) {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_pos_closures_delete_register',
                    nonce: wp_pos_closures.nonce,
                    register_id: id
                },
                success: function(response) {
                    if (response.success) {
                        self.loadRegisters();
                    } else {
                        alert(response.data.message || wp_pos_closures.messages.error);
                    }
                },
                error: function() {
                    alert(wp_pos_closures.messages.error);
                }
            });
        },
        
        /**
         * Poblar selectores de cajas registradoras
         */
        populateRegisterSelects: function(registers) {
            var options = '<option value="">Todas</option>';
            
            $.each(registers, function(index, register) {
                options += '<option value="' + register.id + '">' + register.name + '</option>';
            });
            
            $('#history-filter-register, #report-register').html(options);
        },
        
        /**
         * Configurar funcionalidad de historial
         */
        setupHistory: function() {
            var self = this;
            
            // Filtrar historial
            $('#history-filter-btn').on('click', function() {
                self.loadHistory();
            });
            
            // Reiniciar filtros
            $('#history-reset-btn').on('click', function() {
                $('#history-filter-register').val('');
                $('#history-filter-from, #history-filter-to').val('');
                self.loadHistory();
            });
        },
        
        /**
         * Cargar historial de cierres
         */
        loadHistory: function() {
            var register_id = $('#history-filter-register').val();
            var date_from = $('#history-filter-from').val();
            var date_to = $('#history-filter-to').val();
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'GET',
                data: {
                    action: 'wp_pos_closures_get_closures',
                    nonce: wp_pos_closures.nonce,
                    register_id: register_id,
                    date_from: date_from,
                    date_to: date_to
                },
                success: function(response) {
                    if (response.success && response.data.closures && response.data.closures.length > 0) {
                        var html = '';
                        
                        $.each(response.data.closures, function(index, closure) {
                            var statusClass = '';
                            var statusText = '';
                            
                            switch (closure.status) {
                                case 'pending':
                                    statusClass = 'status-pending';
                                    statusText = 'Pendiente';
                                    break;
                                case 'approved':
                                    statusClass = 'status-approved';
                                    statusText = 'Aprobado';
                                    break;
                                case 'rejected':
                                    statusClass = 'status-rejected';
                                    statusText = 'Rechazado';
                                    break;
                                default:
                                    statusText = closure.status;
                            }
                            
                            html += '<tr>' +
                                '<td>' + closure.id + '</td>' +
                                '<td>' + closure.register_name + '</td>' +
                                '<td>' + (closure.user_name || '-') + '</td>' +
                                '<td>' + closure.created_at + '</td>' +
                                '<td>' + parseFloat(closure.initial_amount).toFixed(2) + '</td>' +
                                '<td>' + parseFloat(closure.actual_amount).toFixed(2) + '</td>' +
                                '<td>' + parseFloat(closure.difference).toFixed(2) + '</td>' +
                                '<td><span class="' + statusClass + '">' + statusText + '</span></td>' +
                                '<td class="actions">' +
                                    '<button class="button closure-view" data-id="' + closure.id + '">Ver</button> ' +
                                '</td>' +
                            '</tr>';
                        });
                        
                        $('#history-list-body').html(html);
                    } else {
                        $('#history-list-body').html('<tr class="no-items"><td colspan="9">No hay registros de cierres para mostrar.</td></tr>');
                    }
                },
                error: function() {
                    alert(wp_pos_closures.messages.error);
                }
            });
        },
        
        /**
         * Configurar funcionalidad de reportes
         */
        setupReports: function() {
            var self = this;
            
            // Cambiar tipo de reporte
            $('.wp-pos-report-types li').on('click', function() {
                $('.wp-pos-report-types li').removeClass('active');
                $(this).addClass('active');
            });
            
            // Generar reporte
            $('#generate-report').on('click', function() {
                var report_type = $('.wp-pos-report-types li.active').data('report');
                var register_id = $('#report-register').val();
                var date_from = $('#report-from').val();
                var date_to = $('#report-to').val();
                var format = $('#report-format').val();
                
                if (!date_from || !date_to) {
                    alert('Por favor selecciona un rango de fechas.');
                    return;
                }
                
                // Si el formato es PDF o CSV, redirigir a la URL de descarga
                if (format === 'pdf' || format === 'csv') {
                    var url = wp_pos_closures.ajax_url + '?' +
                        'action=wp_pos_closures_generate_report' +
                        '&nonce=' + wp_pos_closures.nonce +
                        '&report_type=' + report_type +
                        '&register_id=' + register_id +
                        '&date_from=' + date_from +
                        '&date_to=' + date_to +
                        '&format=' + format;
                    
                    window.open(url, '_blank');
                } else {
                    // Para HTML, mostrar en la pu00e1gina
                    $('#report-results').html('<div class="wp-pos-loading">Generando reporte...</div>');
                    
                    $.ajax({
                        url: wp_pos_closures.ajax_url,
                        type: 'GET',
                        data: {
                            action: 'wp_pos_closures_generate_report',
                            nonce: wp_pos_closures.nonce,
                            report_type: report_type,
                            register_id: register_id,
                            date_from: date_from,
                            date_to: date_to,
                            format: format
                        },
                        success: function(response) {
                            if (response.success && response.data.report_data) {
                                $('#report-results').html(response.data.report_data.html);
                            } else {
                                $('#report-results').html('<div class="wp-pos-report-empty">No hay datos para mostrar con los filtros seleccionados.</div>');
                            }
                        },
                        error: function() {
                            $('#report-results').html('<div class="wp-pos-report-error">Error al generar el reporte. Intenta nuevamente.</div>');
                        }
                    });
                }
            });
        }
    };
    
    // Inicializar cuando el DOM estu00e9 listo
    $(document).ready(function() {
        WP_POS_Closures.init();
    });
    
})(jQuery);
