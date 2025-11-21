<?php
session_start();
require_once 'includes/db.php'; // Asegúrate de que esta ruta sea correcta

// 1. Restricción de Acceso: Si no hay sesión, redirige al login.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$gastos = [];
$total_gastado = 0;
$mensaje_error = '';
$mensaje_success = '';

// Limpiar y capturar mensajes de sesión (para mostrar éxito o error después de una acción)
if (isset($_SESSION['error_message'])) {
    $mensaje_error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    $mensaje_success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// 2. Lógica de Filtrado (CORREGIDA PARA MARIADB/MYSQL)
$filter = $_GET['filter'] ?? 'all';
$sql_filter = '';
$filter_name = 'Todos los Gastos';
$params = [$user_id]; // Parámetros para la ejecución del SQL

switch ($filter) {
    case 'today':
        // Filtro para el día actual (MySQL: CURDATE())
        $sql_filter = " AND g.fecha = CURDATE()"; 
        $filter_name = 'Gastos de Hoy';
        break;
    case 'week':
        // Filtro para la última semana (últimos 7 días) (MySQL: DATE_SUB)
        $sql_filter = " AND g.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $filter_name = 'Gastos de la Última Semana';
        break;
    case 'month':
        // Filtro para el mes actual (MySQL: YEAR() y MONTH())
        $sql_filter = " AND YEAR(g.fecha) = YEAR(CURDATE()) AND MONTH(g.fecha) = MONTH(CURDATE())";
        $filter_name = 'Gastos del Mes Actual';
        break;
    case 'year':
        // Filtro para el año actual (MySQL: YEAR())
        $sql_filter = " AND YEAR(g.fecha) = YEAR(CURDATE())";
        $filter_name = 'Gastos del Año Actual';
        break;
    case 'all':
    default:
        $sql_filter = '';
        $filter_name = 'Todos los Gastos';
        break;
}

// 3. Obtener los gastos del usuario logueado con el filtro aplicado
try {
    // La columna 'pagado' debe ser un TINYINT o BOOLEAN en MySQL
    $stmt = $db->prepare("
        SELECT g.id, g.fecha, g.monto, g.pagado, g.forma_pago, g.descripcion, c.nombre AS categoria_nombre
        FROM gastos g
        JOIN categorias c ON g.categoria_id = c.id
        WHERE g.usuario_id = ? 
        " . $sql_filter . "
        ORDER BY g.fecha DESC, g.id DESC
    ");
    $stmt->execute($params);
    $gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Calcular el total gastado (solo del filtro actual)
    $monto_columna = array_column($gastos, 'monto');
    $total_gastado = array_sum($monto_columna);

} catch (PDOException $e) {
    // Si hay un error de base de datos
    $mensaje_error = "Error al cargar los gastos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestor de Gastos</title>
    <!-- Asumiendo que tienes un archivo CSS para los estilos -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos básicos para la app */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            color: #333;
        }
        .header_index {
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .dashboard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .dashboard-actions h1 {
            font-size: 1.8em;
            margin: 0;
            color: #007bff;
        }
        nav a {
            margin-left: 10px;
        }
        .btn {
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            margin-bottom: 5px;
        }
        .btn-primary {
            background-color: #28a745;
            color: white;
        }
        .btn-primary:hover {
            background-color: #218838;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        /* Estilos de Mensajes */
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        /* Tabla de Gastos */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-pagado {
            color: #28a745;
            font-weight: bold;
        }
        .status-pendiente {
            color: #dc3545;
            font-weight: bold;
        }
        .action-links a {
            margin-right: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .delete-link {
            color: #dc3545 !important;
        }

        /* Estilos para Resumen y Filtros */
        .dashboard-summary {
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
        }
        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .btn-filter {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ccc;
        }
        .btn-filter:hover {
            background-color: #e2e6ea;
            border-color: #adadad;
        }
        .btn-active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.3);
        }
        
        /* Media Queries para Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 10px;
            }
            .dashboard-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            .dashboard-actions nav {
                margin-top: 10px;
            }
            .filter-buttons {
                justify-content: space-between;
                gap: 5px;
            }
            .btn {
                flex: 1 1 auto; /* Permite que los botones se expandan */
                font-size: 0.85em;
                padding: 8px 10px;
            }
            th, td {
                padding: 8px 10px;
                font-size: 0.9em;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
                border-radius: 5px;
                overflow: hidden;
            }
            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td:before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                text-align: left;
                color: #007bff;
            }
            /* Ajuste para evitar que la acción se meta en el pseudo-elemento */
            td:last-child {
                text-align: center;
                padding-left: 10px;
            }
        }
    </style>
</head>
<body>
    <header class="header_index">
        <h1><b>Farmacia</b> El Imperio de la Pastilla </h1>
    </header>

    <div class="container">
        <div class="dashboard-actions">
            <h1>Panel de Control de <?php echo htmlspecialchars($user_name); ?></h1>
            <nav>
                <!-- Asegúrate de que esta ruta es correcta -->
                <a href="gastos_crear.php" class="btn btn-primary">Registrar Gasto</a>
                <a href="logout.php" class="btn btn-secondary">Cerrar Sesión</a>
            </nav>
        </div>

        <hr>

        <?php if ($mensaje_success): ?>
            <p class="message-success"><?php echo htmlspecialchars($mensaje_success); ?></p>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <p class="message-error"><?php echo htmlspecialchars($mensaje_error); ?></p>
        <?php endif; ?>

        <!-- Sección de Filtros -->
        <div class="filter-buttons">
            <a href="dashboard.php?filter=all" class="btn <?php echo $filter == 'all' ? 'btn-active' : 'btn-filter'; ?>">Ver Todos</a>
            <a href="dashboard.php?filter=today" class="btn <?php echo $filter == 'today' ? 'btn-active' : 'btn-filter'; ?>">Gastos de Hoy</a>
            <a href="dashboard.php?filter=week" class="btn <?php echo $filter == 'week' ? 'btn-active' : 'btn-filter'; ?>">Gastos Semana</a>
            <a href="dashboard.php?filter=month" class="btn <?php echo $filter == 'month' ? 'btn-active' : 'btn-filter'; ?>">Gastos Mes</a>
            <a href="dashboard.php?filter=year" class="btn <?php echo $filter == 'year' ? 'btn-active' : 'btn-filter'; ?>">Gastos Año</a>
        </div>
        
        <div class="dashboard-summary">
            <p>TOTAL GASTADO EN EL PERIODO (<?php echo htmlspecialchars($filter_name); ?>): $<?php echo number_format($total_gastado, 2); ?></p>
        </div>

        <h2><?php echo htmlspecialchars($filter_name); ?></h2>

        <?php if (count($gastos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Categoría</th>
                        <th>Monto (USD)</th>
                        <th>Forma Pago</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gastos as $gasto): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($gasto['id']); ?></td>
                            <td data-label="Fecha"><?php echo htmlspecialchars($gasto['fecha']); ?></td>
                            <td data-label="Categoría"><?php echo htmlspecialchars($gasto['categoria_nombre']); ?></td>
                            <td data-label="Monto (USD)">$<?php echo number_format($gasto['monto'], 2); ?></td>
                            <td data-label="Forma Pago"><?php echo htmlspecialchars($gasto['forma_pago']); ?></td>
                            <td data-label="Estado">
                                <?php if ($gasto['pagado']): ?>
                                    <span class="status-pagado">Pagado</span>
                                <?php else: ?>
                                    <span class="status-pendiente">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Descripción"><?php echo htmlspecialchars(substr($gasto['descripcion'], 0, 50)) . (strlen($gasto['descripcion']) > 50 ? '...' : ''); ?></td>
                            <td data-label="Acciones" class="action-links">
                                <!-- Asegúrate de que estas rutas son correctas -->
                                <a href="gastos_editar.php?id=<?php echo htmlspecialchars($gasto['id']); ?>">Editar</a>
                                <a href="gastos_eliminar.php?id=<?php echo htmlspecialchars($gasto['id']); ?>" 
                                   class="delete-link"
                                   onclick="return confirm('¿Está seguro de que desea eliminar este gasto?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; margin-top: 30px;">No hay gastos registrados para el periodo: **<?php echo htmlspecialchars($filter_name); ?>**.</p>
        <?php endif; ?>
    </div>
</body>
</html>