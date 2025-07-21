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
    $recordar = isset($_POST['recordar']);
    
    if (empty($usuario) || empty($clave)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        // Consultar usuario en la base de datos
        $stmt = $conexion->prepare("SELECT usuario_id, usuario_nombre, usuario_apellido, usuario_usuario, usuario_clave, usuario_email FROM usuario WHERE usuario_usuario = ? OR usuario_email = ?");
        $stmt->bind_param("ss", $usuario, $usuario);
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
                
                // Si marcó "recordar", crear cookie (opcional)
                if ($recordar) {
                    setcookie('recordar_usuario', $usuario, time() + (86400 * 30), "/"); // 30 días
                }
                
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

// Recuperar usuario recordado si existe
$usuario_recordado = $_COOKIE['recordar_usuario'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header con logo */
        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 30px;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 300px;
            height: auto;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Container principal */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 48px;
            width: 100%;
            max-width: 440px;
            border: 1px solid #e2e8f0;
        }
        
        .welcome-section {
            text-align: left;
            margin-bottom: 32px;
        }
        
        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .welcome-subtitle {
            font-size: 16px;
            color: #64748b;
            font-weight: 400;
        }
        
        /* Formulario */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 400;
            background-color: #ffffff;
            transition: all 0.2s ease;
            color: #1f2937;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input::placeholder {
            color: #9ca3af;
        }
        
        /* Checkbox y enlaces */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 8px 0;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #3b82f6;
            cursor: pointer;
        }
        
        .checkbox-label {
            font-size: 14px;
            color: #374151;
            font-weight: 400;
            cursor: pointer;
            user-select: none;
        }
        
        .forgot-link {
            font-size: 14px;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .forgot-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        
        /* Botón */
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }
        
        .login-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        /* Mensajes de error */
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            font-size: 14px;
            color: #6b7280;
        }
        
        .footer-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .footer-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .header {
                padding: 16px 20px;
            }
            
            .logo-icon {
                width: 32px;
            }
            
            .logo-text {
                font-size: 20px;
            }
            
            .login-card {
                padding: 32px 24px;
                border-radius: 16px;
                margin: 0 16px;
            }
            
            .welcome-title {
                font-size: 28px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
        
        /* Animaciones de entrada */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            animation: slideUp 0.6s ease-out;
        }
        
        .header {
            animation: slideUp 0.6s ease-out 0.2s both;
        }
    </style>
</head>
<body>

    <!-- Container principal -->
    <div class="login-container">
        <div class="login-card">
            <!-- Sección de bienvenida -->
            <div class="welcome-section">
                        <div class="logo">
            <svg class="logo-icon" viewBox="0 0 166 54" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_0_1)">
                <path d="M44.3081 15.7295C45.0177 15.9844 45.1978 16.5749 45.1966 17.3145C45.1853 24.0817 45.1785 30.8488 45.1763 37.616C45.1779 37.6449 45.1813 37.6737 45.1864 37.7022C45.1134 37.8469 45.1892 38.0147 45.1259 38.1608C44.9771 38.9566 44.7313 39.7317 44.3939 40.469C43.7742 41.7129 42.8042 42.7531 41.6005 43.4645C39.6551 44.6704 37.7188 45.8937 35.7793 47.1111C33.7466 48.387 31.7141 49.6633 29.6818 50.9401C28.3796 51.7545 27.1013 52.6148 25.7644 53.363C25.581 53.4029 25.3907 53.3995 25.2089 53.353C25.0117 53.2925 24.8366 53.1764 24.7051 53.0191C24.5736 52.8617 24.4913 52.6698 24.4683 52.4669C24.4162 51.9019 24.4488 51.3355 24.4473 50.77C24.4421 48.9248 24.4462 47.0796 24.4438 45.2345C24.4639 45.076 24.453 44.9153 24.4118 44.7609C24.4199 44.6021 24.4351 44.4434 24.4348 44.2846C24.4255 39.2119 24.4227 34.1391 24.3943 29.0665C24.3649 28.7807 24.4213 28.4927 24.5566 28.2386C24.6919 27.9844 24.9 27.7754 25.1549 27.6376C30.7995 24.0733 36.4228 20.4692 42.0497 16.8721C42.629 16.4278 43.2534 16.0442 43.9128 15.7275C44.0414 15.6866 44.1799 15.6873 44.3081 15.7295Z" fill="#FEB000"/>
                <path d="M3.53019 16.9757C3.73861 17.1607 3.9785 17.3078 4.23877 17.4101C4.37423 17.4296 4.50354 17.4789 4.61718 17.5544C4.73082 17.6298 4.82591 17.7295 4.89545 17.8461C4.93038 17.8948 4.9754 17.9355 5.0275 17.9656C5.07959 17.9958 5.13757 18.0146 5.19759 18.0208C5.58173 18.1254 5.92279 18.347 6.17231 18.6542C6.26745 18.7799 6.40744 18.8652 6.56383 18.8925C6.69946 18.9132 6.82882 18.9632 6.94271 19.0389C7.05659 19.1146 7.15221 19.2142 7.22275 19.3306C7.43106 19.5162 7.67131 19.6633 7.93212 19.765C8.06735 19.785 8.19638 19.8345 8.30981 19.9101C8.42325 19.9856 8.51822 20.0851 8.58784 20.2015C8.62269 20.2505 8.66786 20.2915 8.72026 20.3216C8.77265 20.3516 8.83102 20.3701 8.89133 20.3757C9.13014 20.4879 9.41332 20.5214 9.55035 20.8143C9.75876 20.9992 9.99859 21.1462 10.2588 21.2486C10.3943 21.2679 10.5238 21.3172 10.6375 21.3927C10.7512 21.4683 10.8462 21.5682 10.9155 21.6851C11.1808 21.9045 11.4744 22.0881 11.7885 22.2313C11.8749 22.2283 11.9601 22.2518 12.0324 22.2986C12.1047 22.3454 12.1606 22.4132 12.1925 22.4927C12.2875 22.6185 12.4276 22.7037 12.584 22.731C12.7196 22.7516 12.849 22.8016 12.9629 22.8773C13.0768 22.953 13.1724 23.0526 13.2429 23.169C13.451 23.3545 13.6909 23.5018 13.9514 23.6039C14.0868 23.6234 14.2161 23.6727 14.3298 23.7482C14.4434 23.8237 14.5385 23.9234 14.608 24.04C14.6431 24.0885 14.6881 24.1292 14.7402 24.1593C14.7923 24.1894 14.8502 24.2082 14.9102 24.2146C15.294 24.3196 15.6349 24.5408 15.8851 24.8472C15.9799 24.9738 16.1204 25.0593 16.2774 25.086C16.4127 25.1072 16.5417 25.1574 16.6554 25.233C16.7691 25.3086 16.8647 25.4079 16.9356 25.5239C17.2009 25.7432 17.4945 25.9268 17.8087 26.0697C17.8951 26.0667 17.9804 26.0902 18.0527 26.1371C18.1251 26.184 18.1809 26.2519 18.2126 26.3315C18.3079 26.457 18.4477 26.5421 18.6039 26.5696C18.7397 26.5899 18.8693 26.6398 18.9832 26.7157C19.0972 26.7915 19.1927 26.8914 19.2629 27.0081C19.4714 27.193 19.7113 27.34 19.9716 27.4424C20.1081 27.4606 20.2383 27.5102 20.3519 27.5873C20.4655 27.6643 20.5592 27.7666 20.6255 27.8859C20.9065 28.214 21.0498 28.6359 21.0261 29.0652C21.0262 31.0229 21.0383 32.9805 21.0461 34.9382C21.076 35.0653 21.0843 35.1965 21.0706 35.3263C21.0716 40.9405 21.0716 46.5547 21.0705 52.1689C21.0865 52.2984 21.0768 52.4298 21.0419 52.5557C20.8971 52.8971 20.638 53.1788 20.3081 53.3538C20.1255 53.4027 19.933 53.4028 19.7503 53.3541C19.2015 53.1348 18.6863 52.841 18.2193 52.4813C16.9321 51.717 15.6732 50.896 14.4057 50.0928C13.0926 49.2607 11.7863 48.416 10.4705 47.5891C8.84617 46.5682 7.20915 45.5711 5.58931 44.5419C4.66364 44.0043 3.76821 43.4172 2.90697 42.7835C2.03095 42.056 1.35263 41.1235 0.933638 40.0708C0.626908 39.3834 0.442498 38.6487 0.388534 37.8992C0.360066 37.7717 0.352191 37.6406 0.365203 37.5106C0.364284 30.676 0.364311 23.8414 0.365283 17.0068C0.351101 16.877 0.359675 16.7458 0.390626 16.6188C0.422588 16.4616 0.492357 16.3143 0.594038 16.1893C0.695719 16.0643 0.826323 15.9654 0.974815 15.9009C1.12331 15.8364 1.28532 15.8081 1.44717 15.8186C1.60902 15.829 1.76595 15.8779 1.9047 15.9609C2.26603 16.1688 2.61435 16.4034 2.96853 16.626C3.08597 16.626 3.20102 16.6588 3.30041 16.7206C3.39979 16.7825 3.47945 16.8709 3.53019 16.9757Z" fill="#FE005F"/>
                <path d="M17.1008 21.4255L16.3884 20.9384C16.0945 20.963 15.9551 20.6787 15.7362 20.5532L15.1909 20.1834C14.8739 20.0989 14.5891 19.9241 14.3717 19.6808L13.8247 19.3118C13.5593 19.2646 13.323 19.1169 13.166 18.9001L12.7385 18.6298C12.3767 18.5172 12.0527 18.3092 11.8014 18.0282C11.6283 17.9483 11.4696 17.8408 11.3316 17.7101C11.0374 17.7355 10.8981 17.4507 10.6793 17.3253L9.96663 16.8394C9.67331 16.8622 9.53356 16.5788 9.3147 16.4532L8.76805 16.0838C8.50252 16.037 8.26604 15.8893 8.10906 15.6724L7.23657 15.0982C7.13632 15.0915 7.03919 15.061 6.95348 15.0091C6.86778 14.9573 6.79605 14.8856 6.74443 14.8004C6.57133 14.7203 6.41264 14.6128 6.27466 14.482C5.98032 14.508 5.84114 14.2229 5.62224 14.0976L4.90986 13.6105C4.61595 13.635 4.47651 13.3508 4.25766 13.2252C4.06219 13.0897 3.85967 12.9643 3.65094 12.8496C3.4903 12.7527 3.35711 12.617 3.26376 12.4554C3.17041 12.2938 3.11995 12.1114 3.11707 11.9253C3.1142 11.7392 3.15901 11.5554 3.24733 11.391C3.33564 11.2267 3.4646 11.0871 3.62217 10.9853C5.22657 9.98426 6.83437 8.98962 8.43716 7.98553C10.6195 6.61837 12.8001 5.24785 14.9789 3.87397C16.5702 2.87336 18.1433 1.83641 19.7606 0.888295C20.4339 0.477842 21.2025 0.245137 21.9927 0.212448C22.2013 0.110786 22.4303 0.23017 22.6402 0.141825C22.6518 0.137534 22.6641 0.135618 22.6765 0.136193C22.6889 0.136768 22.701 0.139822 22.7121 0.145171C22.764 0.214765 22.8012 0.293976 22.8215 0.378084C22.8417 0.462192 22.8447 0.549475 22.8301 0.634732C22.8318 6.88171 22.8316 13.1287 22.8297 19.3757C22.8301 19.4479 22.8255 19.52 22.8159 19.5916C22.8104 19.6403 22.7931 19.687 22.7655 19.7277C22.7379 19.7683 22.7008 19.8019 22.6573 19.8254C21.2555 20.514 19.993 21.4714 18.6517 22.2768C18.6281 22.2902 18.6037 22.3021 18.5787 22.3125C18.4152 22.3541 18.3107 22.2602 18.2231 22.1283C18.0498 22.0484 17.8911 21.9408 17.7533 21.8099C17.4589 21.8359 17.3197 21.5508 17.1008 21.4255Z" fill="#0080FC"/>
                <path d="M22.8038 0.143385L23.3354 0.136536C23.4695 0.214087 23.6239 0.134697 23.7594 0.200986C25.403 0.486915 26.7211 1.54252 28.1174 2.40184C30.4654 3.84686 32.7953 5.32677 35.1315 6.79444C37.3758 8.20437 39.62 9.61444 41.8642 11.0246C42.057 11.1624 42.2115 11.346 42.3136 11.5584C42.3586 11.7579 42.3592 11.9647 42.3152 12.1644C42.1697 12.4643 41.9318 12.7109 41.6356 12.8687C36.8462 15.9212 32.0582 18.9764 27.2717 22.0343C27.146 22.1146 27.0204 22.1949 26.8949 22.2753C25.9122 21.7497 24.9977 21.094 24.0447 20.5116C23.7065 20.2831 23.3532 20.0773 22.9871 19.8958C22.8584 19.8392 22.7374 19.7784 22.7591 19.5891C22.7603 13.2767 22.7612 6.96504 22.7619 0.654281C22.7322 0.483215 22.7466 0.307488 22.8038 0.143385Z" fill="#006EC4"/>
                <path d="M22.791 19.7495C23.0872 19.7901 23.3656 19.913 23.5939 20.1039C24.6207 20.7442 25.6451 21.389 26.6671 22.0385C26.7522 22.1083 26.8287 22.1878 26.8949 22.2753C25.8688 22.9378 24.8428 23.6005 23.8169 24.2635C23.7157 24.329 23.6195 24.4037 23.521 24.4741C23.4656 24.5754 23.3849 24.6608 23.2865 24.7224C23.1882 24.784 23.0755 24.8196 22.9593 24.8258C22.6462 25.0261 22.3984 24.8085 22.1457 24.6652C21.9024 24.5271 21.6707 24.365 21.4339 24.2135C21.1897 24.2024 21.0625 23.9722 20.8725 23.8595L20.2406 23.4146C19.9755 23.3634 19.7412 23.2116 19.5878 22.9917L18.7137 22.4351C18.6704 22.4343 18.629 22.4173 18.5979 22.3875C18.5668 22.3577 18.5483 22.3173 18.5461 22.2745C19.0569 21.8736 19.5977 21.5114 20.1637 21.1911C20.7636 20.7941 21.3726 20.4133 21.9812 20.0322C22.1928 19.8648 22.4507 19.7646 22.721 19.7448C22.7463 19.7106 22.7696 19.7138 22.791 19.7495Z" fill="#00539A"/>
                </g>
                <g clip-path="url(#clip1_0_1)">
                <path d="M58.9409 32.0678V29.2466H61.4892V19.6437H58.9409V16.8225H67.2905V19.6437H64.7423V29.2466H67.2905V32.0678H58.9409ZM70.3809 32.0678V20.566H73.3087L73.4172 22.8989L72.8208 23.1702C72.9824 22.6325 73.2814 22.1463 73.6882 21.7596C74.1265 21.3253 74.6412 20.9759 75.2064 20.7288C75.7855 20.4682 76.4149 20.3386 77.0498 20.349C77.81 20.3147 78.5638 20.5032 79.2185 20.8915C79.8279 21.2415 80.2901 21.8004 80.5197 22.4649C80.8483 23.292 80.9962 24.18 80.9535 25.0691V32.1221H77.863V25.2318C77.8895 24.787 77.8153 24.3419 77.6462 23.9297C77.5052 23.6202 77.2796 23.3569 76.9955 23.1702C76.6683 22.9791 76.2867 22.9028 75.9112 22.9532C75.5792 22.9543 75.2496 23.0092 74.9353 23.1159C74.6649 23.2279 74.4099 23.3737 74.1762 23.55C73.966 23.7483 73.7998 23.9886 73.6882 24.2553C73.5772 24.531 73.5219 24.8261 73.5256 25.1233V32.0136H71.14C70.8892 32.055 70.6351 32.0732 70.3809 32.0678ZM87.5139 32.0678L82.6885 20.566H86.05L89.0862 29.1381L88.3814 29.2466L91.5802 20.566H94.8875L89.8452 32.0678H87.5139ZM101.936 32.2848C100.806 32.3036 99.6881 32.0427 98.6828 31.5253C97.7882 31.0451 97.0391 30.333 96.5141 29.4636C95.9788 28.5432 95.7156 27.4896 95.755 26.4254C95.7577 25.5927 95.9045 24.7667 96.1888 23.984C96.4557 23.2777 96.8613 22.6321 97.3816 22.0851C97.9006 21.543 98.529 21.1177 99.225 20.8373C99.9621 20.5271 100.757 20.3791 101.556 20.4032C102.301 20.4056 103.037 20.5531 103.725 20.8373C104.385 21.1 104.978 21.5079 105.46 22.0308C105.962 22.5405 106.35 23.1507 106.599 23.8212C106.873 24.5477 106.984 25.3257 106.924 26.0999V27.0765H97.7069L97.2189 25.1776H104.376L104.05 25.5573V25.0691C104.006 24.6836 103.877 24.3128 103.671 23.984C103.448 23.6635 103.15 23.4027 102.803 23.2244C102.431 23.0459 102.023 22.9532 101.611 22.9532C101.043 22.9239 100.48 23.0556 99.984 23.3329C99.553 23.5831 99.2115 23.9628 99.0081 24.418C98.7759 24.9665 98.6649 25.5587 98.6828 26.1541C98.6716 26.7783 98.821 27.3948 99.1165 27.9445C99.4189 28.4462 99.8493 28.8582 100.364 29.1381C100.971 29.4335 101.64 29.5822 102.315 29.5721C102.794 29.584 103.272 29.5105 103.725 29.3551C104.215 29.1536 104.672 28.8792 105.081 28.5413L106.544 30.603C106.12 30.9891 105.646 31.3175 105.135 31.5795C104.642 31.8404 104.111 32.0234 103.562 32.1221C102.966 32.1763 102.424 32.2848 101.936 32.2848ZM109.201 32.0678V20.566H112.129L112.237 22.8989L111.641 23.1702C111.803 22.6325 112.102 22.1463 112.508 21.7596C112.947 21.3253 113.461 20.9759 114.027 20.7288C114.606 20.4682 115.235 20.3386 115.87 20.349C116.63 20.3147 117.384 20.5032 118.039 20.8915C118.648 21.2415 119.11 21.8004 119.34 22.4649C119.669 23.292 119.816 24.18 119.774 25.0691V32.1221H116.683V25.2318C116.71 24.787 116.636 24.3419 116.466 23.9297C116.325 23.6202 116.1 23.3569 115.816 23.1702C115.488 22.9791 115.107 22.9028 114.731 22.9532C114.399 22.9543 114.07 23.0092 113.755 23.1159C113.485 23.2279 113.23 23.3737 112.996 23.55C112.786 23.7483 112.62 23.9886 112.508 24.2553C112.397 24.531 112.342 24.8261 112.346 25.1233V32.0136H109.96C109.709 32.0518 109.455 32.07 109.201 32.0678ZM123.786 32.0678V17.6363H126.876V32.0136H123.786V32.0678ZM121.617 23.3329V20.566H129.208V23.3329H121.617ZM136.419 32.2848C135.324 32.2975 134.243 32.0365 133.274 31.5253C132.388 31.0091 131.643 30.2823 131.105 29.4094C130.57 28.4692 130.307 27.3983 130.346 26.3169C130.333 25.2556 130.595 24.209 131.105 23.2787C131.613 22.3815 132.365 21.6476 133.274 21.1628C134.233 20.6268 135.321 20.3641 136.419 20.4032C137.496 20.397 138.557 20.658 139.509 21.1628C140.395 21.679 141.14 22.4057 141.678 23.2787C142.213 24.1991 142.476 25.2527 142.437 26.3169C142.443 27.3946 142.182 28.4571 141.678 29.4094C141.17 30.3065 140.418 31.0405 139.509 31.5253C138.562 32.0427 137.497 32.3045 136.419 32.2848ZM136.419 29.5721C136.955 29.57 137.48 29.4198 137.937 29.1381C138.389 28.8487 138.747 28.434 138.967 27.9445C139.232 27.4245 139.363 26.8462 139.346 26.2626C139.369 25.6785 139.238 25.0986 138.967 24.5808C138.729 24.1026 138.375 23.6921 137.937 23.3872C137.48 23.1055 136.955 22.9553 136.419 22.9532C135.881 22.9477 135.354 23.0985 134.9 23.3872C134.446 23.6893 134.073 24.0994 133.816 24.5808C133.545 25.0986 133.414 25.6785 133.437 26.2626C133.42 26.8462 133.551 27.4245 133.816 27.9445C134.072 28.4269 134.445 28.8374 134.9 29.1381C134.346 29.4438 135.879 29.5961 136.419 29.5721ZM144.822 32.0678V20.566H147.804L147.913 24.2553L147.371 23.4957C147.528 22.8924 147.825 22.3347 148.238 21.8681C148.61 21.4017 149.07 21.0143 149.594 20.7288C150.111 20.4524 150.688 20.3036 151.274 20.2947C151.511 20.2836 151.747 20.3018 151.979 20.349C152.196 20.4032 152.413 20.4575 152.576 20.5117L151.762 23.8755C151.561 23.7627 151.34 23.6891 151.112 23.6585C150.863 23.5958 150.609 23.5594 150.353 23.55C150.015 23.5437 149.68 23.6181 149.377 23.767C149.08 23.8778 148.819 24.065 148.618 24.3095C148.407 24.5398 148.225 24.7948 148.075 25.0691C147.969 25.3836 147.914 25.7135 147.913 26.0456V32.0136H144.822V32.0678ZM155.937 37.0592L158.431 31.1455L158.485 32.9359L152.901 20.566H156.371L159.19 27.185C159.299 27.4562 159.407 27.7275 159.516 28.053C159.623 28.355 159.714 28.663 159.787 28.9753L159.244 29.1924C159.338 28.8976 159.446 28.6079 159.57 28.3243C159.678 27.9988 159.787 27.6732 159.895 27.2935L162.281 20.5117H165.805L160.979 32.0136L159.028 37.0049H155.937V37.0592Z" fill="black"/>
                </g>
                <defs>
                <clipPath id="clip0_0_1">
                <rect width="44.8355" height="53.2544" fill="white" transform="translate(0.36084 0.136093)"/>
                </clipPath>
                <clipPath id="clip1_0_1">
                <rect width="106.864" height="20.2367" fill="white" transform="translate(58.9409 16.8225)"/>
                </clipPath>
                </defs>
            </svg>
        </div>
                <h1 class="welcome-title">
                    Bienvenido 
                    <span style="font-size: 32px;">👋</span>
                </h1>
                <p class="welcome-subtitle">Inicia sesión aquí</p>
            </div>

            <!-- Mensajes de error/éxito -->
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM8 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zM9 8c0 .6-.4 1-1 1s-1-.4-1-1V5c0-.6.4-1 1-1s1 .4 1 1v3z"/>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($exito)): ?>
                <div class="success-message">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM7 11L3 7l1.4-1.4L7 8.2l4.6-4.6L13 5l-6 6z"/>
                    </svg>
                    <?php echo htmlspecialchars($exito); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de login -->
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="usuario" class="form-label">Correo electrónico</label>
                    <input 
                        type="text" 
                        id="usuario" 
                        name="usuario" 
                        class="form-input"
                        placeholder="Tu Usuario o Correo Electrónico"
                        value="<?php echo htmlspecialchars($usuario_recordado); ?>" 
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="clave" class="form-label">Contraseña</label>
                    <input 
                        type="password" 
                        id="clave" 
                        name="clave" 
                        class="form-input"
                        placeholder="••••••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-options">
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="recordar" 
                            name="recordar" 
                            class="checkbox"
                            <?php echo $usuario_recordado ? 'checked' : ''; ?>
                        >
                        <label for="recordar" class="checkbox-label">Recuérdame</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="login-button">
                    Iniciar
                </button>
            </form>

            <!-- Footer del formulario -->
            <div class="login-footer">
                <p class="footer-text">
                    ¿No tienes una cuenta? 
                    <a href="views/usuarios/usuario_nuevo.php" class="footer-link">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Mejorar la experiencia del usuario
        document.addEventListener('DOMContentLoaded', function() {
            const usuarioInput = document.getElementById('usuario');
            const claveInput = document.getElementById('clave');
            const recordarCheckbox = document.getElementById('recordar');
            
            // Auto-focus en el primer campo
            if (usuarioInput && !usuarioInput.value) {
                usuarioInput.focus();
            } else if (claveInput) {
                claveInput.focus();
            }
            
            // Animación suave para los mensajes de error
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.style.opacity = '0';
                errorMessage.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    errorMessage.style.transition = 'all 0.3s ease';
                    errorMessage.style.opacity = '1';
                    errorMessage.style.transform = 'translateY(0)';
                }, 100);
            }
            
            // Efecto de escritura en tiempo real para el placeholder
            const placeholders = [
                'jorgepadilla@inventory.com',
                'usuario@correo.com',
                'admin@sistema.com'
            ];
            
            let currentPlaceholder = 0;
            
            // Cambiar placeholder ocasionalmente
            setInterval(() => {
                if (document.activeElement !== usuarioInput) {
                    currentPlaceholder = (currentPlaceholder + 1) % placeholders.length;
                    usuarioInput.placeholder = placeholders[currentPlaceholder];
                }
            }, 3000);
        });
        
        // Función para la demo del "¿Olvidaste tu contraseña?"
        function mostrarRecuperacion() {
            alert('Esta funcionalidad estará disponible próximamente.\n\nPor ahora, contacta al administrador del sistema para recuperar tu contraseña.');
        }
    </script>
</body>
</html>