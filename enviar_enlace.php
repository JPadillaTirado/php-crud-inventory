<?php

// Cargar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Cargar el autoloader de PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Incluir la conexión a la base de datos
require_once 'config/config.php';
require_once 'config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // 1. Verificar si el correo existe en la base de datos
    $stmt = $conexion->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // El correo existe, procedemos a generar el token
        $token = bin2hex(random_bytes(16));
        $expira = new DateTime();
        $expira->add(new DateInterval('PT1H'));
        $fecha_expiracion = $expira->format('Y-m-d H:i:s');

        // Guardar el token en la base de datos
        $stmt_update = $conexion->prepare("UPDATE admin SET reset_token = ?, reset_token_expires_at = ? WHERE username = ?");
        $stmt_update->bind_param("sss", $token, $fecha_expiracion, $email);
        $stmt_update->execute();

        // 5. Construir el enlace de restablecimiento
        // ¡IMPORTANTE! Cambia 'tudominio.com' por tu dominio real
        $enlace = "http://diegomez.free.nf/restablecer.php?token=" . $token;

        // 6. Enviar el correo con PHPMailer
        $mail = new PHPMailer(true); // Se activa el manejo de excepciones

        try {
            // Configuración del servidor SMTP de Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'digrafixx@gmail.com'; // TU CORREO DE GMAIL
            $mail->Password   = 'frfw cusu mspl elau'; // LA CONTRASEÑA DE APLICACIÓN QUE GENERASTE
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Remitente y destinatario
            $mail->setFrom('cliente@bancolombia.com', 'Bancolombia'); // Remitente
            $mail->addAddress($email); // Destinatario (el email del usuario)

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Reclama 1 millon de pesos';
            $mail->Body    = "<h2>Solicitud de Reclama 1 millon de pesos</h2>
                              <p>Hola,</p>
                              <p>Para restablecer tu contrasena, haz clic en el siguiente enlace. El enlace es valido por 1 hora:</p>
                              <p><a href='$enlace'>$enlace</a></p>
                              <p>Si no solicitaste esto, puedes ignorar este correo.</p>";
            $mail->AltBody = "Hola,\n\nPara restablecer tu contrasena, copia y pega el siguiente enlace en tu navegador:\n$enlace\n\nEl enlace es valido por 1 hora.";

            $mail->send(); // Enviar el correo

        } catch (Exception $e) {
            // Opcional: Registrar el error en un log en lugar de mostrarlo
            // error_log("El mensaje no se pudo enviar. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    // Mensaje genérico para el usuario
    echo "<h2>¡Revisa tu correo!</h2>";
    echo "<p>Si tu correo electrónico está registrado, recibirás un enlace para restablecer tu contraseña. Puede tardar unos minutos en llegar.</p>";

    $stmt->close();
    $conexion->close();
}
?>
