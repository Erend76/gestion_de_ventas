<?php
session_start();
// IMPORTANTE: Asegúrate de que esta ruta sea correcta para tu archivo db.php
require_once 'includes/db.php'; 

// Restricción de Acceso
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$mensaje_error = '';
$mensaje_success = '';
$categorias = [];

// 1. Obtener la lista de categorías
try {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si falla la conexión o la consulta, asignamos el error
    $mensaje_error = "Error al cargar categorías: " . $e->getMessage();
}

// 2. Procesar el formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && count($categorias) > 0) {
    // Sanitizar y validar datos
    $fecha = trim($_POST['fecha'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $monto = filter_var($_POST['monto'] ?? 0, FILTER_VALIDATE_FLOAT);
    $forma_pago = trim($_POST['forma_pago'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $pagado = isset($_POST['pagado']) ? 1 : 0; // 1 si está marcado, 0 si no

    if (!$fecha || $categoria_id <= 0 || $monto === false || $monto <= 0 || !$forma_pago) {
        $mensaje_error = 'Por favor, completa todos los campos requeridos con valores válidos.';
    } else {
        try {
            // Consulta de inserción (MariaDB/MySQL)
            $stmt = $db->prepare("
                INSERT INTO gastos (usuario_id, fecha, categoria_id, monto, forma_pago, descripcion, pagado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id, 
                $fecha, 
                $categoria_id, 
                $monto, 
                $forma_pago, 
                $descripcion, 
                $pagado
            ]);

            // Redirigir con mensaje de éxito
            $_SESSION['success_message'] = "¡Gasto registrado con éxito!";
            header('Location: dashboard.php');
            exit();

        } catch (PDOException $e) {
            $mensaje_error = "Error al guardar el gasto: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Gasto</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
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
            max-width: 600px;
            margin: 20px auto;
            padding: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        h2 {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Incluir padding y borde en el ancho total */
        }
        .form-group textarea {
            resize: vertical;
            height: 100px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn {
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            border: none;
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
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header class="header_index">
        <h1>Farmacia El Imperio de la Pastilla</h1>
    </header>

    <div class="container">
        <h2>Registrar Nuevo Gasto</h2>

        <?php if ($mensaje_error): ?>
            <!-- Si hay un error, lo mostramos claramente -->
            <p class="message-error"><?php echo htmlspecialchars($mensaje_error); ?></p>
        <?php endif; ?>

        <form method="POST" action="gasto_crear.php">
            
            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="categoria_id">Categoría:</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">-- Seleccione una Categoría --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['id']); ?>">
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (count($categorias) === 0): ?>
                    <p style="color: red;">No hay categorías. Crea una en tu base de datos primero.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="monto">Monto (USD):</label>
                <input type="number" id="monto" name="monto" step="0.01" min="0.01" required>
            </div>

            <div class="form-group">
                <label for="forma_pago">Forma de Pago:</label>
                <select id="forma_pago" name="forma_pago" required>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                    <option value="Transferencia">Transferencia Bancaria</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción (Opcional):</label>
                <textarea id="descripcion" name="descripcion"></textarea>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="pagado" name="pagado" value="1">
                <label for="pagado">Gasto Pagado</label>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Gasto</button>
            <!-- ESTE ES EL ENLACE CRÍTICO QUE DEBE FUNCIONAR -->
            <a href="dashboard.php" class="btn btn-secondary">Cancelar y Volver</a>
        </form>
    </div>
</body>
</html>