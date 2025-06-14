# Módulo de Clientes

## Visión General
El módulo de clientes gestiona la información de los clientes, incluyendo sus datos personales, direcciones, historial de compras y preferencias. Facilita la gestión de relaciones con los clientes (CRM) y el seguimiento de sus actividades, con capacidades avanzadas de búsqueda y segmentación.

## Estructura de Archivos
```
modules/customers/
├── api/                    # Controladores de la API REST
│   └── class-pos-customers-rest-controller.php
├── assets/                 # Recursos estáticos
│   ├── css/
│   └── js/
├── includes/              # Clases y funciones auxiliares
├── templates/             # Plantillas para la interfaz de usuario
└── class-pos-customers-module.php  # Clase principal del módulo
```

## Clases Principales

### 1. WP_POS_Customers_Module
Gestiona la inicialización y configuración del módulo de clientes.

#### Métodos Principales
- `get_instance()`: Devuelve la instancia singleton del módulo
- `register_module()`: Registra el módulo en el sistema
- `register_customer_post_type()`: Registra el tipo de post personalizado para clientes
- `register_customer_meta()`: Registra los metadatos personalizados
- `save_customer_data()`: Guarda los datos del cliente

### 2. WP_POS_Customers_REST_Controller
Maneja las peticiones relacionadas con clientes a través de la API REST.

#### Endpoints Principales
- `GET /wp-json/wp-pos/v1/customers` - Lista de clientes
- `POST /wp-json/wp-pos/v1/customers` - Crear nuevo cliente
- `GET /wp-pos/v1/customers/(?P<id>\d+)` - Obtener detalles de un cliente
- `PUT /wp-pos/v1/customers/(?P<id>\d+)` - Actualizar un cliente
- `DELETE /wp-pos/v1/customers/(?P<id>\d+)` - Eliminar un cliente (lógico)

## Estructura de Datos

### Tabla Principal: wp_pos_customers
| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | BIGINT | Identificador único |
| first_name | VARCHAR(100) | Nombre(s) |
| last_name | VARCHAR(100) | Apellido(s) |
| email | VARCHAR(255) | Correo electrónico |
| phone | VARCHAR(50) | Teléfono |
| document_type | VARCHAR(20) | Tipo de documento |
| document_number | VARCHAR(50) | Número de documento |
| birthdate | DATE | Fecha de nacimiento |
| status | ENUM | Estado (activo, inactivo) |
| created_at | DATETIME | Fecha de creación |
| updated_at | DATETIME | Última actualización |

### Tablas Relacionadas
- `wp_pos_customer_addresses` - Direcciones del cliente
- `wp_pos_customer_meta` - Metadatos adicionales
- `wp_pos_customer_notes` - Notas sobre el cliente

## Gestión de Clientes

### Flujo de Registro
1. Creación del perfil del cliente
2. Verificación de datos (opcional)
3. Asignación de grupo/clasificación
4. Configuración de preferencias y notificaciones

### Características Avanzadas

#### Búsqueda y Filtrado
- Búsqueda en tiempo real por:
  - Nombre completo o parcial
  - Documento de identidad
  - Correo electrónico
  - Teléfono
  - Código de cliente
- Filtros avanzados:
  - Historial de compras
  - Ubicación geográfica
  - Segmentación por valor
  - Fechas de última compra
  - Estado de cuenta

#### Gestión de Datos
- Perfiles detallados con campos personalizables
- Historial completo de interacciones
- Documentos adjuntos
- Notas internas
- Etiquetas personalizadas

#### Importación/Exportación
- Plantillas personalizables
- Asignación de campos
- Validación de datos
- Procesamiento por lotes
- Registro de operaciones

## Hooks y Filtros

### Acciones
- `wp_pos_before_save_customer` - Antes de guardar un cliente
- `wp_pos_after_save_customer` - Después de guardar un cliente
- `wp_pos_before_delete_customer` - Antes de eliminar un cliente
- `wp_pos_after_delete_customer` - Después de eliminar un cliente

### Filtros
- `wp_pos_customer_data` - Filtra los datos del cliente
- `wp_pos_customer_fields` - Filtra los campos del formulario de cliente
- `wp_pos_customer_search_args` - Filtra los argumentos de búsqueda

## Integración con Otros Módulos

### Módulo de Ventas
- Asociación automática de ventas
- Historial detallado de compras
- Estado de cuenta corriente
- Límites de crédito
- Documentos comerciales

### Módulo de Marketing
- Segmentación avanzada
  - Comportamiento de compra
  - Valor de por vida (LTV)
  - Frecuencia de compra
- Campañas personalizadas
  - Email marketing
  - SMS/WhatsApp
  - Notificaciones push
- Programas de fidelización
  - Puntos y recompensas
  - Niveles y beneficios
  - Cumpleaños y aniversarios

### Módulo de Atención al Cliente
- Tickets de soporte
- Registro de incidencias
- Seguimiento de garantías
- Encuestas de satisfacción

## Seguridad

- Validación de permisos en cada operación
- Encriptación de datos sensibles
- Protección contra inyección SQL
- Cumplimiento de normativas de privacidad (GDPR, LGPD, etc.)

## Optimización de Rendimiento

- Caché de consultas frecuentes
- Paginación inteligente de resultados
- Carga progresiva de datos
- Índices optimizados en la base de datos
- Compresión de imágenes
- Agregación de datos para reportes

## Seguridad y Privacidad

### Control de Acceso
- Permisos granulares por rol de usuario
- Registro detallado de accesos
- Autenticación de dos factores
- Bloqueo por intentos fallidos

### Protección de Datos
- Encriptación de información sensible
- Máscara de datos confidenciales
- Cumplimiento RGPD/LGPD
- Políticas de retención
- Exportación de datos personales

## Características Recientemente Implementadas

1. **Búsqueda Mejorada**
   - Resultados en tiempo real
   - Búsqueda difusa
   - Corrección ortográfica
   - Búsqueda por voz

2. **Interfaz de Usuario**
   - Diseño responsivo
   - Accesibilidad mejorada
   - Atajos de teclado
   - Vistas personalizables

3. **Rendimiento**
   - Carga más rápida de listados
   - Mejor manejo de grandes volúmenes
   - Optimización de consultas

## Próximas Mejoras

1. Portal de autoservicio para clientes
2. Autenticación biométrica
3. Integración con redes sociales
4. Análisis predictivo de comportamiento
5. Chatbot de atención al cliente
6. Sistema de recomendaciones

---
*Última actualización: Mayo 2025*
