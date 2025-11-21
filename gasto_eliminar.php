<?php
session_start();
require_once 'includes/db.php';

// 1. Restricción de Acceso
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$mensaje = "Error: Solicitud inválida.";

// 2. Verificar que se recibió el ID del gasto
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $gasto_id = $_GET['id'];
    
    try {
        // 3. Consulta DELETE
        // Es CRÍTICO incluir el usuario_id en la condición WHERE
        // Esto evita que un usuario borre gastos de otro usuario (seguridad).
        $stmt = $db->prepare("DELETE FROM gastos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$gasto_id, $user_id]);
        
        // Verificar si se eliminó alguna fila
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Gasto eliminado con éxito.";
        } else {
            $_SESSION['error_message'] = "Error: El gasto no existe o no tienes permiso para eliminarlo.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error de base de datos al intentar eliminar.";
    }
}

// 4. Redirigir siempre al dashboard
header('Location: dashboard.php');
exit();
?>