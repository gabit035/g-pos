<?php
/**
 * Herramienta de diagnóstico para el Total Efectivo
 * 
 * Este script ayuda a detectar problemas con el cálculo del Total Efectivo
 * para un usuario específico en una fecha determinada.
 */

// Parámetros de conexión a la base de datos
// Cambiar estos valores según la configuración local
$db_host = 'localhost';
$db_name = 'pep';
$db_user = 'root';
$db_password = '';
$table_prefix = 'wp_';

// Conectar a la base de datos
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    echo "<p style='color:green'>Conectado exitosamente a la base de datos.</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>Error de conexión: " . $e->getMessage() . "</p>");
}

// Agregamos estilos CSS para hacer el informe más legible
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    tr:hover { background-color: #f5f5f5; }
    .highlight { background-color: #ffcccc; }
    .success { color: green; }
    .error { color: red; }
</style>";

// Cabecera
echo "<h1>Herramienta de Diagnóstico para Total Efectivo</h1>";
echo "<p>Esta herramienta busca el origen del total de efectivo incorrecto para un usuario específico.</p>";

// Obtener parámetros
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$register_id = isset($_GET['register_id']) ? (int)$_GET['register_id'] : 1;

// Mostrar parámetros recibidos
echo "<div style='background-color: #f2f2f2; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>Parámetros de búsqueda:</h3>";
echo "<ul>";
echo "<li><strong>Fecha:</strong> {$date}</li>";
echo "<li><strong>ID Usuario:</strong> {$user_id}</li>";
echo "<li><strong>ID Registro:</strong> {$register_id}</li>";
echo "</ul>";
echo "</div>";





// Obtener todas las tablas con prefijo pos_
echo "<h2>Tablas de la base de datos:</h2>";
$prefix = $table_prefix;
$stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}pos_%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<ul>";
foreach ($tables as $table_name) {
    echo "<li>{$table_name}</li>";
}
echo "</ul>";

// Buscar en la tabla de ventas (pos_sales)
echo "<h2>Registros en pos_sales para la fecha {$date}:</h2>";
$pos_sales_table = $table_prefix . 'pos_sales';
$stmt = $pdo->query("SHOW TABLES LIKE '{$pos_sales_table}'");
$sales_table_exists = $stmt->rowCount() > 0;

if (!$sales_table_exists) {
    echo "<p>La tabla {$pos_sales_table} no existe.</p>";
} else {
    // Obtener estructura de la tabla
    $stmt = $pdo->query("DESCRIBE {$pos_sales_table}");
    $columns = $stmt->fetchAll();
    $column_names = array_map(function($col) { return $col->Field; }, $columns);
    
    // Buscar columna de fecha
    $date_column = 'created_at'; // Valor predeterminado
    $date_columns_priority = ['date_created', 'created_at', 'date', 'timestamp'];
    foreach ($columns as $column) {
        foreach ($date_columns_priority as $priority_name) {
            if (strtolower($column->Field) === strtolower($priority_name)) {
                $date_column = $column->Field;
                break 2;
            }
        }
    }
    
    // Determinar si existe columna para método de pago
    $payment_method_column = '';
    foreach ($column_names as $col) {
        if (strpos(strtolower($col), 'payment_method') !== false || 
            strpos(strtolower($col), 'payment') !== false ||
            strpos(strtolower($col), 'method') !== false) {
            $payment_method_column = $col;
            break;
        }
    }

    // Columnas para usuario
    $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
    $found_user_column = '';
    foreach ($user_columns as $user_col) {
        if (in_array($user_col, $column_names)) {
            $found_user_column = $user_col;
            break;
        }
    }

    // Mostrar la estructura de la tabla
    echo "<h3>Estructura de la tabla:</h3>";
    echo "<ul>";
    echo "<li>Columnas: " . implode(", ", $column_names) . "</li>";
    echo "<li>Columna de fecha detectada: {$date_column}</li>";
    echo "<li>Columna de método de pago detectada: {$payment_method_column}</li>";
    echo "<li>Columna de usuario detectada: {$found_user_column}</li>";
    echo "</ul>";

    // Query para ventas en efectivo
    $query = "SELECT * FROM {$pos_sales_table} WHERE DATE({$date_column}) = :date";
    $args = ['date' => $date];
    
    // Filtro de método de pago (efectivo)
    if ($payment_method_column) {
        $cash_values = ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'];
        $placeholders = implode(',', array_fill(0, count($cash_values), '%s'));
        $query .= " AND {$payment_method_column} IN ({$placeholders})";
        $args = array_merge($args, $cash_values);
    }
    
    // Filtro de usuario si se especificó
    if ($user_id > 0 && $found_user_column) {
        $query .= " AND {$found_user_column} = %d";
        $args[] = $user_id;
    }
    
    // Ejecutar consulta
    $stmt = $pdo->prepare($query);
    $stmt->execute($args);
    $sales = $stmt->fetchAll();
    
    if (empty($sales)) {
        echo "<p>No se encontraron ventas en efectivo para la fecha y usuario seleccionados.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach ($column_names as $col) {
            echo "<th>{$col}</th>";
        }
        echo "</tr>";
        
        $total_amount = 0;
        
        foreach ($sales as $sale) {
            echo "<tr>";
            foreach ($column_names as $col) {
                echo "<td>{$sale->$col}</td>";
            }
            echo "</tr>";
            
            // Sumar al total si existe columna de total
            if (isset($sale->total)) {
                $total_amount += (float) $sale->total;
            } elseif (isset($sale->amount)) {
                $total_amount += (float) $sale->amount;
            }
        }
        
        echo "</table>";
        echo "<p><strong>Total ventas en efectivo: $" . number_format($total_amount, 2) . "</strong></p>";
    }
}

// Buscar en la tabla de transacciones (pos_transactions)
echo "<h2>Registros en pos_transactions para la fecha {$date}:</h2>";
$transactions_table = $wpdb->prefix . 'pos_transactions';
$transactions_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$transactions_table}'") === $transactions_table;

if (!$transactions_table_exists) {
    echo "<p>La tabla {$transactions_table} no existe.</p>";
} else {
    // Misma lógica que para pos_sales pero para transacciones
    $columns = $wpdb->get_results("DESCRIBE {$transactions_table}");
    $column_names = array_map(function($col) { return $col->Field; }, $columns);
    
    // Buscar columna de fecha
    $date_column = 'created_at'; // Valor predeterminado
    $date_columns_priority = ['date_created', 'created_at', 'date', 'timestamp'];
    foreach ($columns as $column) {
        foreach ($date_columns_priority as $priority_name) {
            if (strtolower($column->Field) === strtolower($priority_name)) {
                $date_column = $column->Field;
                break 2;
            }
        }
    }
    
    // Buscar columna de tipo de transacción
    $type_column = '';
    foreach ($column_names as $col) {
        if (strpos(strtolower($col), 'type') !== false || 
            strpos(strtolower($col), 'payment_method') !== false) {
            $type_column = $col;
            break;
        }
    }
    
    // Columnas para usuario
    $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
    $found_user_column = '';
    foreach ($user_columns as $user_col) {
        if (in_array($user_col, $column_names)) {
            $found_user_column = $user_col;
            break;
        }
    }
    
    echo "<h3>Estructura de la tabla:</h3>";
    echo "<ul>";
    echo "<li>Columnas: " . implode(", ", $column_names) . "</li>";
    echo "<li>Columna de fecha detectada: {$date_column}</li>";
    echo "<li>Columna de tipo detectada: {$type_column}</li>";
    echo "<li>Columna de usuario detectada: {$found_user_column}</li>";
    echo "</ul>";
    
    // Query para transacciones en efectivo
    $query = "SELECT * FROM {$transactions_table} WHERE DATE({$date_column}) = %s";
    $args = [$date];
    
    // Filtro de tipo (efectivo)
    if ($type_column) {
        $cash_values = ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'];
        $placeholders = implode(',', array_fill(0, count($cash_values), '%s'));
        $query .= " AND {$type_column} IN ({$placeholders})";
        $args = array_merge($args, $cash_values);
    }
    
    // Filtro de usuario si se especificó
    if ($user_id > 0 && $found_user_column) {
        $query .= " AND {$found_user_column} = %d";
        $args[] = $user_id;
    }
    
    // Ejecutar consulta
    $transactions = $wpdb->get_results($wpdb->prepare($query, $args));
    
    if (empty($transactions)) {
        echo "<p>No se encontraron transacciones en efectivo para la fecha y usuario seleccionados.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach ($column_names as $col) {
            echo "<th>{$col}</th>";
        }
        echo "</tr>";
        
        $total_amount = 0;
        
        foreach ($transactions as $transaction) {
            echo "<tr>";
            foreach ($column_names as $col) {
                echo "<td>{$transaction->$col}</td>";
            }
            echo "</tr>";
            
            // Sumar al total si existe columna de monto
            if (isset($transaction->amount)) {
                $total_amount += (float) $transaction->amount;
            } elseif (isset($transaction->total)) {
                $total_amount += (float) $transaction->total;
            }
        }
        
        echo "</table>";
        echo "<p><strong>Total transacciones en efectivo: $" . number_format($total_amount, 2) . "</strong></p>";
    }
}

// Buscar en la tabla de pagos (pos_payments)
echo "<h2>Registros en pos_payments para la fecha {$date}:</h2>";
$payments_table = $wpdb->prefix . 'pos_payments';
$payments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$payments_table}'") === $payments_table;

if (!$payments_table_exists) {
    echo "<p>La tabla {$payments_table} no existe.</p>";
} else {
    // Misma lógica para la tabla de pagos
    $columns = $wpdb->get_results("DESCRIBE {$payments_table}");
    $column_names = array_map(function($col) { return $col->Field; }, $columns);
    
    // Buscar columna de fecha
    $date_column = 'created_at'; // Valor predeterminado
    $date_columns_priority = ['date_created', 'created_at', 'date', 'timestamp'];
    foreach ($columns as $column) {
        foreach ($date_columns_priority as $priority_name) {
            if (strtolower($column->Field) === strtolower($priority_name)) {
                $date_column = $column->Field;
                break 2;
            }
        }
    }
    
    // Determinar si existe columna para método de pago
    $payment_method_column = '';
    foreach ($column_names as $col) {
        if (strpos(strtolower($col), 'payment_method') !== false || 
            strpos(strtolower($col), 'payment') !== false ||
            strpos(strtolower($col), 'method') !== false) {
            $payment_method_column = $col;
            break;
        }
    }
    
    // Columnas para usuario
    $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
    $found_user_column = '';
    foreach ($user_columns as $user_col) {
        if (in_array($user_col, $column_names)) {
            $found_user_column = $user_col;
            break;
        }
    }
    
    echo "<h3>Estructura de la tabla:</h3>";
    echo "<ul>";
    echo "<li>Columnas: " . implode(", ", $column_names) . "</li>";
    echo "<li>Columna de fecha detectada: {$date_column}</li>";
    echo "<li>Columna de método de pago detectada: {$payment_method_column}</li>";
    echo "<li>Columna de usuario detectada: {$found_user_column}</li>";
    echo "</ul>";
    
    // Query para pagos en efectivo
    $query = "SELECT * FROM {$payments_table} WHERE DATE({$date_column}) = %s";
    $args = [$date];
    
    // Filtro de método de pago (efectivo)
    if ($payment_method_column) {
        $cash_values = ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'];
        $placeholders = implode(',', array_fill(0, count($cash_values), '%s'));
        $query .= " AND {$payment_method_column} IN ({$placeholders})";
        $args = array_merge($args, $cash_values);
    }
    
    // Filtro de usuario si se especificó
    if ($user_id > 0 && $found_user_column) {
        $query .= " AND {$found_user_column} = %d";
        $args[] = $user_id;
    }
    
    // Ejecutar consulta
    $payments = $wpdb->get_results($wpdb->prepare($query, $args));
    
    if (empty($payments)) {
        echo "<p>No se encontraron pagos en efectivo para la fecha y usuario seleccionados.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach ($column_names as $col) {
            echo "<th>{$col}</th>";
        }
        echo "</tr>";
        
        $total_amount = 0;
        
        foreach ($payments as $payment) {
            echo "<tr>";
            foreach ($column_names as $col) {
                echo "<td>{$payment->$col}</td>";
            }
            echo "</tr>";
            
            // Sumar al total si existe columna de monto
            if (isset($payment->amount)) {
                $total_amount += (float) $payment->amount;
            } elseif (isset($payment->total)) {
                $total_amount += (float) $payment->total;
            }
        }
        
        echo "</table>";
        echo "<p><strong>Total pagos en efectivo: $" . number_format($total_amount, 2) . "</strong></p>";
    }
}

// Mostrar también las ventas de TODOS los usuarios en la fecha para comparar
if ($user_id > 0) {
    echo "<h2>Comparación: Ventas en efectivo de TODOS los usuarios para la fecha {$date}:</h2>";
    
    if ($sales_table_exists && $payment_method_column) {
        // Query para todas las ventas en efectivo (sin filtro de usuario)
        $query = "SELECT * FROM {$pos_sales_table} WHERE DATE({$date_column}) = %s";
        $args = [$date];
        
        // Filtro de método de pago (efectivo)
        $cash_values = ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'];
        $placeholders = implode(',', array_fill(0, count($cash_values), '%s'));
        $query .= " AND {$payment_method_column} IN ({$placeholders})";
        $args = array_merge($args, $cash_values);
        
        // Ejecutar consulta
        $all_sales = $wpdb->get_results($wpdb->prepare($query, $args));
        
        if (empty($all_sales)) {
            echo "<p>No se encontraron ventas en efectivo para la fecha seleccionada (todos los usuarios).</p>";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr>";
            foreach ($column_names as $col) {
                echo "<th>{$col}</th>";
            }
            echo "</tr>";
            
            $total_amount = 0;
            
            foreach ($all_sales as $sale) {
                echo "<tr>";
                foreach ($column_names as $col) {
                    // Destacar si el usuario_id no coincide con el seleccionado
                    if ($found_user_column && $col == $found_user_column && $sale->$col != $user_id) {
                        echo "<td style='background-color:#ffcccc;'><strong>{$sale->$col}</strong> (No es Ileana)</td>";
                    } else {
                        echo "<td>{$sale->$col}</td>";
                    }
                }
                echo "</tr>";
                
                // Sumar al total si existe columna de total
                if (isset($sale->total)) {
                    $total_amount += (float) $sale->total;
                } elseif (isset($sale->amount)) {
                    $total_amount += (float) $sale->amount;
                }
            }
            
            echo "</table>";
            echo "<p><strong>Total ventas en efectivo (todos los usuarios): $" . number_format($total_amount, 2) . "</strong></p>";
        }
    }
}

// Mostrar un enlace para volver
echo "<p><a href='javascript:history.back()'>« Volver</a></p>";
