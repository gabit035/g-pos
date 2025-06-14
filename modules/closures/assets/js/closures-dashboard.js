/**
 * Script para la funcionalidad del Dashboard de Cierres
 * 
 * @package WP-POS
 * @subpackage Closures
 */

jQuery(document).ready(function($) {
    // Inicializar variables
    var dashboardData = {
        current_month: {
            total: 0,
            difference: 0
        },
        prev_month: {
            total: 0
        },
        pending_count: 0,
        recent_closures: [],
        daily_amounts: [],
        status_distribution: {}
    };
    
    // Referencias a gráficos
    var dailyChart = null;
    var statusChart = null;
    
    // Cargar datos del dashboard
    function loadDashboardData() {
        var period = $('#chart-period').val() || 'month';
        
        // Mostrar indicador de carga
        var dashboardLoader = WP_POS_LoadingIndicator.showInTargets('Cargando datos...');
        
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_pos_closures_dashboard_data',
                nonce: ajax_object.nonce,
                period: period
            },
            success: function(response) {
                if (response.success && response.data) {
                    dashboardData = response.data;
                    updateDashboardUI();
                } else {
                    console.error('Error en la respuesta:', response);
                    WP_POS_Notifications.error('Error al cargar los datos del dashboard');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
                WP_POS_Notifications.error('Error de conexión al cargar datos del dashboard');
            },
            complete: function() {
                // Ocultar indicador de carga
                dashboardLoader.hideAll();
            }
        });
    }
    
    // Actualizar la interfaz con los datos obtenidos
    function updateDashboardUI() {
        // 1. Actualizar tarjetas de resumen
        $('#current-month-total').text(formatCurrency(dashboardData.current_month.total));
        $('#prev-month-total').text(formatCurrency(dashboardData.prev_month.total));
        $('#pending-closures').text(dashboardData.pending_count);
        
        var difference = dashboardData.current_month.difference;
        $('#total-difference')
            .text(formatCurrency(Math.abs(difference)))
            .removeClass('wp-pos-positive wp-pos-negative')
            .addClass(difference < 0 ? 'wp-pos-negative' : (difference > 0 ? 'wp-pos-positive' : ''));
        
        // 2. Actualizar el encabezado
        updateDashboardHeader();
        
        // 3. Actualizar tabla de cierres recientes
        updateRecentClosures();
        
        // 4. Actualizar gráficos
        updateCharts();
    }
    
    // Actualizar el encabezado del dashboard
    function updateDashboardHeader() {
        // Actualizar saldo de caja actual
        if (dashboardData.current_register && dashboardData.current_register.balance !== undefined) {
            $('#current-register-balance').text(formatCurrency(dashboardData.current_register.balance));
            
            // Actualizar cambio en el saldo
            const changeAmount = dashboardData.current_register.today_change || 0;
            const changeElement = $('#register-change');
            const changeIcon = changeElement.find('.dashicons');
            
            changeElement.text(formatCurrency(Math.abs(changeAmount)));
            changeElement.prepend(changeIcon);
            
            // Actualizar estilos según si es positivo o negativo
            changeElement.removeClass('wp-pos-positive wp-pos-negative');
            if (changeAmount > 0) {
                changeElement.addClass('wp-pos-positive');
                changeIcon.removeClass('dashicons-arrow-down-alt').addClass('dashicons-arrow-up-alt');
            } else if (changeAmount < 0) {
                changeElement.addClass('wp-pos-negative');
                changeIcon.removeClass('dashicons-arrow-up-alt').addClass('dashicons-arrow-down-alt');
            }
        }
        
        // Actualizar ventas del día
        if (dashboardData.today_sales !== undefined) {
            $('#today-sales').text(formatCurrency(dashboardData.today_sales.amount));
            
            // Actualizar cambio porcentual
            const changePercent = dashboardData.today_sales.change_percent || 0;
            const changePercentElement = $('#sales-change');
            const changePercentIcon = changePercentElement.find('.dashicons');
            
            changePercentElement.text(changePercent + '%');
            changePercentElement.prepend(changePercentIcon);
            
            // Actualizar estilos según si es positivo o negativo
            changePercentElement.removeClass('wp-pos-positive wp-pos-negative');
            if (changePercent > 0) {
                changePercentElement.addClass('wp-pos-positive');
                changePercentIcon.removeClass('dashicons-arrow-down-alt').addClass('dashicons-arrow-up-alt');
            } else if (changePercent < 0) {
                changePercentElement.addClass('wp-pos-negative');
                changePercentIcon.removeClass('dashicons-arrow-up-alt').addClass('dashicons-arrow-down-alt');
            }
        }
        
        // Actualizar contador de cierres pendientes
        if (dashboardData.pending_count !== undefined) {
            $('#pending-closures-count').text(dashboardData.pending_count);
            
            // Mostrar/ocultar enlace de ver pendientes
            const viewPendingLink = $('#view-pending-closures');
            if (dashboardData.pending_count > 0) {
                viewPendingLink.show();
            } else {
                viewPendingLink.hide();
            }
        }
    }
    
    // Actualizar gráficos
    function updateCharts() {
        // Solo actualizar los gráficos si existen los elementos correspondientes
        if (document.getElementById('daily-amounts-chart')) {
            updateDailyChart();
        }
        
        if (document.getElementById('status-distribution-chart')) {
            updateStatusChart();
        }
    }
    
    // Actualizar gráfico de montos diarios
    function updateDailyChart() {
        var chartCanvas = document.getElementById('daily-amounts-chart');
        if (!chartCanvas) return;
        
        var ctx = chartCanvas.getContext('2d');
        var labels = [];
        var amounts = [];
        var differences = [];
        
        if (dashboardData.daily_amounts && dashboardData.daily_amounts.length) {
            dashboardData.daily_amounts.forEach(function(item) {
                labels.push(formatShortDate(item.date));
                amounts.push(item.amount);
                differences.push(item.difference || 0);
            });
        }
        
        // Destruir el gráfico existente si hay uno
        if (typeof dailyChart !== 'undefined' && dailyChart) {
            dailyChart.destroy();
        }
        
        // Crear el nuevo gráfico
        dailyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monto Total',
                    data: amounts,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Diferencia',
                    data: differences,
                    type: 'line',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                var value = context.raw || 0;
                                return label + ': ' + formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Actualizar gráfico de distribución de estados
    function updateStatusChart() {
        var chartCanvas = document.getElementById('status-distribution-chart');
        if (!chartCanvas) return;
        
        var ctx = chartCanvas.getContext('2d');
        var statusData = dashboardData.status_distribution || { pending: 0, approved: 0, rejected: 0 };
        var data = [statusData.pending || 0, statusData.approved || 0, statusData.rejected || 0];
        
        // Destruir el gráfico existente si hay uno
        if (typeof statusChart !== 'undefined' && statusChart) {
            statusChart.destroy();
        }
        
        // Crear el nuevo gráfico
        statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [getStatusText('pending'), getStatusText('approved'), getStatusText('rejected')],
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(255, 159, 64, 0.7)',  // Naranja para pendiente
                        'rgba(75, 192, 192, 0.7)',  // Verde para aprobado
                        'rgba(255, 99, 132, 0.7)'   // Rojo para rechazado
                    ],
                    borderColor: [
                        'rgba(255, 159, 64, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Actualizar tabla de cierres recientes
    function updateRecentClosures() {
        var closures = dashboardData.recent_closures;
        var html = '';
        
        if (closures && closures.length > 0) {
            closures.forEach(function(closure) {
                var statusClass = 'status-' + closure.status;
                var statusText = getStatusText(closure.status);
                var difference = parseFloat(closure.actual_amount) - parseFloat(closure.expected_amount);
                var differenceClass = difference < 0 ? 'wp-pos-negative' : (difference > 0 ? 'wp-pos-positive' : '');
                
                html += `
                    <tr>
                        <td>${closure.id}</td>
                        <td>${formatDate(closure.created_at)}</td>
                        <td>${closure.user_name || '-'}</td>
                        <td>${formatCurrency(closure.actual_amount)}</td>
                        <td class="${differenceClass}">${formatCurrency(Math.abs(difference))}</td>
                        <td><span class="status-label ${statusClass}">${statusText}</span></td>
                        <td>
                            <a href="#" class="view-closure-details" data-id="${closure.id}">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                        </td>
                    </tr>
                `;
            });
        } else {
            html = `<tr class="no-items"><td colspan="7">No hay cierres recientes</td></tr>`;
        }
        
        $('#recent-closures-list').html(html);
        
        // Vincular eventos para ver detalles
        $('.view-closure-details').on('click', function(e) {
            e.preventDefault();
            var closureId = $(this).data('id');
            viewClosureDetails(closureId);
        });
    }
    
    // Ver detalles de un cierre
    function viewClosureDetails(closureId) {
        // Redirigir a la vista de historial con el ID seleccionado
        window.location.href = closures_dashboard.history_url + closureId;
    }
    
    // Funciones auxiliares
    function formatCurrency(amount) {
        return '$' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        var date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function formatShortDate(dateString) {
        if (!dateString) return '-';
        var date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit'
        });
    }
    
    function getStatusText(status) {
        switch (status) {
            case 'pending': return closures_dashboard.text.pending;
            case 'approved': return closures_dashboard.text.approved;
            case 'rejected': return closures_dashboard.text.rejected;
            default: return status;
        }
    }
    
    // Evento para el botón de actualizar
    $('#refresh-chart, #refresh-page').on('click', function() {
        if ($(this).hasClass('button-disabled')) return;
        
        // Mostrar spinner de carga
        $(this).addClass('button-disabled').find('.dashicons').addClass('dashicons-update-alt dashicons-update-spin');
        
        // Si es el botón de actualizar la página, recargamos toda la página
        if ($(this).is('#refresh-page')) {
            window.location.reload();
            return;
        }
        
        // Si es el botón de actualizar datos, cargamos solo los datos
        loadDashboardData().always(function() {
            // Restaurar el botón después de cargar los datos
            setTimeout(function() {
                $('#refresh-chart, #refresh-page')
                    .removeClass('button-disabled')
                    .find('.dashicons')
                    .removeClass('dashicons-update-alt dashicons-update-spin')
                    .addClass('dashicons-update');
            }, 500);
        });
    });
    
    // Evento para el botón de iniciar cierre
    $('#start-closure-process, .wp-pos-page-actions .button-primary').on('click', function(e) {
        e.preventDefault();
        window.location.href = ajax_object.ajax_url + '?page=wp-pos-module-closures&view=form';
    });
    
    // Evento para ver cierres pendientes
    $('#view-pending-closures').on('click', function(e) {
        e.preventDefault();
        window.location.href = ajax_object.ajax_url + '?page=wp-pos-module-closures&view=history&status=pending';
    });
    
    // Manejar el dropdown de exportación
    $('.wp-pos-dropdown').on('click', function(e) {
        e.stopPropagation();
        var $dropdown = $(this);
        var $content = $dropdown.find('.wp-pos-dropdown-content');
        
        // Cerrar otros dropdowns abiertos
        $('.wp-pos-dropdown-content').not($content).hide();
        
        // Alternar el dropdown actual
        $content.toggle();
    });
    
    // Cerrar dropdown al hacer clic fuera
    $(document).on('click', function() {
        $('.wp-pos-dropdown-content').hide();
    });
    
    // Prevenir que el clic en el dropdown lo cierre
    $('.wp-pos-dropdown-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Evento para cambio de período
    $('#chart-period').on('change', function() {
        loadDashboardData();
    });
    
    // Cargar datos iniciales
    loadDashboardData();
});
