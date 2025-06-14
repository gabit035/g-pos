# Documentación para Desarrolladores - G-POS v2

## Tabla de Contenidos
1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Seguridad](#seguridad)
5. [Hooks y Filtros](#hooks-y-filtros)
6. [API REST](#api-rest)
7. [Frontend](#frontend)
8. [Ejemplos de Uso](#ejemplos-de-uso)
9. [Guía de Contribución](#guía-de-contribución)
10. [Preguntas Frecuentes](#preguntas-frecuentes)
11. [Optimización](#optimización)
12. [Pruebas](#pruebas)

## Requisitos del Sistema

### Requisitos Mínimos
- PHP 8.0 o superior
- MySQL 8.0 o MariaDB 10.5 o superior
- WordPress 6.0 o superior
- Extensiones PHP requeridas:
  - PDO
  - JSON
  - MBString
  - cURL
  - OpenSSL
  - GD/Imagick
  - ZIP
  - Intl
  - BCMath

### Configuración Recomendada
- PHP 8.2+
- MySQL 8.0+ o MariaDB 10.6+
- Memoria PHP: 512MB o superior
- Límite de ejecución: 300 segundos
- Tamaño máximo de subida: 128MB
- OPcache habilitado
- Redis/Memcached para caché

### Compatibilidad
- Navegadores modernos (últimas 2 versiones)
- Dispositivos móviles (responsive)
- Módulos de WordPress populares
- Estándares de accesibilidad WCAG 2.1 AA

## Arquitectura del Sistema

### Patrones de Diseño
- **Arquitectura Modular**: Sistema basado en módulos independientes
- **Patrón MVC**: Separación clara entre Modelo, Vista y Controlador
- **Inyección de Dependencias**: Para un acoplamiento flexible
- **Patrón Repositorio**: Para el acceso a datos
- **Patrón Fábrica**: Para la creación de objetos complejos
- **Patrón Observador**: Para eventos y notificaciones

### Flujo de Datos
1. **Capa de Presentación**
   - Interfaz de usuario (HTML/CSS/JS)
   - Plantillas Twig/Blade
   - Componentes Vue.js/React

2. **Capa de Aplicación**
   - Controladores
   - Middleware
   - Validación
   - Transformación de datos

3. **Capa de Dominio**
   - Lógica de negocio
   - Entidades
   - Reglas de negocio
   - Servicios de dominio

4. **Capa de Infraestructura**
   - Persistencia de datos
   - Caché
   - Colas
   - Almacenamiento

## Estructura del Proyecto

```
G-POS/
├── assets/                 # Recursos estáticos (CSS, JS, imágenes)
│   ├── css/               # Estilos CSS/SCSS
│   ├── js/                # Scripts JavaScript/TypeScript
│   └── images/            # Imágenes y recursos gráficos
├── config/                # Archivos de configuración
│   ├── app.php           # Configuración principal
│   ├── database.php      # Configuración de base de datos
│   └── cache.php         # Configuración de caché
├── includes/              # Núcleo del sistema
│   ├── admin/            # Funcionalidades de administración
│   ├── api/              # Controladores de la API REST
│   ├── class/            # Clases principales
│   │   ├── Core/        # Clases del núcleo
│   │   ├── Models/      # Modelos de datos
│   │   ├── Services/    # Servicios de negocio
│   │   └── Traits/      # Traits reutilizables
│   ├── helpers/          # Funciones auxiliares
│   └── interfaces/       # Interfaces y contratos
├── modules/              # Módulos del sistema
│   ├── sales/           # Módulo de ventas (V2)
│   ├── products/        # Gestión de productos/servicios
│   ├── customers/       # Gestión de clientes
│   ├── inventory/       # Control de inventario
│   ├── reports/         # Reportes y análisis
│   └── ...
├── templates/           # Plantillas de la interfaz
│   ├── admin/           # Plantillas de administración
│   ├── frontend/        # Plantillas de frontend
│   └── emails/          # Plantillas de correo
├── tests/               # Pruebas automatizadas
│   ├── Unit/           # Pruebas unitarias
│   ├── Feature/        # Pruebas de características
│   └── Browser/        # Pruebas de navegador
├── vendor/              # Dependencias de Composer
├── .github/             # Configuración de GitHub
├── .vscode/             # Configuración de VS Code
├── docs/                # Documentación técnica
└── languages/           # Archivos de idioma
```

## Seguridad

### Buenas Prácticas
- **Validación de Entrada**: Siempre validar y sanitizar datos de entrada
- **Escape de Salida**: Escapar datos antes de mostrarlos
- **Nonces**: Protección contra CSRF
- **Capacidades**: Control de acceso basado en roles
- **Seguridad en Base de Datos**: Consultas preparadas
- **Protección de Archivos**: Restricción de acceso directo
- **Cifrado**: Para datos sensibles
- **Registro de Auditoría**: Logging de actividades sensibles

### Recomendaciones de Seguridad
- Mantener actualizado el plugin y sus dependencias
- Usar HTTPS en producción
- Implementar autenticación de dos factores
- Realizar copias de seguridad periódicas
- Monitorear registros de seguridad
- Realizar auditorías de seguridad periódicas

## Hooks y Filtros

### Filtros Disponibles

#### Productos
- `wp_pos_product_data`
  - Filtra los datos del producto antes de guardar
  - **Parámetros:** `$product_data` (array), `$product_id` (int)
  - **Retorna:** array de datos del producto

- `wp_pos_product_price`
  - Filtra el precio de un producto
  - **Parámetros:** `$price` (float), `$product_id` (int), `$context` (string)
  - **Retorna:** float con el precio modificado

#### Ventas
- `wp_pos_sale_data`
  - Filtra los datos de la venta antes de guardar
  - **Parámetros:** `$sale_data` (array)
  - **Retorna:** array de datos de venta

- `wp_pos_sale_statuses`
  - Filtra los estados disponibles para las ventas
  - **Parámetros:** `$statuses` (array)
  - **Retorna:** array de estados

### Acciones Disponibles

#### Eventos de Producto
- `wp_pos_before_save_product`
  - Se ejecuta antes de guardar un producto
  - **Parámetros:** `$product_id` (int), `$product_data` (array)

- `wp_pos_after_save_product`
  - Se ejecuta después de guardar un producto
  - **Parámetros:** `$product_id` (int), `$product_data` (array), `$is_update` (bool)

#### Eventos de Venta
- `wp_pos_before_create_sale`
  - Se ejecuta antes de crear una venta
  - **Parámetros:** `$sale_data` (array)

- `wp_pos_after_create_sale`
  - Se ejecuta después de crear una venta
  - **Parámetros:** `$sale_id` (int), `$sale_data` (array)

## API REST

### Endpoints Principales
- `POST /wp-json/pos/v1/products` - Crear producto
- `GET /wp-json/pos/v1/products` - Listar productos
- `GET /wp-json/pos/v1/products/{id}` - Obtener producto
- `PUT /wp-json/pos/v1/products/{id}` - Actualizar producto
- `DELETE /wp-json/pos/v1/products/{id}` - Eliminar producto

### Autenticación
- Autenticación por cookie de WordPress
- Tokens JWT
- Claves API
- OAuth 2.0 (próximamente)

### Formato de Respuesta
```json
{
  "success": true,
  "data": {},
  "message": "Operación exitosa",
  "pagination": {}
}
```

### Manejo de Errores
```json
{
  "success": false,
  "error": {
    "code": "invalid_data",
    "message": "Datos inválidos",
    "data": {
      "field": "name",
      "message": "El nombre es requerido"
    }
  }
}
```

## Frontend

### Estructura de Componentes
```
assets/js/components/
├── common/           # Componentes reutilizables
│   ├── Button.vue
│   ├── Modal.vue
│   └── ...
├── pos/             # Componentes específicos de POS
│   ├── Cart.vue
│   ├── ProductList.vue
│   └── ...
└── admin/           # Componentes de administración
    ├── Dashboard.vue
    └── ...
```

### Gestión de Estado
- Vuex para gestión de estado global
- Módulos para cada funcionalidad
- Persistencia de estado
- Time travel para desarrollo

### Estilos
- SCSS como preprocesador
- Variables CSS para temas
- Metodología BEM
- Diseño responsivo
- Soporte para modo oscuro

## Ejemplos de Uso

### 1. Crear un Nuevo Producto (API REST)

```javascript
// Usando la API REST
const response = await wp.apiFetch({
  path: '/pos/v1/products',
  method: 'POST',
  data: {
    name: 'Producto de Ejemplo',
    sku: 'PROD-001',
    price: 29.99,
    cost: 15.50,
    stock_quantity: 100,
    type: 'simple',
    status: 'publish',
    categories: [1, 2],
    tax_status: 'taxable',
    tax_class: ''
  }
});

if (response.success) {
  console.log('Producto creado con ID:', response.data.id);
} else {
  console.error('Error:', response.error.message);
}
```

### 2. Registrar una Nueva Venta

```php
$sale_data = array(
    'customer_id'   => 123,
    'status'        => 'completed',
    'payment_method'=> 'cash',
    'items'         => array(
        array(
            'product_id' => 456,
            'quantity'   => 2,
            'price'      => 29.99,
            'discount'   => 0,
            'tax'        => 6.30
        )
    ),
    'discounts'     => 0,
    'shipping'      => 5.99,
    'taxes'         => 6.30,
    'total'         => 65.28,
    'notes'         => 'Venta de prueba',
    'created_by'    => get_current_user_id()
);

$sale_id = WP_POS_Sales_Controller::get_instance()->create_sale($sale_data);

if (is_wp_error($sale_id)) {
    // Manejar error
    error_log($sale_id->get_error_message());
} else {
    // Venta registrada exitosamente
    echo "Venta registrada con ID: " . $sale_id;
}
```

### 3. Usar Filtros para Modificar Comportamiento

```php
// Añadir un 10% de descuento a productos específicos
add_filter('wp_pos_product_price', function($price, $product_id, $context) {
    $discount_products = array(123, 456, 789);
    
    if (in_array($product_id, $discount_products)) {
        return $price * 0.9; // Aplicar 10% de descuento
    }
    
    return $price;
}, 10, 3);

// Registrar acción personalizada después de crear una venta
add_action('wp_pos_after_create_sale', function($sale_id, $sale_data) {
    // Enviar notificación personalizada
    $customer = get_user_by('id', $sale_data['customer_id']);
    $message = sprintf(
        'Nueva venta #%d realizada por %s por un total de %s',
        $sale_id,
        $customer->display_name,
        wc_price($sale_data['total'])
    );
    
    // Ejemplo: Enviar notificación a un canal de Slack
    wp_remote_post('https://hooks.slack.com/services/...', array(
        'body' => json_encode(array('text' => $message))
    ));
}, 10, 2);
```

## Optimización

### Rendimiento
- Caché de consultas
- Carga diferida de recursos
- Minificación de assets
- Optimización de imágenes
- Uso de CDN
- Compresión GZIP/Brotli

### Base de Datos
- Índices optimizados
- Consultas eficientes
- Paginación de resultados
- Limpieza de datos antiguos
- Optimización de tablas

### Frontend
- Code splitting
- Lazy loading
- Optimización de imágenes
- Uso de service workers
- Critical CSS

## Pruebas

### Tipos de Pruebas
- **Unitarias**: Pruebas de unidades individuales
- **Integración**: Pruebas de interacción entre componentes
- **Aceptación**: Pruebas de flujos completos
- **Rendimiento**: Pruebas de carga y estrés
- **Seguridad**: Pruebas de penetración

### Herramientas Recomendadas
- PHPUnit para pruebas de PHP
- Jest para pruebas de JavaScript
- Codeception para pruebas de aceptación
- K6 para pruebas de carga
- PHPStan para análisis estático

## Guía de Contribución

### 1. Reportar Problemas
- Verificar si el problema ya existe en los issues
- Usar la plantilla de reporte de errores
- Proporcionar información detallada:
  - Versión de WordPress
  - Versión de PHP
  - Versión de G-POS
  - Pasos para reproducir el error
  - Mensajes de error relevantes
  - Capturas de pantalla (si aplica)
  - Configuración relevante

### 2. Enviar Pull Requests
- Crear una rama descriptiva
- Seguir las guías de estilo
- Incluir pruebas unitarias
- Actualizar documentación
- Asegurar compatibilidad con versiones anteriores
- Revisar el código antes de enviar

### 3. Estándares de Código
- PSR-12 para PHP
- JavaScript Standard Style
- Comentarios PHPDoc
- Documentación en línea
- Convenciones de nomenclatura

### 4. Proceso de Revisión
- Revisión de código por pares
- Pruebas automáticas
- Verificación de estándares
- Aprobación de mantenedores
- Merge a la rama principal
1. Crear un fork del repositorio
2. Crear una rama descriptiva: `git checkout -b feature/nueva-funcionalidad`
3. Realizar commits atómicos con mensajes descriptivos
4. Actualizar la documentación según sea necesario
5. Enviar el pull request a la rama `develop`

### 3. Estándares de Código
- Seguir los estándares de codificación de WordPress
- Documentar todas las funciones y clases con PHPDoc
- Escribir pruebas unitarias para nuevo código
- Mantener la retrocompatibilidad

## Preguntas Frecuentes

### General

**¿Cómo actualizo a la última versión?**
1. Haz una copia de seguridad de tu sitio
2. Ve a Plugins > Actualizaciones en tu panel de WordPress
3. Busca G-POS y haz clic en "Actualizar ahora"
4. Revisa el registro de cambios para cambios importantes

**¿Es compatible con [plugin X]?**
G-POS es compatible con la mayoría de los plugins populares de WordPress. Si encuentras algún problema de compatibilidad, por favor repórtalo en nuestro repositorio de GitHub.

**¿Cómo puedo personalizar la interfaz?**
Puedes sobrescribir las plantillas en tu tema hijo o usar los filtros y acciones proporcionados. Consulta la documentación para más detalles.

### Desarrollo

**¿Cómo puedo extender la funcionalidad?**
Puedes crear extensiones usando el sistema de hooks y filtros. También puedes crear complementos que se integren con G-POS.

**¿Dónde puedo encontrar documentación de la API?**
La documentación completa de la API está disponible en `wp-json/pos/v1` en tu instalación de WordPress.

**¿Cómo puedo contribuir al proyecto?**
Las contribuciones son bienvenidas. Por favor, lee nuestra guía de contribución y sigue el proceso de envío de pull requests.

### Solución de Problemas

**¿Qué hago si encuentro un error?**
1. Verifica si ya existe un reporte en GitHub
2. Si no existe, crea un nuevo issue con la plantilla proporcionada
3. Incluye toda la información solicitada
4. Estaremos encantados de ayudarte

**¿Cómo puedo hacer una copia de seguridad de mis datos?**
Recomendamos usar un plugin de respaldo como UpdraftPlus o BackWPup para realizar copias de seguridad completas de tu sitio, incluyendo la base de datos.

**¿Dónde puedo obtener soporte?**
- Para problemas técnicos: Crea un issue en GitHub
- Para soporte general: Usa los foros de WordPress
- Para soporte prioritario: Contrata un plan de soporte premium

---
*Última actualización: Mayo 2025*
*Versión del documento: 2.0.0*

### ¿Cómo extender la funcionalidad del plugin?
Puedes crear un plugin complementario que utilice los hooks y filtros proporcionados.

### ¿Cómo personalizar las plantillas?
Copia el archivo de plantilla deseado desde `templates/` a `your-theme/woocommerce/pos/` y modifícalo según tus necesidades.

### ¿Cómo habilito el modo de depuración?
Añade lo siguiente a tu `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### ¿Dónde puedo encontrar los logs?
Los logs se guardan en `wp-content/debug.log` cuando el modo de depuración está activado.

---
*Última actualización: Mayo 2025*
