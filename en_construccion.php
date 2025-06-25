<?php
// Incluir archivo de configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Obtener el módulo solicitado desde la URL
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'Página';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En Construcción - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #2c3e50;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .construction-container {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .construction-icon {
            font-size: 120px;
            margin-bottom: 30px;
            animation: swing 2s ease-in-out infinite;
        }

        @keyframes swing {
            0%, 100% {
                transform: rotate(-5deg);
            }
            50% {
                transform: rotate(5deg);
            }
        }

        h1 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .module-name {
            color: #2980b9;
            font-weight: 700;
        }

        p {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 30px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2980b9 0%, #3498db 100%);
            width: 65%;
            animation: progress 2s ease-out;
        }

        @keyframes progress {
            from {
                width: 0%;
            }
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .feature-item {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .feature-text {
            font-size: 14px;
            color: #6c757d;
        }

        .back-button {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background-color: #2980b9;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #1a5490;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }

        .contact-info {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }

        .contact-info p {
            font-size: 14px;
            color: #6c757d;
        }

        .contact-email {
            color: #2980b9;
            text-decoration: none;
        }

        .contact-email:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 28px;
            }
            
            .construction-icon {
                font-size: 80px;
            }
            
            p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="construction-container">
        <div class="construction-icon">🚧</div>
        
        <h1>Módulo <span class="module-name"><?php echo htmlspecialchars($modulo); ?></span> en Construcción</h1>
        
        <p>Estamos trabajando arduamente para traerte esta funcionalidad.</p>
        <p>¡Pronto estará disponible!</p>
        
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">⚡</div>
                <div class="feature-text">Rendimiento Optimizado</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🔒</div>
                <div class="feature-text">Seguridad Mejorada</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">📱</div>
                <div class="feature-text">Diseño Responsive</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🎨</div>
                <div class="feature-text">Interfaz Moderna</div>
            </div>
        </div>
        
        <a href="dashboard.php" class="back-button">Volver al Dashboard</a>
        
        <div class="contact-info">
            <p>¿Necesitas esta funcionalidad urgentemente?</p>
            <p>Contáctanos: <a href="mailto:<?php echo APP_EMAIL; ?>" class="contact-email"><?php echo APP_EMAIL; ?></a></p>
        </div>
    </div>

    <script>
        // Actualizar el nombre del módulo basado en el referrer
        document.addEventListener('DOMContentLoaded', function() {
            const referrer = document.referrer;
            const moduleNames = {
                'configuraciones': '⚙️ Configuraciones',
                'proveedores': '🏢 Proveedores',
                'pedidos': '📋 Pedidos',
                'informes': '📊 Informes'
            };
            
            // Intentar detectar el módulo desde la URL anterior
            for (const [key, value] of Object.entries(moduleNames)) {
                if (referrer.includes(key) || window.location.href.includes(key)) {
                    document.querySelector('.module-name').textContent = value;
                    break;
                }
            }
        });
    </script>
</body>
</html>