<?php
// Inicia la sesión de PHP (necesaria para el futuro)
session_start();
require_once 'includes/db.php'; // Incluye la conexión a la base de datos

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // 1. Hashear la Contraseña por seguridad (¡CRÍTICO!)
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    try {
        // 2. Prepara la consulta INSERT
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
        
        // 3. Ejecuta la consulta
        $stmt->execute([$nombre, $email, $hashed_password]);
        
        $mensaje = "¡Registro exitoso! Ya puedes iniciar sesión.";
        
    } catch (PDOException $e) {
        $mensaje = "Error al registrar: El email ya existe o hubo un problema con la DB.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Empleados</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header_index">
        <h1><b>Farmacia</b> El imperio de la pastilla </h1>
    </header>
    <h1>Registro de Empleados</h1>
    <p style="color: blue;"><?php echo $mensaje; ?></p>
    
    <form action="registro.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required><br><br>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>
        
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required><br><br>
        
        <button type="submit">Registrar</button>
    </form>
    <p>¿Ya tienes cuenta? <a href="index.php">Inicia Sesión</a></p>
</body>
</html>