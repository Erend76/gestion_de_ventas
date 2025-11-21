<?php
session_start();
require_once 'includes/db.php';

// 1. Restricción de Acceso
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$gasto = null;
$categorias = [];
$error_message = "";
$gasto_id = null;

// 2. Obtener Categorías para el formulario SELECT
try {
    $stmt_cat = $db->prepare("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $stmt_cat->execute();
    $categorias = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar categorías.");
}

// --- LÓGICA DE PROCESAMIENTO DEL FORMULARIO (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $gasto_id = $_POST['gasto_id'] ?? null;
    $fecha = $_POST['fecha'];
    $categoria_id = $_POST['categoria_id'];
    $monto = $_POST['monto'];
    $pagado = isset($_POST['pagado']) ? 1 : 0; 
    $forma_pago = $_POST['forma_pago'];
    $descripcion = $_POST['descripcion'];

    // Validación básica
    if (empty($gasto_id) || empty($fecha) || empty($categoria_id) || empty($monto) || empty($forma_pago)) {
        $error_message = "Error: Faltan datos obligatorios o el ID del gasto es inválido.";
    } else {
        try {
            // Consulta UPDATE (segura: por ID de gasto Y usuario)
            $stmt_update = $db->prepare("
                UPDATE gastos SET fecha=?, categoria_id=?, monto=?, pagado=?, forma_pago=?, descripcion=? 
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt_update->execute([
                $fecha, $categoria_id, $monto, $pagado, $forma_pago, $descripcion,
                $gasto_id, $user_id
            ]);
            
            $_SESSION['success_message'] = "Gasto actualizado con éxito.";
            header('Location: dashboard.php');
            exit();

        } catch (PDOException $e) {
            $error_message = "Error al actualizar el gasto: " . $e->getMessage();
        }
    }
} 
// --- LÓGICA DE CARGA DE DATOS EXISTENTES (GET) ---
else if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $gasto_id = $_GET['id'];
    
    try {
        // Consultar el gasto, restringido por ID y usuario_id
        $stmt = $db->prepare("
            SELECT id, fecha, categoria_id, monto, pagado, forma_pago, descripcion 
            FROM gastos 
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([$gasto_id, $user_id]);
        $gasto = $stmt->fetch();

        // Si el gasto no existe o no pertenece al usuario, redirigir
        if (!$gasto) {
            $_SESSION['error_message'] = "Gasto no encontrado o acceso denegado.";
            header('Location: dashboard.php');
            exit();
        }
    } catch (PDOException $e) {
        die("Error al cargar el gasto para edición: " . $e->getMessage());
    }
} else {
    // Si no se pasó un ID, redirigir
    header('Location: dashboard.php');
    exit();
}

// Si la lógica POST falló, recargamos $gasto con los datos POST para no perder la información del usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $error_message) {
    $gasto = [
        'id' => $gasto_id,
        'fecha' => $_POST['fecha'],
        'categoria_id' => $_POST['categoria_id'],
        'monto' => $_POST['monto'],
        'pagado' => isset($_POST['pagado']) ? 1 : 0,
        'forma_pago' => $_POST['forma_pago'],
        'descripcion' => $_POST['descripcion'],
    ];
}
// Si el script llegó hasta aquí, $gasto debe tener datos
if (!$gasto) {
    $_SESSION['error_message'] = "Error inesperado al cargar los datos del gasto.";
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Gasto</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header_index">
        <h1><b>Farmacia</b> El Imperio de la Pastilla</h1>
    </header>
    <div class="container">
        <h1>Editar Gasto (ID: <?php echo htmlspecialchars($gasto['id']); ?>)</h1>
        <nav>
            <a href="dashboard.php">Volver al Panel</a>
        </nav>

        <hr>

        <?php if ($error_message): ?>
            <p class="message-error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form action="gastos_editar.php" method="POST">
            <input type="hidden" name="gasto_id" value="<?php echo htmlspecialchars($gasto['id']); ?>">

            <label for="fecha">Fecha del Gasto:</label>
            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($gasto['fecha']); ?>" required>

            <label for="categoria_id">Categoría:</label>
            <select id="categoria_id" name="categoria_id" required>
                <option value="">-- Seleccione una categoría --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['id']); ?>" 
                        <?php if ($cat['id'] == $gasto['categoria_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="monto">Monto (USD):</label>
            <input type="number" id="monto" name="monto" step="0.01" value="<?php echo htmlspecialchars($gasto['monto']); ?>" required>

            <label style="display: inline-flex; align-items: center;">
                <input type="checkbox" name="pagado" style="width: auto; margin-right: 5px;" <?php if ($gasto['pagado']) echo 'checked'; ?>> Pagado
            </label><br>
            
            <label for="forma_pago">Forma de Pago:</label>
            <select id="forma_pago" name="forma_pago" required>
                <?php $formas = ['Tarjeta de Credito', 'Efectivo', 'Transferencia', 'Cheque']; ?>
                <?php foreach ($formas as $forma): ?>
                    <option value="<?php echo $forma; ?>" <?php if ($forma == $gasto['forma_pago']) echo 'selected'; ?>>
                        <?php echo $forma; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="descripcion">Descripción (opcional):</label>
            <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($gasto['descripcion']); ?></textarea>

            <button type="submit">Guardar Cambios</button>
        </form>
    </div>
</body>
</html>