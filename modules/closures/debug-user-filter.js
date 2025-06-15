// Debug script para verificar el filtro por usuario en Closures
// Ejecutar en la consola del navegador (F12) cuando estés en el formulario de cierre

console.log('🧪 INICIANDO DEBUG DEL FILTRO POR USUARIO');

// Función para probar el filtro por usuario
function testUserFilter() {
    console.log('=== TEST FILTRO POR USUARIO ===');
    
    // 1. Obtener dropdown de usuario
    const userDropdown = document.getElementById('closure-user');
    if (!userDropdown) {
        console.error('❌ No se encontró el dropdown de usuario #closure-user');
        return;
    }
    
    console.log('✅ Dropdown de usuario encontrado');
    console.log('Opciones disponibles:', Array.from(userDropdown.options).map(opt => ({
        value: opt.value,
        text: opt.textContent
    })));
    
    // 2. Verificar event handler
    const events = jQuery._data(userDropdown, 'events');
    if (events && events.change) {
        console.log('✅ Event handler de change encontrado');
    } else {
        console.warn('⚠️ No se encontró event handler de change');
    }
    
    // 3. Función para probar con un usuario específico
    window.testWithUser = function(userId) {
        console.log(`🔍 PROBANDO CON USUARIO: ${userId}`);
        
        // Cambiar el valor del dropdown
        userDropdown.value = userId;
        
        // Simular el evento change
        const changeEvent = new Event('change', { bubbles: true });
        userDropdown.dispatchEvent(changeEvent);
        
        // También disparar el evento jQuery
        jQuery(userDropdown).trigger('change');
        
        console.log('📤 Evento de cambio disparado');
    };
    
    // 4. Función para monitorear las llamadas AJAX
    let originalAjax = jQuery.ajax;
    jQuery.ajax = function(options) {
        if (options.data && options.data.action === 'wp_pos_closures_calculate_amounts') {
            console.log('📡 LLAMADA AJAX INTERCEPTADA:', {
                action: options.data.action,
                user_id: options.data.user_id,
                date: options.data.date,
                register_id: options.data.register_id
            });
            
            // Interceptar la respuesta
            let originalSuccess = options.success;
            options.success = function(response) {
                console.log('📥 RESPUESTA AJAX RECIBIDA:', {
                    success: response.success,
                    total_transactions: response.data?.total_transactions,
                    payment_methods_cash: response.data?.payment_methods?.cash,
                    user_id: options.data.user_id
                });
                
                if (originalSuccess) {
                    return originalSuccess.apply(this, arguments);
                }
            };
        }
        
        return originalAjax.apply(this, arguments);
    };
    
    console.log('✅ Monitor AJAX instalado');
    
    // 5. Prueba automática con todos los usuarios
    window.testAllUsers = function() {
        console.log('🔄 INICIANDO PRUEBA CON TODOS LOS USUARIOS');
        
        const options = Array.from(userDropdown.options);
        let index = 0;
        
        function testNext() {
            if (index >= options.length) {
                console.log('✅ PRUEBA COMPLETADA CON TODOS LOS USUARIOS');
                return;
            }
            
            const option = options[index];
            console.log(`\n--- PROBANDO: ${option.textContent} (ID: ${option.value}) ---`);
            
            testWithUser(option.value);
            
            index++;
            setTimeout(testNext, 3000); // Esperar 3 segundos entre pruebas
        }
        
        testNext();
    };
    
    console.log('\n📋 COMANDOS DISPONIBLES:');
    console.log('• testWithUser("123") - Probar con usuario específico');
    console.log('• testAllUsers() - Probar con todos los usuarios automáticamente');
    console.log('• testWithUser("") - Probar con "Todos"');
}

// Ejecutar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', testUserFilter);
} else {
    testUserFilter();
}

console.log('🎯 Debug script cargado. Ejecuta testUserFilter() para iniciar las pruebas.');
