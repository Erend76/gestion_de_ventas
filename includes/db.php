<?php
// Configuración de la Base de Datos
$host = 'localhost';
$dbname = 'gastos_db'; // Asegúrate de que este sea el nombre correcto
$user = 'root';        // Usuario por defecto de XAMPP/WAMP
$pass = '';            // Contraseña por defecto de XAMPP/WAMP (normalmente vacía)

try {
    // Cadena de conexión DSN para MariaDB/MySQL
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    $db = new PDO($dsn, $user, $pass);
    
    // Configura PDO para lanzar excepciones en caso de error (muy importante)
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configura PDO para devolver resultados como arrays asociativos por defecto
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la conexión falla, muestra un mensaje de error detallado y detiene la ejecución
    die("Error de conexión a la base de datos: " . $e->getMessage() . 
        "<br>Verifica que XAMPP/WAMP esté funcionando y que el nombre de la base de datos ('$dbname') sea correcto.");
}
?>
