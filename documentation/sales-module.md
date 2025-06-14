# Módulo de Ventas (V2)

## Visión General
El módulo de ventas es el núcleo del sistema G-POS, gestionando todo el flujo de ventas desde la creación hasta el procesamiento de pagos y generación de recibos. La versión 2 incluye una interfaz rediseñada y mejoras significativas en usabilidad y rendimiento.

## Estructura de Archivos
```
modules/sales/
├── api/                    # Controladores de la API REST
│   └── class-pos-sales-rest-controller.php
├── assets/                 # Recursos estáticos
│   ├── css/
│   └── js/
├── includes/              # Clases y funciones auxiliares
├── templates/             # Plantillas para la interfaz de usuario
└── class-pos-sales-module.php  # Clase principal del módulo
```

## Clases Principales

### 1. WP_POS_Sales_Module
Clase principal que gestiona la inicialización y configuración del módulo.

#### Métodos Principales
- `get_instance()`: Devuelve la instancia singleton del módulo
- `register_module()`: Registra el módulo en el sistema
- `register_rest_routes()`: Define los endpoints de la API REST
- `register_admin_menu()`: Añade los menús de administración
- `load_assets()`: Carga los recursos necesarios (CSS/JS)

### 2. WP_POS_Sales_REST_Controller
Maneja todas las peticiones relacionadas con ventas a través de la API REST.

#### Endpoints Principales
- `GET /wp-json/wp-pos/v1/sales` - Lista de ventas
- `POST /wp-json/wp-pos/v1/sales` - Crear nueva venta
- `GET /wp-pos/v1/sales/(?P<id>\d+)` - Obtener detalles de una venta
- `PUT /wp-pos/v1/sales/(?P<id>\d+)` - Actualizar una venta
- `DELETE /wp-pos/v1/sales/(?P<id>\d+)` - Eliminar una venta

## Flujo de una Venta (V2)

1. **Inicio de Venta**
   - Interfaz optimizada para dispositivos táctiles
   - Búsqueda instantánea de productos
   - Asignación automática de transacción única
   - Registro de fecha/hora y usuario

2. **Agregar Productos/Servicios**
   - Búsqueda por código, código de barras o nombre
   - Separación visual clara entre productos y servicios
   - Validación en tiempo real de stock disponible
   - Cálculo automático de impuestos y descuentos
   - Soporte para atajos de teclado

3. **Gestión del Carrito**
   - Vista detallada de ítems
   - Modificación de cantidades
   - Aplicación de descuentos
   - Notas especiales por ítem

4. **Procesamiento de Pago**
   - Múltiples métodos de pago
   - Validación en tiempo real
   - Cálculo automático de cambio
   - Opción de facturación electrónica

5. **Finalización**
   - Actualización automática de inventario
   - Impresión/Envío de recibos
   - Registro completo de auditoría
   - Cierre automático de caja cuando aplica

## Hooks y Filtros

### Acciones
- `wp_pos_before_create_sale` - Antes de crear una venta
- `wp_pos_after_create_sale` - Después de crear una venta
- `wp_pos_before_update_sale` - Antes de actualizar una venta
- `wp_pos_after_update_sale` - Después de actualizar una venta

### Filtros
- `wp_pos_sale_data` - Filtra los datos de la venta antes de guardar
- `wp_pos_sale_totals` - Filtra los totales de la venta
- `wp_pos_payment_methods` - Filtra los métodos de pago disponibles

## Integración con Otros Módulos

### Módulo de Productos
- Verificación de stock disponible
- Actualización de inventario al finalizar venta
- Gestión de variaciones de productos

### Módulo de Clientes
- Asociación de ventas a clientes
- Historial de compras
- Puntos de fidelización

## Gestión de Inventario

### Actualización en Tiempo Real
- Reducción automática de stock al confirmar ventas
- Validación de disponibilidad antes de procesar
- Manejo de productos agotados

### Características de Productos vs Servicios
- **Productos**: Control de inventario habilitado
  - Reducción automática de stock
  - Alertas de bajo inventario
  - Gestión de variantes

- **Servicios**: Sin control de inventario
  - Sin afectación de stock
  - Gestión de tiempo/duración
  - Asignación de recursos

## Manejo de Errores

El módulo implementa un sistema de códigos de error estandarizados:

| Código | Descripción |
|--------|-------------|
| 400 | Datos de entrada inválidos |
| 401 | No autorizado |
| 403 | Prohibido |
| 404 | Venta/Producto no encontrado |
| 409 | Conflicto (ej. stock insuficiente) |
| 422 | Validación fallida |
| 500 | Error interno del servidor |
| 503 | Servicio no disponible |

## Seguridad

- Validación de permisos en cada endpoint
- Sanitización de datos de entrada
- Protección contra CSRF
- Registro de auditoría de operaciones sensibles

## Características Recientemente Implementadas

1. **Nueva Interfaz de Usuario (V2)**
   - Diseño optimizado para pantallas táctiles
   - Navegación más intuitiva
   - Mejor organización de funciones

2. **Mejoras en Inventario**
   - Corrección de actualización de stock
   - Mejor manejo de productos vs servicios
   - Validación de disponibilidad mejorada

3. **Optimizaciones de Rendimiento**
   - Carga más rápida de productos
   - Búsqueda instantánea
   - Mejor manejo de sesiones

4. **Experiencia de Usuario**
   - Retroalimentación visual mejorada
   - Menos clics para operaciones comunes
   - Guías contextuales

## Próximas Mejoras

1. Integración con pasarelas de pago externas
2. Facturación electrónica
3. Sincronización en tiempo real multi-sucursal
4. Análisis de ventas en tiempo real
5. Sistema de devoluciones mejorado
6. Integración con programas de fidelización

---

*Última actualización: Mayo 2025*
