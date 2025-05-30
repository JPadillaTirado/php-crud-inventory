CREATE TABLE categoria (
    categoria_id INT(7) NOT NULL AUTO_INCREMENT,
    categoria_nombre VARCHAR(50) NOT NULL COLLATE 'utf8mb3_general_ci',
    categoria_ubicacion VARCHAR(150) NOT NULL COLLATE 'utf8mb3_general_ci',
    PRIMARY KEY (categoria_id) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=8
;

CREATE TABLE producto (
    producto_id INT(20) NOT NULL AUTO_INCREMENT,
    producto_codigo VARCHAR(70) NOT NULL COLLATE 'utf8mb3_general_ci',
    producto_nombre VARCHAR(70) NOT NULL COLLATE 'utf8mb3_general_ci',
    producto_precio DECIMAL(30,2) NOT NULL,
    producto_stock INT(25) NOT NULL,
    producto_foto VARCHAR(500) NOT NULL COLLATE 'utf8mb3_general_ci',
    categoria_id INT(7) NOT NULL,
    usuario_id INT(10) NOT NULL,
    PRIMARY KEY (producto_id) USING BTREE,
    INDEX producto_codigo (producto_codigo) USING BTREE,
    INDEX fk_producto_categoria (categoria_id) USING BTREE,
    INDEX fk_producto_usuario (usuario_id) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=8
;

CREATE TABLE usuario (
    usuario_id INT(10) NOT NULL AUTO_INCREMENT,
    usuario_nombre VARCHAR(40) NOT NULL COLLATE 'utf8mb3_general_ci',
    usuario_apellido VARCHAR(40) NOT NULL COLLATE 'utf8mb3_general_ci',
    usuario_usuario VARCHAR(20) NOT NULL COLLATE 'utf8mb3_general_ci',
    usuario_clave VARCHAR(200) NOT NULL COLLATE 'utf8mb3_general_ci',
    usuario_email VARCHAR(70) NOT NULL COLLATE 'utf8mb3_general_ci',
    PRIMARY KEY (usuario_id) USING BTREE,
    INDEX usuario_usuario (usuario_usuario) USING BTREE,
    INDEX usuario_email (usuario_email) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=2
;