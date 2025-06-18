<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

// Incluir archivos de configuración
require_once 'config/config.php';
require_once 'config/conexion.php';

$error = '';
$exito = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';
    
    if (empty($usuario) || empty($clave)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        // Consultar usuario en la base de datos
        $stmt = $conexion->prepare("SELECT usuario_id, usuario_nombre, usuario_apellido, usuario_usuario, usuario_clave, usuario_email FROM usuario WHERE usuario_usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 1) {
            $usuario_data = $resultado->fetch_assoc();
            
            // Verificar la contraseña
            if (password_verify($clave, $usuario_data['usuario_clave'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario_data['usuario_id'];
                $_SESSION['usuario_nombre'] = $usuario_data['usuario_nombre'];
                $_SESSION['usuario_apellido'] = $usuario_data['usuario_apellido'];
                $_SESSION['usuario_usuario'] = $usuario_data['usuario_usuario'];
                $_SESSION['usuario_email'] = $usuario_data['usuario_email'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventario</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .login-form .grupo-formulario {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .login-form .grupo-formulario label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }
        
        .login-form .grupo-formulario input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .login-form .grupo-formulario input:focus {
            border-color: #667eea;
            background-color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .login-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }
        
        .login-footer p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .mensaje.error {
            background-color: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .mensaje.exito {
            background-color: #f0fff4;
            color: #2f855a;
            border: 1px solid #c6f6d5;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Sistema de Inventario</h1>
            <p>Inicia sesión para continuar</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="mensaje error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($exito)): ?>
            <div class="mensaje exito">
                <?php echo htmlspecialchars($exito); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="">
            <div class="grupo-formulario">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>" required>
            </div>
            
            <div class="grupo-formulario">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required>
            </div>
            
            <button type="submit" class="login-btn">
                Iniciar Sesión
            </button>
        </form>
        
        <div class="login-footer">
            <p>¿No tienes una cuenta? <a href="views/usuarios/usuario_nuevo.php">Regístrate aquí</a></p>
        </div>
    </div>
</body>
</html>