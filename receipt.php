<?php
session_start();

$conn = new mysqli("localhost:3306", "admin_digitickets", "admintickets123.", "digitaltickets");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo "Debe iniciar sesión para ver el recibo.";
    exit();
}

// Verificar si se pasó un receipt_id
if (isset($_GET['receipt_id'])) {
    $receipt_id = $_GET['receipt_id'];

    // Obtener el recibo específico
    $stmt = $conn->prepare("SELECT bw_copies_used, color_copies_used, bw_copies_remaining, color_copies_remaining, transaction_time FROM receipts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $receipt_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($bw_used, $color_used, $bw_remaining, $color_remaining, $transaction_time);
    $stmt->fetch();
    $stmt->close();

    if (!$transaction_time) {
        echo "Recibo no encontrado.";
        exit();
    }
} else {
    // Usar los datos de sesión para mostrar el último recibo
    if (!isset($_SESSION['transaction_time'])) {
        echo "No hay información de recibo disponible.";
        exit();
    }
    $bw_used = $_SESSION['used_bwCopies'];
    $color_used = $_SESSION['used_colorCopies'];
    $bw_remaining = $_SESSION['remaining_bwCopies'];
    $color_remaining = $_SESSION['remaining_colorCopies'];
    $transaction_time = $_SESSION['transaction_time'];
}

// Limpiar variables de sesión relacionadas con el recibo
unset($_SESSION['used_bwCopies']);
unset($_SESSION['used_colorCopies']);
unset($_SESSION['remaining_bwCopies']);
unset($_SESSION['remaining_colorCopies']);
unset($_SESSION['transaction_time']);
?>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Copias</title>
    <style>
        body {
            font-family: 'Raleway', sans-serif;
            background-color: #fff;
            color: #333;
            padding: 20px;
        }
        .receipt-container {
            max-width: 600px;
            margin: auto;
            border: 1px solid #e0e0e0;
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
        .total {
            font-weight: bold;
        }
        .print-button {
            background-color: #53b7c4;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .print-button:hover {
            background-color: #48a3b0;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <h1>Recibo de Copias</h1>
        <p><strong>Usuario:</strong> <?php echo $_SESSION['username']; ?></p>
        <p><strong>Fecha y Hora:</strong> <?php echo $transaction_time; ?></p>

        <table>
            <tr>
                <th>Tipo de Copia</th>
                <th>Copias Usadas</th>
                <th>Copias Restantes</th>
            </tr>
            <tr>
                <td>Blanco y Negro</td>
                <td><?php echo $bw_used; ?></td>
                <td><?php echo $bw_remaining; ?></td>
            </tr>
            <tr>
                <td>Color</td>
                <td><?php echo $color_used; ?></td>
                <td><?php echo $color_remaining; ?></td>
            </tr>
        </table>

        <button class="print-button" onclick="window.print()">Imprimir Recibo</button>
    </div>
</body>
</html>
