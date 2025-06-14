/**
 * Indicador de carga mejorado para G-POS
 * Proporciona feedback visual durante operaciones asu00edncronas
 */

var WP_POS_LoadingIndicator = (function() {
    // Contador para IDs u00fanicos
    var indicatorCounter = 0;
    
    // Colores predeterminados
    var defaultColors = {
        primary: '#2271b1',  // Azul de WordPress
        secondary: '#f0f0f1' // Gris claro de WordPress
    };
    
    // Opciones predeterminadas
    var defaultOptions = {
        size: 'medium',      // small, medium, large
        color: 'primary',    // primary, custom
        customColor: null,   // color personalizado si color = 'custom'
        text: 'Procesando...', // texto a mostrar
        showText: true,      // mostrar texto
        overlay: false,      // mostrar superposiciU+00f3n
        fullscreen: false    // superposiciU+00f3n a pantalla completa
    };
    
    // Dimensiones segu00fan tamau00f1o
    var sizes = {
        small: {
            width: '15px',
            height: '15px',
            borderWidth: '2px'
        },
        medium: {
            width: '30px',
            height: '30px',
            borderWidth: '3px'
        },
        large: {
            width: '50px',
            height: '50px',
            borderWidth: '4px'
        }
    };
    
    // Crear un indicador de carga en un elemento
    function show(element, customOptions) {
        var options = Object.assign({}, defaultOptions, customOptions || {});
        var indicatorId = 'wp-pos-loading-' + (++indicatorCounter);
        
        // Si es un selector de cadena, obtener el elemento
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        // Si no se proporciona elemento o no es va00e1lido, usar el body para pantalla completa
        if (!element && options.fullscreen) {
            element = document.body;
            options.overlay = true;
        } else if (!element) {
            console.error('Elemento no va00e1lido para el indicador de carga');
            return null;
        }
        
        // Guardar la posiciU+00f3n original del elemento
        var originalPosition = window.getComputedStyle(element).position;
        if (originalPosition === 'static') {
            element.style.position = 'relative';
        }
        
        // Crear contenedor para el indicador
        var container = document.createElement('div');
        container.id = indicatorId;
        container.className = 'wp-pos-loading-container';
        
        // Aplicar estilos al contenedor
        var containerStyles = {
            position: options.overlay ? 'absolute' : 'relative',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 9999
        };
        
        // Si es overlay, cubrir todo el elemento
        if (options.overlay) {
            containerStyles.top = '0';
            containerStyles.left = '0';
            containerStyles.width = '100%';
            containerStyles.height = '100%';
            containerStyles.backgroundColor = 'rgba(255, 255, 255, 0.7)';
        }
        
        // Si es pantalla completa, fijar al viewport
        if (options.fullscreen) {
            containerStyles.position = 'fixed';
        }
        
        // Aplicar estilos al contenedor
        Object.assign(container.style, containerStyles);
        
        // Crear el spinner
        var spinner = document.createElement('div');
        spinner.className = 'wp-pos-loading-spinner';
        
        // Obtener tamau00f1o del spinner
        var sizeProps = sizes[options.size] || sizes.medium;
        
        // Obtener color del spinner
        var spinnerColor = options.color === 'custom' && options.customColor ?
            options.customColor : defaultColors.primary;
        
        // Aplicar estilos al spinner
        var spinnerStyles = {
            width: sizeProps.width,
            height: sizeProps.height,
            border: sizeProps.borderWidth + ' solid ' + defaultColors.secondary,
            borderTop: sizeProps.borderWidth + ' solid ' + spinnerColor,
            borderRadius: '50%',
            animation: 'wp-pos-spin 1s linear infinite'
        };
        
        Object.assign(spinner.style, spinnerStyles);
        
        // Au00f1adir el spinner al contenedor
        container.appendChild(spinner);
        
        // Au00f1adir texto si es necesario
        if (options.showText && options.text) {
            var textElement = document.createElement('div');
            textElement.className = 'wp-pos-loading-text';
            textElement.textContent = options.text;
            
            var textStyles = {
                marginTop: '10px',
                color: '#333',
                fontSize: options.size === 'large' ? '16px' : '14px'
            };
            
            Object.assign(textElement.style, textStyles);
            container.appendChild(textElement);
        }
        
        // Au00f1adir animaciU+00f3n CSS si no existe
        if (!document.getElementById('wp-pos-loading-keyframes')) {
            var style = document.createElement('style');
            style.id = 'wp-pos-loading-keyframes';
            style.textContent = `
                @keyframes wp-pos-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Au00f1adir el contenedor al elemento
        element.appendChild(container);
        
        // Devolver el ID para poder eliminar el indicador
        return {
            id: indicatorId,
            hide: function() {
                hide(indicatorId);
                // Restaurar la posiciU+00f3n original si la cambiamos
                if (originalPosition === 'static') {
                    element.style.position = originalPosition;
                }
            }
        };
    }
    
    // Ocultar un indicador de carga por ID
    function hide(indicatorId) {
        var indicator = document.getElementById(indicatorId);
        if (indicator && indicator.parentNode) {
            indicator.parentNode.removeChild(indicator);
        }
    }
    
    // Crear un indicador a pantalla completa
    function showFullscreen(text) {
        return show(null, {
            fullscreen: true,
            overlay: true,
            size: 'large',
            text: text || 'Procesando, espere por favor...'
        });
    }
    
    // Crear un indicador en el elemento con clase 'wp-pos-loading-target'
    function showInTargets(text) {
        var targets = document.querySelectorAll('.wp-pos-loading-target');
        var indicators = [];
        
        targets.forEach(function(target) {
            indicators.push(show(target, {
                overlay: true,
                text: text || 'Cargando...'
            }));
        });
        
        return {
            hideAll: function() {
                indicators.forEach(function(indicator) {
                    indicator.hide();
                });
            }
        };
    }
    
    // API pu00fablica
    return {
        show: show,
        hide: hide,
        showFullscreen: showFullscreen,
        showInTargets: showInTargets
    };
})();
