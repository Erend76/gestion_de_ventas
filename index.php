<?php
session_start();
// Si el usuario ya está logueado, redirigir al panel de control inmediatamente
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/db.php'; // Conexión a la base de datos

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // 1. Buscar el usuario por email
    $stmt = $db->prepare("SELECT id, nombre, password FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. Verificar la contraseña hasheada
        if (password_verify($pass, $user['password'])) {
            
            // 3. Inicio de sesión exitoso: Guarda datos en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];

            // 4. Redirige al panel de control (dashboard.php)
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = "Contraseña incorrecta.";
        }
    } else {
        $error_message = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión - Gestor de Gastos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header_index">
        <h1><b>Farmacia</b> El imperio de la pastilla </h1>
    </header>

    <div class="container">
        <h2>Iniciar Sesión</h2>
        
        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <form action="index.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required><br><br>
            
            <button type="submit">Entrar</button>
        </form>
        <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>
</body>
</html>