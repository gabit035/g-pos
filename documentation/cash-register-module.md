# Módulo de Cierres de Caja

## Visión General
El módulo de cierres de caja gestiona el control de efectivo, permitiendo realizar aperturas, cierres parciales y cierres finales de caja. Facilita la conciliación entre el dinero físico y las transacciones registradas en el sistema. Incluye un sistema de aprobación/rechazo de cierres con justificación obligatoria para los rechazos.

## Estructura de Archivos
```
modules/cash-register/
├── api/                    # Controladores de la API REST
│   └── class-pos-cash-register-controller.php
├── assets/                 # Recursos estáticos
│   ├── css/
│   └── js/
├── includes/              # Clases y funciones auxiliares
├── templates/             # Plantillas para la interfaz de usuario
└── class-pos-cash-register-module.php  # Clase principal del módulo
```

## Flujo de Trabajo

### 1. Apertura de Caja
- Verificación de saldo inicial
- Asignación de cajero
- Registro de observaciones
- Confirmación de apertura

### 2. Operaciones Durante el Turno
- Registro de ventas
- Retiros parciales
- Ingresos/egresos no relacionados con ventas
- Notas de crédito

### 3. Cierre de Caja
- Conteo físico de efectivo
- Conciliación con ventas registradas
- Registro de diferencias
- Generación de reporte de cierre

## Estructura de Datos

### Tabla Principal: wp_pos_cash_registers
| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | BIGINT | Identificador único |
| user_id | BIGINT | ID del usuario/cajero |
| status | ENUM | Estado (abierto, cerrado, en revisión) |
| opened_at | DATETIME | Fecha/hora de apertura |
| closed_at | DATETIME | Fecha/hora de cierre |
| opening_balance | DECIMAL(10,2) | Saldo inicial |
| closing_balance | DECIMAL(10,2) | Saldo final |
| expected_balance | DECIMAL(10,2) | Saldo esperado |
| difference | DECIMAL(10,2) | Diferencia |
| notes | TEXT | Observaciones |
| approved_by | BIGINT | ID del usuario que aprobó el cierre |
| approved_at | DATETIME | Fecha/hora de aprobación |

### Tablas Relacionadas
- `wp_pos_cash_register_shifts` - Turnos de caja
- `wp_pos_cash_register_transactions` - Transacciones de caja
- `wp_pos_cash_register_counts` - Conteos de efectivo

## Roles y Permisos

### Cajero
- Abrir/cerrar caja
- Registrar transacciones
- Realizar arqueos parciales
- Ver historial de cierres propios

### Vendedor
- Acceso al dashboard
- Ver reportes básicos
- Realizar ventas

### Supervisor
- Aprobar/rechazar cierres de caja
- Ver reportes detallados
- Realizar auditorías
- Justificar rechazos de cierres

### Administrador
- Configuración del módulo
- Acceso completo a todas las funciones
- Eliminar registros históricos
- Anulación de operaciones
- Gestión de permisos

## Hooks y Filtros

### Acciones
- `wp_pos_before_open_register` - Antes de abrir caja
- `wp_pos_after_open_register` - Después de abrir caja
- `wp_pos_before_close_register` - Antes de cerrar caja
- `wp_pos_after_close_register` - Después de cerrar caja
- `wp_pos_register_transaction` - Al registrar transacción

### Filtros
- `wp_pos_register_data` - Filtra los datos de la caja
- `wp_pos_register_permissions` - Filtra los permisos de caja
- `wp_pos_register_transaction_types` - Filtra los tipos de transacción

## Integración con Otros Módulos

### Módulo de Ventas
- Registro automático de ventas en caja
- Conciliación de transacciones
- Cálculo de comisiones

### Módulo de Usuarios
- Control de acceso
- Asignación de turnos
- Registro de responsabilidades

### Módulo de Reportes
- Reportes de caja
- Auditoría de operaciones
- Análisis de rendimiento

## Seguridad

- Validación de permisos en cada operación
- Registro detallado de auditoría
- Firmado digital de cierres
- Bloqueo de modificaciones posteriores

## Proceso de Cierre

1. **Preparación**
   - Verificación de transacciones pendientes
   - Conteo físico de efectivo
   - Validación de saldos

2. **Conciliación**
   - Comparación entre saldo físico y sistema
   - Registro de diferencias
   - Justificación obligatoria para discrepancias
   - Captura de firmas digitales cuando sea necesario

3. **Solicitud de Aprobación**
   - Envío automático a supervisor
   - Notificación en tiempo real
   - Historial de estados

4. **Revisión y Aprobación**
   - Validación por supervisor/administrador
   - Comentarios y observaciones
   - Opción de aprobar o rechazar con justificación
   - Confirmación de cierre

5. **Cierre**
   - Bloqueo de operaciones
   - Generación de comprobante
   - Notificaciones a partes interesadas
   - Archivo seguro de evidencia

## Características Recientemente Implementadas

1. **Sistema de Aprobación/Rechazo**
   - Flujo de trabajo para aprobación de cierres
   - Justificación obligatoria para rechazos
   - Notificaciones en tiempo real
   - Validación de permisos por roles

2. **Mejoras de Seguridad**
   - Validación de permisos mejorada
   - Registro detallado de auditoría
   - Protección contra modificaciones no autorizadas
   - Cifrado de datos sensibles

3. **Optimizaciones**
   - Interfaz más rápida y responsiva
   - Mejor manejo de errores
   - Carga optimizada de datos

## Próximas Mejoras

1. Integración con bóveda virtual
2. Soporte para múltiples monedas
3. Firmado digital avanzado
4. Sincronización en tiempo real
5. Análisis predictivo de flujo de caja
6. Alertas automáticas para discrepancias
7. Integración con sistemas contables externos

---
*Última actualización: Mayo 2025*
