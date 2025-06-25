-- ==============================================
-- SCRIPT PARA CREAR USUARIO DE PRUEBA
-- ==============================================
-- Ejecuta este script en tu base de datos para crear un usuario de prueba
-- Usuario: admin
-- Contraseña: admin123

INSERT INTO usuario (
    usuario_nombre, 
    usuario_apellido, 
    usuario_usuario, 
    usuario_clave, 
    usuario_email
) VALUES (
    'Administrador',
    'Sistema',
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'admin@sistema.com'
);

-- Para crear otros usuarios, puedes usar este comando PHP para generar el hash:
-- <?php echo password_hash('tu_contraseña', PASSWORD_DEFAULT); ?> 