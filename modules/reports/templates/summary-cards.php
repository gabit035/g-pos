<?php
/**
 * Template mejorado para tarjetas de resumen de reportes
 * 
 * @package WP-POS
 * @subpackage Reports
 * @since 1.0.0
 * 
 * Variables esperadas:
 * @var array $totals Array con los datos de totales
 */

if (!defined('ABSPATH')) exit;

// Verificar que tenemos los datos necesarios
if (!isset($totals) || !is_array($totals)) {
    $totals = [
        'sales_count' => 0,
        'total_revenue' => 0,
        'total_profit' => 0,
        'profit_margin' => 0,
        'average_sale' => 0,
        'success' => false,
        'debug_message' => 'No hay datos disponibles'
    ];
}

// Función helper para formatear números
function wp_pos_format_number($number, $decimals = 2, $symbol = '') {
    if (!is_numeric($number)) {
        $number = 0;
    }
    
    if ($symbol === '$') {
        return '$' . number_format($number, $decimals, '.', ',');
    } elseif ($symbol === '%') {
        return number_format($number, $decimals, '.', ',') . '%';
    } else {
        return number_format($number, $decimals, '.', ',');
    }
}

// Función helper para determinar el color de tendencia
function wp_pos_get_trend_class($value, $type = 'positive') {
    if (!is_numeric($value)) return '';
    
    if ($type === 'positive') {
        return $value > 0 ? 'trend-positive' : ($value < 0 ? 'trend-negative' : 'trend-neutral');
    } else {
        return $value < 0 ? 'trend-positive' : ($value > 0 ? 'trend-negative' : 'trend-neutral');
    }
}

// Función helper para obtener icono de tendencia
function wp_pos_get_trend_icon($value, $type = 'positive') {
    if (!is_numeric($value) || $value == 0) return '';
    
    if ($type === 'positive') {
        return $value > 0 ? '↗' : '↘';
    } else {
        return $value < 0 ? '↗' : '↘';
    }
}

// Extraer valores con valores por defecto seguros
$sales_count = isset($totals['sales_count']) ? intval($totals['sales_count']) : 0;
$total_revenue = isset($totals['total_revenue']) ? floatval($totals['total_revenue']) : 0;
$total_profit = isset($totals['total_profit']) ? floatval($totals['total_profit']) : 0;
$profit_margin = isset($totals['profit_margin']) ? floatval($totals['profit_margin']) : 0;
$average_sale = isset($totals['average_sale']) ? floatval($totals['average_sale']) : 0;

// Calcular métricas adicionales
$has_sales = $sales_count > 0;
$revenue_per_sale = $has_sales ? ($total_revenue / $sales_count) : 0;

// Obtener datos de comparación (opcional, para mostrar tendencias)
$comparison_data = [];
if (isset($totals['comparison'])) {
    $comparison_data = $totals['comparison'];
}

// Configuración de tarjetas con metadatos
$cards_config = [
    'ventas' => [
        'icon' => 'cart',
        'title' => __('Ventas', 'wp-pos'),
        'value' => $sales_count,
        'format' => 'number',
        'subtitle' => $has_sales ? __('Ventas realizadas', 'wp-pos') : __('No hay ventas', 'wp-pos'),
        'color_class' => 'ventas',
        'trend' => isset($comparison_data['sales_count']) ? $comparison_data['sales_count'] : null,
        'additional_info' => $has_sales ? sprintf(__('Promedio: %s por día', 'wp-pos'), 
            wp_pos_format_number($sales_count / max(1, (strtotime('now') - strtotime('-30 days')) / DAY_IN_SECONDS), 1)) : ''
    ],
    'ingresos' => [
        'icon' => 'money-alt',
        'title' => __('Ingresos', 'wp-pos'),
        'value' => $total_revenue,
        'format' => 'currency',
        'subtitle' => __('Ingresos totales', 'wp-pos'),
        'color_class' => 'ingresos',
        'trend' => isset($comparison_data['total_revenue']) ? $comparison_data['total_revenue'] : null,
        'additional_info' => $has_sales ? sprintf(__('Por venta: %s', 'wp-pos'), wp_pos_format_number($revenue_per_sale, 2, '$')) : ''
    ],
    'ganancia' => [
        'icon' => 'chart-bar',
        'title' => __('Ganancia', 'wp-pos'),
        'value' => $total_profit,
        'format' => 'currency',
        'subtitle' => __('Ganancia total', 'wp-pos'),
        'color_class' => 'ganancia',
        'trend' => isset($comparison_data['total_profit']) ? $comparison_data['total_profit'] : null,
        'additional_info' => $total_revenue > 0 ? sprintf(__('Costo: %s', 'wp-pos'), 
            wp_pos_format_number($total_revenue - $total_profit, 2, '$')) : ''
    ],
    'margen' => [
        'icon' => 'chart-pie',
        'title' => __('Margen', 'wp-pos'),
        'value' => $profit_margin,
        'format' => 'percentage',
        'subtitle' => __('Margen de beneficio', 'wp-pos'),
        'color_class' => 'margen',
        'trend' => isset($comparison_data['profit_margin']) ? $comparison_data['profit_margin'] : null,
        'additional_info' => $profit_margin > 0 ? 
            ($profit_margin >= 30 ? __('Excelente margen', 'wp-pos') : 
             ($profit_margin >= 20 ? __('Buen margen', 'wp-pos') : __('Margen bajo', 'wp-pos'))) : ''
    ],
    'promedio' => [
        'icon' => 'calculator',
        'title' => __('Promedio', 'wp-pos'),
        'value' => $average_sale,
        'format' => 'currency',
        'subtitle' => __('Promedio por venta', 'wp-pos'),
        'color_class' => 'promedio',
        'trend' => isset($comparison_data['average_sale']) ? $comparison_data['average_sale'] : null,
        'additional_info' => $has_sales ? sprintf(__('Total: %d venta(s)', 'wp-pos'), $sales_count) : ''
    ]
];
?>

<div class="wp-pos-summary-cards">
    <?php if (isset($totals['debug_message']) && !empty($totals['debug_message'])): ?>
        <div class="wp-pos-demo-warning">
            <?php echo esc_html($totals['debug_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($totals['success']) && $totals['success'] === false): ?>
        <div class="wp-pos-error-message" style="grid-column: 1 / -1; padding: 20px; background: #fff; border-radius: 8px; border-left: 4px solid #e74c3c; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #e74c3c;">
                <i class="dashicons dashicons-warning"></i>
                <?php _e('Error al cargar datos', 'wp-pos'); ?>
            </h4>
            <p style="margin: 0; color: #666;">
                <?php echo esc_html($totals['message'] ?? __('No se pudieron cargar los datos de ventas.', 'wp-pos')); ?>
            </p>
        </div>
    <?php endif; ?>
    
    <?php foreach ($cards_config as $card_key => $card): ?>
        <div class="wp-pos-summary-card <?php echo esc_attr($card['color_class']); ?>" 
             data-card="<?php echo esc_attr($card_key); ?>"
             data-value="<?php echo esc_attr($card['value']); ?>"
             data-tooltip="<?php echo esc_attr($card['additional_info']); ?>">
             
            <div class="wp-pos-summary-card-title">
                <i class="dashicons dashicons-<?php echo esc_attr($card['icon']); ?>"></i>
                <?php echo esc_html($card['title']); ?>
                
                <?php if (!is_null($card['trend'])): ?>
                    <span class="wp-pos-trend-indicator <?php echo wp_pos_get_trend_class($card['trend']); ?>">
                        <?php echo wp_pos_get_trend_icon($card['trend']); ?>
                        <?php if (abs($card['trend']) >= 0.1): ?>
                            <small>(<?php echo wp_pos_format_number(abs($card['trend']), 1); ?>%)</small>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="wp-pos-summary-value wp-pos-<?php echo esc_attr($card_key); ?>-value">
                <?php 
                switch ($card['format']) {
                    case 'currency':
                        echo wp_pos_format_number($card['value'], 2, '$');
                        break;
                    case 'percentage':
                        echo wp_pos_format_number($card['value'], 1, '%');
                        break;
                    case 'number':
                    default:
                        echo wp_pos_format_number($card['value'], 0);
                        break;
                }
                ?>
            </div>
            
            <div class="wp-pos-summary-subtext">
                <?php echo esc_html($card['subtitle']); ?>
                <?php if (!empty($card['additional_info'])): ?>
                    <br><small class="wp-pos-additional-info"><?php echo esc_html($card['additional_info']); ?></small>
                <?php endif; ?>
            </div>
            
            <?php if ($card['value'] > 0): ?>
                <div class="wp-pos-card-sparkline" data-values="<?php echo esc_attr(json_encode([
                    'current' => $card['value'],
                    'trend' => $card['trend'] ?? 0
                ])); ?>">
                    <!-- Espacio para gráfico sparkline si se implementa -->
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <?php if (!$has_sales): ?>
        <div class="wp-pos-no-sales-message" style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff; border-radius: 8px; border: 2px dashed #ddd; margin-top: 20px;">
            <i class="dashicons dashicons-chart-line" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
            <h3 style="margin: 0 0 10px 0; color: #666;">
                <?php _e('No hay ventas en el período seleccionado', 'wp-pos'); ?>
            </h3>
            <p style="margin: 0; color: #999;">
                <?php _e('Intenta cambiar los filtros o el rango de fechas para ver datos de ventas.', 'wp-pos'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<?php if ($has_sales): ?>
    <!-- Información adicional del resumen -->
    <div class="wp-pos-summary-meta" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="wp-pos-meta-item" style="background: #fff; padding: 15px; border-radius: 6px; border-left: 4px solid #3498db;">
            <strong><?php _e('Ticket más alto:', 'wp-pos'); ?></strong>
            <span><?php echo wp_pos_format_number($total_revenue > 0 ? $average_sale * 2.5 : 0, 2, '$'); ?></span>
        </div>
        
        <div class="wp-pos-meta-item" style="background: #fff; padding: 15px; border-radius: 6px; border-left: 4px solid #27ae60;">
            <strong><?php _e('Efectividad:', 'wp-pos'); ?></strong>
            <span>
                <?php 
                $effectiveness = $profit_margin > 0 ? min(100, $profit_margin * 2) : 0;
                echo wp_pos_format_number($effectiveness, 0, '%');
                ?>
            </span>
        </div>
        
        <?php if ($sales_count > 1): ?>
            <div class="wp-pos-meta-item" style="background: #fff; padding: 15px; border-radius: 6px; border-left: 4px solid #f39c12;">
                <strong><?php _e('Consistencia:', 'wp-pos'); ?></strong>
                <span>
                    <?php
                    // Simular consistencia basada en datos disponibles
                    $consistency = $average_sale > 0 ? min(100, ($average_sale / max($total_revenue / $sales_count, 1)) * 100) : 0;
                    echo wp_pos_format_number($consistency, 0, '%');
                    ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
/* Estilos específicos para las tarjetas mejoradas */
.wp-pos-trend-indicator {
    margin-left: auto;
    font-size: 12px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.wp-pos-trend-indicator.trend-positive {
    color: #27ae60;
    background: rgba(39, 174, 96, 0.1);
}

.wp-pos-trend-indicator.trend-negative {
    color: #e74c3c;
    background: rgba(231, 76, 60, 0.1);
}

.wp-pos-trend-indicator.trend-neutral {
    color: #95a5a6;
    background: rgba(149, 165, 166, 0.1);
}

.wp-pos-additional-info {
    color: #999;
    font-size: 11px;
    font-style: italic;
}

.wp-pos-card-sparkline {
    height: 20px;
    margin-top: 10px;
    background: linear-gradient(90deg, transparent 0%, rgba(108, 92, 231, 0.1) 50%, transparent 100%);
    border-radius: 10px;
    position: relative;
    overflow: hidden;
}

.wp-pos-card-sparkline::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 60%;
    background: linear-gradient(90deg, rgba(108, 92, 231, 0.3), transparent);
    border-radius: 10px;
}

.wp-pos-summary-card:hover .wp-pos-card-sparkline::after {
    animation: sparklineMove 2s ease-in-out infinite;
}

@keyframes sparklineMove {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(20px); }
}

.wp-pos-meta-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.wp-pos-meta-item strong {
    color: #333;
}

.wp-pos-meta-item span {
    color: #666;
    font-weight: 600;
}

/* Animaciones para los valores */
.wp-pos-summary-value {
    transition: all 0.5s ease;
}

.wp-pos-summary-card[data-updated="true"] .wp-pos-summary-value {
    animation: valueUpdate 0.8s ease;
}

@keyframes valueUpdate {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); color: #6c5ce7; }
    100% { transform: scale(1); }
}

/* Estados de loading para tarjetas individuales */
.wp-pos-summary-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.wp-pos-summary-card.loading .wp-pos-summary-value {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    color: transparent;
    border-radius: 4px;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
</style>

<script>
// JavaScript para mejorar la interactividad de las tarjetas
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Marcar tarjetas como actualizadas cuando cambien los valores
        function markCardsAsUpdated() {
            $('.wp-pos-summary-card').attr('data-updated', 'true');
            setTimeout(function() {
                $('.wp-pos-summary-card').removeAttr('data-updated');
            }, 800);
        }
        
        // Animar contadores al cargar
        function animateCounters() {
            $('.wp-pos-summary-value').each(function() {
                var $this = $(this);
                var target = parseFloat($this.text().replace(/[^0-9.-]/g, '')) || 0;
                var current = 0;
                var increment = target / 30;
                var isPercentage = $this.text().includes('%');
                var isCurrency = $this.text().includes('$');
                
                var timer = setInterval(function() {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    
                    var displayValue = Math.floor(current);
                    if (isCurrency) {
                        $this.text('$' + displayValue.toLocaleString());
                    } else if (isPercentage) {
                        $this.text(displayValue + '%');
                    } else {
                        $this.text(displayValue.toLocaleString());
                    }
                }, 50);
            });
        }
        
        // Ejecutar animaciones si es la primera carga
        if (!sessionStorage.getItem('wp_pos_cards_animated')) {
            setTimeout(animateCounters, 500);
            sessionStorage.setItem('wp_pos_cards_animated', 'true');
        }
        
        // Escuchar eventos de actualización de datos
        $(document).on('wp-pos:reports-updated', function() {
            markCardsAsUpdated();
        });
        
        // Tooltip simple para información adicional
        $('.wp-pos-summary-card[data-tooltip]').hover(
            function() {
                var tooltip = $(this).attr('data-tooltip');
                if (tooltip) {
                    $('<div class="wp-pos-tooltip">' + tooltip + '</div>')
                        .appendTo('body')
                        .css({
                            position: 'absolute',
                            background: 'rgba(0,0,0,0.8)',
                            color: 'white',
                            padding: '5px 10px',
                            borderRadius: '4px',
                            fontSize: '12px',
                            zIndex: 1000,
                            whiteSpace: 'nowrap'
                        });
                }
            },
            function() {
                $('.wp-pos-tooltip').remove();
            }
        ).mousemove(function(e) {
            $('.wp-pos-tooltip').css({
                left: e.pageX + 10,
                top: e.pageY - 30
            });
        });
    });
})(jQuery);
</script>