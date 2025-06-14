# Módulo de Productos y Servicios

## Visión General
El módulo de productos y servicios gestiona el catálogo completo, incluyendo productos físicos (con control de inventario) y servicios (sin control de inventario). Ofrece un sistema robusto para la gestión de precios, categorías, atributos y variaciones, siendo fundamental para las operaciones del sistema POS.

## Estructura de Archivos
```
modules/products/
├── api/                    # Controladores de la API REST
│   └── class-pos-products-rest-controller.php
├── assets/                 # Recursos estáticos
│   ├── css/
│   └── js/
├── includes/              # Clases y funciones auxiliares
├── templates/             # Plantillas para la interfaz de usuario
└── class-pos-products-module.php  # Clase principal del módulo
```

## Tipos de Ítems

### 1. Productos Físicos
- Control de inventario habilitado
- Reducción automática de stock al vender
- Gestión de variantes (tamaño, color, etc.)
- Soporte para códigos de barras
- Alertas de stock bajo

### 2. Servicios
- Sin control de inventario
- Gestión de duración/citas
- Asignación de personal
- Categorización flexible

## Clases Principales

### 1. WP_POS_Products_Module
Gestiona la inicialización y configuración del módulo de productos y servicios.

#### Métodos Principales
- `get_instance()`: Devuelve la instancia singleton del módulo
- `register_module()`: Registra el módulo en el sistema
- `register_product_post_type()`: Registra los tipos de ítems (productos y servicios)
- `register_taxonomies()`: Registra las taxonomías (categorías, etiquetas)
- `save_product_meta()`: Guarda los metadatos del ítem
- `update_stock()`: Maneja la actualización de inventario
- `validate_stock()`: Valida la disponibilidad de stock

### 2. WP_POS_Products_REST_Controller
Maneja las peticiones relacionadas con productos a través de la API REST.

#### Endpoints Principales
- `GET /wp-json/wp-pos/v1/products` - Lista de productos
- `POST /wp-json/wp-pos/v1/products` - Crear nuevo producto
- `GET /wp-pos/v1/products/(?P<id>\d+)` - Obtener detalles de un producto
- `PUT /wp-pos/v1/products/(?P<id>\d+)` - Actualizar un producto
- `DELETE /wp-pos/v1/products/(?P<id>\d+)` - Eliminar un producto (lógico)

## Estructura de Datos

### Tabla Principal: wp_pos_products
| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | BIGINT | Identificador único |
| name | VARCHAR(255) | Nombre del producto |
| sku | VARCHAR(100) | Código único |
| price | DECIMAL(10,2) | Precio base |
| cost | DECIMAL(10,2) | Costo |
| stock_quantity | INT | Cantidad en inventario |
| low_stock_threshold | INT | Umbral de stock bajo |
| type | ENUM | Tipo (simple, variable, servicio) |
| status | ENUM | Estado (activo, inactivo) |
| created_at | DATETIME | Fecha de creación |
| updated_at | DATETIME | Última actualización |

### Tablas Relacionadas
- `wp_pos_product_meta` - Metadatos adicionales
- `wp_pos_product_categories` - Categorías de productos
- `wp_pos_product_variations` - Variaciones de productos

## Gestión de Inventario Avanzada

### Flujo de Actualización
1. Verificación en tiempo real de disponibilidad
2. Bloqueo de inventario durante el proceso de venta
3. Actualización atómica de cantidades
4. Notificaciones en tiempo real
5. Historial de movimientos

### Características de Inventario
- Control de stock por ubicación
- Múltiples almacenes
- Transferencias entre ubicaciones
- Ajustes de inventario
- Conteos cíclicos

### Métodos de Costeo
- Costo promedio ponderado
- FIFO (Primero en entrar, primero en salir)
- LIFO (Último en entrar, primero en salir)
- Costo estándar
- Precio minorista

## Integración con Módulo de Ventas

### Proceso de Venta
1. Validación de disponibilidad
2. Reserva temporal de inventario
3. Confirmación de venta
4. Actualización de existencias
5. Generación de alertas

### Gestión de Servicios
- Sin afectación de inventario
- Control de disponibilidad de personal
- Gestión de citas
- Tiempos de servicio

## Hooks y Filtros

### Acciones
- `wp_pos_before_save_product` - Antes de guardar un producto
- `wp_pos_after_save_product` - Después de guardar un producto
- `wp_pos_before_update_stock` - Antes de actualizar el inventario
- `wp_pos_after_update_stock` - Después de actualizar el inventario

### Filtros
- `wp_pos_product_data` - Filtra los datos del producto
- `wp_pos_product_price` - Filtra el precio del producto
- `wp_pos_product_categories` - Filtra las categorías disponibles

## Integración con Otros Módulos

### Módulo de Ventas
- Verificación de stock al agregar productos a una venta
- Actualización de inventario al finalizar una venta
- Cálculo de totales e impuestos

### Módulo de Reportes
- Análisis de productos más vendidos
- Rotación de inventario
- Margen de ganancia por producto

## Seguridad

- Validación de permisos en cada operación
- Sanitización de datos de entrada
- Protección contra inyección SQL
- Validación de tipos de datos

## Optimización de Rendimiento

- Caché de consultas frecuentes
- Carga perezosa de imágenes
- Paginación de resultados
- Índices optimizados en la base de datos

## Características Recientemente Implementadas

1. **Separación Productos/Servicios**
   - Gestión diferenciada en la interfaz
   - Flujos de trabajo optimizados
   - Filtros y búsquedas mejoradas

2. **Mejoras en Inventario**
   - Corrección de actualización de stock
   - Validación mejorada de cantidades
   - Historial detallado de movimientos

3. **Optimizaciones**
   - Rendimiento mejorado en catálogos grandes
   - Búsqueda más rápida
   - Mejor manejo de variantes

## Próximas Mejoras

1. Sincronización con proveedores
2. Gestión de lotes y fechas de vencimiento
3. Códigos de barras personalizados
4. Importación/exportación masiva
5. Kits/Paquetes de productos
6. Asignación de impuestos avanzada
7. Precios especiales por cliente/grupo

---
*Última actualización: Mayo 2025*
