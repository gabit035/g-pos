<?php
/**
 * Plantilla para mostrar gráficos en la página de reportes
 *
 * @package WP-POS
 * @subpackage Reports
 */

// Salir si se accede directamente
defined('ABSPATH') || exit;

// Variables esperadas: $chart_data que contiene los datos para los gráficos
// Si no se pasan datos, usar datos demo
if (!isset($chart_data) || !is_array($chart_data)) {
    $chart_data = [
        'sales_trend' => [
            'labels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            'data' => [1200, 1900, 1500, 2500, 2200, 3000, 2800]
        ],
        'payment_methods' => [
            'labels' => ['Efectivo', 'Tarjeta', 'Transferencia'],
            'data' => [45, 35, 20]
        ],
        'sellers_performance' => [
            'labels' => ['Vendedor 1', 'Vendedor 2', 'Vendedor 3'],
            'data' => [8, 5, 3]
        ],
        'top_products' => [
            'labels' => ['Producto A', 'Producto B', 'Producto C', 'Producto D', 'Producto E'],
            'data' => [54, 45, 30, 25, 15]
        ]
    ];
}

// Obtener datos específicos o usar fallbacks
$sales_trend_data = isset($chart_data['sales_trend']) ? $chart_data['sales_trend'] : $chart_data;
$payment_methods_data = isset($payment_methods_data) ? $payment_methods_data : (isset($chart_data['payment_methods']) ? $chart_data['payment_methods'] : ['labels' => ['Efectivo', 'Tarjeta'], 'data' => [60, 40]]);
$top_products_data = isset($top_products_data) ? $top_products_data : (isset($chart_data['top_products']) ? $chart_data['top_products'] : ['labels' => ['Producto A', 'Producto B'], 'data' => [30, 20]]);
$sellers_data = isset($chart_data['sellers_performance']) ? $chart_data['sellers_performance'] : ['labels' => ['Vendedor 1', 'Vendedor 2'], 'data' => [10, 8]];
?>

<div class="wp-pos-charts-section">
    <div class="wp-pos-section-title">
        <h2><i class="dashicons dashicons-chart-area"></i> <?php _e('Análisis Gráfico', 'wp-pos'); ?></h2>
        <p><?php _e('Visualización de datos de ventas y tendencias', 'wp-pos'); ?></p>
    </div>
    
    <div class="wp-pos-charts-row">
        <!-- Gráfico de tendencia de ventas -->
        <div class="wp-pos-chart-container">
            <div class="wp-pos-chart-header">
                <h2 class="wp-pos-chart-title">
                    <i class="dashicons dashicons-chart-line"></i>
                    <?php _e('Tendencia de Ventas', 'wp-pos'); ?>
                </h2>
            </div>
            <div class="wp-pos-chart-body">
                <canvas id="salesTrendChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Gráfico de distribución por vendedor -->
        <div class="wp-pos-chart-container">
            <div class="wp-pos-chart-header">
                <h2 class="wp-pos-chart-title">
                    <i class="dashicons dashicons-groups"></i>
                    <?php _e('Ventas por Vendedor', 'wp-pos'); ?>
                </h2>
            </div>
            <div class="wp-pos-chart-body">
                <canvas id="sellerDistributionChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="wp-pos-charts-row">
        <!-- Gráfico de métodos de pago -->
        <div class="wp-pos-chart-container">
            <div class="wp-pos-chart-header">
                <h2 class="wp-pos-chart-title">
                    <i class="dashicons dashicons-money-alt"></i>
                    <?php _e('Métodos de Pago', 'wp-pos'); ?>
                </h2>
            </div>
            <div class="wp-pos-chart-body">
                <canvas id="paymentMethodsChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Gráfico de rendimiento de productos -->
        <div class="wp-pos-chart-container">
            <div class="wp-pos-chart-header">
                <h2 class="wp-pos-chart-title">
                    <i class="dashicons dashicons-cart"></i>
                    <?php _e('Productos más Vendidos', 'wp-pos'); ?>
                </h2>
            </div>
            <div class="wp-pos-chart-body">
                <canvas id="topProductsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Script para inicializar los gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando gráficos...');
    
    // Verificar que Chart.js esté disponible
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        return;
    }
    
    // Datos de gráficos desde PHP
    var chartData = <?php echo json_encode($chart_data); ?>;
    console.log('Datos de gráficos:', chartData);
    
    // Colores para los gráficos con mejor contraste
    const chartColors = [
        'rgba(54, 162, 235, 0.7)',   // Azul
        'rgba(255, 99, 132, 0.7)',   // Rojo 
        'rgba(75, 192, 192, 0.7)',   // Verde
        'rgba(255, 206, 86, 0.7)',   // Amarillo
        'rgba(153, 102, 255, 0.7)',  // Púrpura
        'rgba(255, 159, 64, 0.7)',   // Naranja
        'rgba(199, 199, 199, 0.7)',  // Gris
        'rgba(83, 102, 255, 0.7)',   // Indigo
        'rgba(78, 205, 196, 0.7)',   // Turquesa
        'rgba(255, 99, 255, 0.7)'    // Rosa
    ];
    
    // Configuración global de Chart.js
    Chart.defaults.font.size = 13;
    Chart.defaults.color = '#333';
    Chart.defaults.plugins.legend.labels.boxWidth = 20;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.boxPadding = 6;

    // Función para generar colores para un array
    const generateColors = (count) => {
        const colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(chartColors[i % chartColors.length]);
        }
        return colors;
    };
    
    // Variable para almacenar las instancias de los gráficos
    var chartInstances = {};
    
    // Función para inicializar/actualizar gráficos
    function initializeCharts(data) {
        console.log('Inicializando gráficos con datos:', data);
        
        // Destruir gráficos existentes
        Object.keys(chartInstances).forEach(function(key) {
            if (chartInstances[key]) {
                chartInstances[key].destroy();
            }
        });
        chartInstances = {};
        
        // 1. GRÁFICO DE TENDENCIA DE VENTAS
        var salesTrendCtx = document.getElementById('salesTrendChart');
        if (salesTrendCtx) {
            salesTrendCtx = salesTrendCtx.getContext('2d');
            
            var trendData = data.sales_trend || { labels: [], data: [] };
            
            chartInstances.salesTrend = new Chart(salesTrendCtx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: '<?php _e("Ventas ($)", "wp-pos"); ?>',
                        data: trendData.data,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                },
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return '<?php _e("Ventas", "wp-pos"); ?>: $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. GRÁFICO DE DISTRIBUCIÓN POR VENDEDOR
        var sellerDistCtx = document.getElementById('sellerDistributionChart');
        if (sellerDistCtx) {
            sellerDistCtx = sellerDistCtx.getContext('2d');
            
            var sellersData = data.sellers_performance || { labels: [], data: [] };
            var sellerColors = generateColors(sellersData.labels.length);

            chartInstances.sellerDist = new Chart(sellerDistCtx, {
                type: 'bar',
                data: {
                    labels: sellersData.labels,
                    datasets: [{
                        label: '<?php _e("Ventas por vendedor", "wp-pos"); ?>',
                        data: sellersData.data,
                        backgroundColor: sellerColors,
                        borderWidth: 1,
                        borderColor: sellerColors.map(color => color.replace('0.7', '1')),
                        borderRadius: 4,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: sellersData.labels.length > 4 ? 'y' : 'x',
                    scales: {
                        y: {
                            grid: {
                                display: false
                            },
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                precision: 0,
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed.y + ' ventas';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. GRÁFICO DE MÉTODOS DE PAGO
        var paymentMethodsCtx = document.getElementById('paymentMethodsChart');
        if (paymentMethodsCtx) {
            paymentMethodsCtx = paymentMethodsCtx.getContext('2d');
            
            var paymentData = data.payment_methods || { labels: [], data: [] };
            var paymentColors = generateColors(paymentData.labels.length);

            chartInstances.paymentMethods = new Chart(paymentMethodsCtx, {
                type: 'doughnut',
                data: {
                    labels: paymentData.labels,
                    datasets: [{
                        data: paymentData.data,
                        backgroundColor: paymentColors,
                        borderColor: paymentColors.map(color => color.replace('0.7', '1')),
                        borderWidth: 2,
                        borderRadius: 4,
                        spacing: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value * 100) / total);
                                    return label + ': ' + percentage + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 4. GRÁFICO DE PRODUCTOS MÁS VENDIDOS
        var topProductsCtx = document.getElementById('topProductsChart');
        if (topProductsCtx) {
            topProductsCtx = topProductsCtx.getContext('2d');
            
            var productsData = data.top_products || { labels: [], data: [] };
            var productColors = generateColors(productsData.labels.length);

            chartInstances.topProducts = new Chart(topProductsCtx, {
                type: 'bar',
                data: {
                    labels: productsData.labels,
                    datasets: [{
                        label: '<?php _e("Unidades vendidas", "wp-pos"); ?>',
                        data: productsData.data,
                        backgroundColor: productColors,
                        borderColor: productColors.map(color => color.replace('0.7', '1')),
                        borderWidth: 2,
                        borderRadius: 6,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 10,
                            top: 0,
                            bottom: 0
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                precision: 0,
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return '<?php _e("Unidades", "wp-pos"); ?>: ' + context.parsed.x;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        console.log('Gráficos inicializados correctamente');
    }
    
    // Inicializar gráficos con los datos iniciales
    initializeCharts(chartData);
    
    // Escuchar eventos de actualización de gráficos
    jQuery(document).on('wppos:update-charts', function(event, newData) {
        console.log('Actualizando gráficos con nuevos datos:', newData);
        initializeCharts(newData);
    });
    
    // Exponer función globalmente para uso desde otros scripts
    window.updateWPPosCharts = initializeCharts;
});
</script>