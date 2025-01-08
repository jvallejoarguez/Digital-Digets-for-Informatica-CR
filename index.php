<?php
session_start();

$conn = new mysqli("localhost:3306", "admin_digitickets", "admintickets123.", "digitaltickets");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Si el usuario ya está logueado, actualiza copias disponibles y usadas desde la base de datos
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT available_copies, used_copies, available_copies_color, used_copies_color FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($available_copies, $used_copies, $available_copies_color, $used_copies_color);
    $stmt->fetch();
    $stmt->close();

    // Actualiza los valores en la sesión para mantenerlos sincronizados
    $_SESSION['available_copies'] = $available_copies;
    $_SESSION['used_copies'] = $used_copies;
    $_SESSION['available_copies_color'] = $available_copies_color;
    $_SESSION['used_copies_color'] = $used_copies_color;
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $initialCopies = 10;

        // Verificar si el nombre de usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $_SESSION['message'] = "El usuario ya existe. Intente con otro nombre de usuario.";
            $_SESSION['show_initial'] = true;
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, password, available_copies, used_copies, available_copies_color, used_copies_color) VALUES (?, ?, ?, 0, ?, 0)");
            $stmt->bind_param("ssii", $username, $password, $initialCopies, $initialCopies);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Registro exitoso. Ahora puede iniciar sesión.";
                $_SESSION['show_login'] = true;
            } else {
                $_SESSION['message'] = "Error en el registro. Intente nuevamente.";
            }
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Procesar inicio de sesión
    elseif (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, password, available_copies, used_copies, available_copies_color, used_copies_color FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $hashed_password, $available_copies, $used_copies, $available_copies_color, $used_copies_color);
        $stmt->fetch();

        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['available_copies'] = $available_copies;
            $_SESSION['used_copies'] = $used_copies;
            $_SESSION['available_copies_color'] = $available_copies_color;
            $_SESSION['used_copies_color'] = $used_copies_color;
            $_SESSION['message'] = "Inicio de sesión exitoso.";
        } else {
            $_SESSION['message'] = "Nombre de usuario o contraseña incorrectos.";
            $_SESSION['show_initial'] = true;
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Procesar uso de copias
    elseif (isset($_POST['use_copies']) && isset($_SESSION['user_id'])) {
        $bwCopies = isset($_POST['bwCopies']) ? (int)$_POST['bwCopies'] : 0;
        $colorCopies = isset($_POST['colorCopies']) ? (int)$_POST['colorCopies'] : 0;

        // Validar que no se usen más copias de las disponibles
        if ($bwCopies > $_SESSION['available_copies']) {
            $_SESSION['message'] = "No tienes suficientes copias en blanco y negro disponibles.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        if ($colorCopies > $_SESSION['available_copies_color']) {
            $_SESSION['message'] = "No tienes suficientes copias a color disponibles.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        $_SESSION['available_copies'] -= $bwCopies;
        $_SESSION['used_copies'] += $bwCopies;
        $_SESSION['available_copies_color'] -= $colorCopies;
        $_SESSION['used_copies_color'] += $colorCopies;

        // Actualizar los datos del usuario
        $stmt = $conn->prepare("UPDATE users SET available_copies = ?, used_copies = ?, available_copies_color = ?, used_copies_color = ? WHERE id = ?");
        $stmt->bind_param("iiiii", $_SESSION['available_copies'], $_SESSION['used_copies'], $_SESSION['available_copies_color'], $_SESSION['used_copies_color'], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        // Guardar información para el recibo
        $_SESSION['used_bwCopies'] = $bwCopies;
        $_SESSION['used_colorCopies'] = $colorCopies;
        $_SESSION['remaining_bwCopies'] = $_SESSION['available_copies'];
        $_SESSION['remaining_colorCopies'] = $_SESSION['available_copies_color'];
        $_SESSION['transaction_time'] = date('Y-m-d H:i:s');

        // Insertar el recibo en la base de datos
        $stmt = $conn->prepare("INSERT INTO receipts (user_id, bw_copies_used, color_copies_used, bw_copies_remaining, color_copies_remaining, transaction_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiis", $_SESSION['user_id'], $bwCopies, $colorCopies, $_SESSION['remaining_bwCopies'], $_SESSION['remaining_colorCopies'], $_SESSION['transaction_time']);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Se han usado $bwCopies copias en blanco y negro y $colorCopies copias a color.";

        header("Location: " . $_SERVER['PHP_SELF'] . "?show_receipt=1");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- La sección head permanece igual -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Ticket System</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Estilos generales */
        body {
            font-family: 'Raleway', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
            flex-direction: column;
        }

        /* Encabezado del logo */
        .logo-header {
            width: 100%;
            background-color: #fff;
            padding: 15px 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
        }

        /* Estilos del contenedor principal */
        .container {
            display: none;
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
            transition: all 0.3s ease-in-out;
            margin-top: 120px;
        }

        .visible {
            display: block !important;
        }

        /* Estilos de botones */
        .main-button {
            background-color: #53b7c4;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 20px;
            transition: background-color 0.3s ease-in-out, transform 0.3s;
            text-decoration: none;
        }

        .main-button:hover {
            background-color: #48a3b0;
            transform: translateY(-3px);
        }

        /* Estilos de inputs */
        input[type="text"], input[type="password"], input[type="number"] {
            padding: 12px;
            width: 100%;
            margin: 15px 0;
            border: 2px solid #e0e0e0;
            border-radius: 30px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus, input[type="password"]:focus, input[type="number"]:focus {
            border-color: #53b7c4;
            outline: none;
        }

        /* Estilos de modales */
        #successModal, #confirmationModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            width: 300px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Botones de confirmación */
        #confirmButton {
            background-color: #53b7c4;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
        }

        #cancelButton {
            background-color: #ff6b6b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
        }

        /* Botón de volver */
        .back-button {
            background-color: #aaa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 16px;
        }

        .back-button:hover {
            background-color: #888;
            transform: translateY(-3px);
        }

        /* Estilos de la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: center;
        }

        table th {
            background-color: #53b7c4;
            color: white;
        }

    </style>
</head>
<body>
    

     <!-- Encabezado del logo y botón de cerrar sesión si el usuario ha iniciado sesión -->
     <div class="logo-header">
        <img src="https://www.informaticacr.es/wp-content/uploads/2018/12/logo_cabecera-1.png" alt="Logo">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="?logout=true" class="main-button">Cerrar Sesión</a>
        <?php endif; ?>
    </div>

    <!-- Botones iniciales de Login o Registro -->
    <div id="initialButtons">
        <button id="showLogin" class="main-button">Iniciar Sesión</button>
        <button id="showRegister" class="main-button">Registrar</button>
    </div>

    <!-- Contenedor de Registro -->
    <div id="registerContainer" class="container">
        <h1>Registro</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Ingrese nombre de usuario" required>
            <input type="password" name="password" placeholder="Ingrese contraseña" required>
            <button type="submit" name="register" class="main-button">Registrar</button>
        </form>
        <button id="backFromRegister" class="back-button">Volver</button>
    </div>

    <!-- Contenedor de Login -->
    <div id="loginContainer" class="container">
        <h1>Login</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Ingrese nombre de usuario" required>
            <input type="password" name="password" placeholder="Ingrese contraseña" required>
            <button type="submit" name="login" class="main-button">Iniciar Sesión</button>
        </form>
        <button id="backFromLogin" class="back-button">Volver</button>
    </div>

    <!-- Contenedor de Copias -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <div id="ticketContainer" class="container visible">
        <h1>Bienvenido, <?php echo $_SESSION['username']; ?></h1>
        <table>
            <tr>
                <th>Tipo</th>
                <th>Copias Usadas</th>
                <th>Copias Restantes</th>
            </tr>
            <tr>
                <td>Blanco y Negro</td>
                <td><?php echo $_SESSION['used_copies']; ?></td>
                <td><?php echo $_SESSION['available_copies']; ?></td>
            </tr>
            <tr>
                <td>Color</td>
                <td><?php echo $_SESSION['used_copies_color']; ?></td>
                <td><?php echo $_SESSION['available_copies_color']; ?></td>
            </tr>
        </table>
        <form method="POST">
            <input type="hidden" name="use_copies" value="1">
            <h3>Copias en Blanco y Negro:</h3>
            <input type="number" name="bwCopies" min="0" max="<?php echo $_SESSION['available_copies']; ?>" value="0" required>
            <h3>Copias a Color:</h3>
            <input type="number" name="colorCopies" min="0" max="<?php echo $_SESSION['available_copies_color']; ?>" value="0" required>
            <button type="submit" class="main-button">Usar Copias</button>
        </form>
        <!-- Enlace para ver los recibos -->
        <a href="my_receipts.php" class="main-button">Ver Mis Recibos</a>
    </div>
    <?php endif; ?>

    <!-- Modal de Éxito -->
    <div id="successModal">
        <div class="modal-content">
            <p id="successMessage"></p>
            <a href="receipt.php" target="_blank" id="viewReceipt" class="main-button">Ver Recibo</a>
            <button id="closeModal" class="main-button">Cerrar</button>
        </div>
    </div>

    <script>
        var showLoginAfterRegister = <?php echo isset($_SESSION['show_login']) && $_SESSION['show_login'] ? 'true' : 'false'; ?>;
        <?php unset($_SESSION['show_login']); ?>
        var showInitialButtons = <?php echo isset($_SESSION['show_initial']) && $_SESSION['show_initial'] ? 'true' : 'false'; ?>;
        <?php unset($_SESSION['show_initial']); ?>

        document.addEventListener('DOMContentLoaded', function () {
            const initialButtons = document.getElementById('initialButtons');
            const loginContainer = document.getElementById('loginContainer');
            const registerContainer = document.getElementById('registerContainer');
            const ticketContainer = document.getElementById('ticketContainer');
            const successModal = document.getElementById('successModal');
            const successMessage = document.getElementById('successMessage');

            // Mostrar/ocultar contenedores según botón clicado
            document.getElementById('showLogin').addEventListener('click', function() {
                loginContainer.classList.add('visible');
                registerContainer.classList.remove('visible');
                initialButtons.style.display = 'none';
            });

            document.getElementById('showRegister').addEventListener('click', function() {
                registerContainer.classList.add('visible');
                loginContainer.classList.remove('visible');
                initialButtons.style.display = 'none';
            });

            // Botones de volver en formularios
            document.getElementById('backFromRegister').addEventListener('click', function() {
                registerContainer.classList.remove('visible');
                initialButtons.style.display = 'block';
            });

            document.getElementById('backFromLogin').addEventListener('click', function() {
                loginContainer.classList.remove('visible');
                initialButtons.style.display = 'block';
            });

            // Mostrar modal de éxito si hay mensaje
            <?php if (isset($_SESSION['message'])): ?>
                successMessage.innerHTML = `<?php echo $_SESSION['message']; ?>`;
                successModal.style.display = "flex";
            <?php endif; ?>
            <?php
            // Limpiar el mensaje de sesión
            unset($_SESSION['message']);
            ?>

            // Botón para ver el recibo
            <?php if (isset($_GET['show_receipt'])): ?>
                document.getElementById('viewReceipt').style.display = 'inline-block';
            <?php else: ?>
                document.getElementById('viewReceipt').style.display = 'none';
            <?php endif; ?>

            // Después de cerrar el modal de éxito
            document.getElementById('closeModal').addEventListener('click', function() {
                successModal.style.display = 'none';
                if (showLoginAfterRegister) {
                    loginContainer.classList.add('visible');
                    registerContainer.classList.remove('visible');
                    initialButtons.style.display = 'none';
                } else if (showInitialButtons) {
                    loginContainer.classList.remove('visible');
                    registerContainer.classList.remove('visible');
                    initialButtons.style.display = 'block';
                }
            });

            // Ocultar botones iniciales y formularios si el usuario ha iniciado sesión
            <?php if (isset($_SESSION['user_id'])): ?>
                initialButtons.style.display = 'none';
                loginContainer.classList.remove('visible');
                registerContainer.classList.remove('visible');
            <?php endif; ?>

        });
    </script>

</body>
</html>
