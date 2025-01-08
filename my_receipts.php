<?php
session_start();

$conn = new mysqli("localhost:3306", "admin_digitickets", "admintickets123.", "digitaltickets");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo "Debe iniciar sesión para ver sus recibos.";
    exit();
}

// Obtener los recibos del usuario
$stmt = $conn->prepare("SELECT id, bw_copies_used, color_copies_used, bw_copies_remaining, color_copies_remaining, transaction_time FROM receipts WHERE user_id = ? ORDER BY transaction_time DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$receipts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mis Recibos</title>
    <style>
        body {
            font-family: 'Raleway', sans-serif;
            background-color: #fff;
            color: #333;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border-radius: 15px;
        }
        h1 {
            text-align: center;
            color: #53b7c4;
        }
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
        .back-button {
            background-color: #53b7c4;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .back-button:hover {
            background-color: #48a3b0;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <h1>Mis Recibos</h1>
        <?php if (count($receipts) > 0): ?>
            <table>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Copias B/N Usadas</th>
                    <th>Copias Color Usadas</th>
                    <th>Copias B/N Restantes</th>
                    <th>Copias Color Restantes</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($receipts as $receipt): ?>
                    <tr>
                        <td><?php echo $receipt['transaction_time']; ?></td>
                        <td><?php echo $receipt['bw_copies_used']; ?></td>
                        <td><?php echo $receipt['color_copies_used']; ?></td>
                        <td><?php echo $receipt['bw_copies_remaining']; ?></td>
                        <td><?php echo $receipt['color_copies_remaining']; ?></td>
                        <td>
                            <a href="receipt.php?receipt_id=<?php echo $receipt['id']; ?>" target="_blank">Ver Recibo</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No hay recibos disponibles.</p>
        <?php endif; ?>
        <a href="index.php" class="back-button">Volver</a>
    </div>
</body>
</html>
