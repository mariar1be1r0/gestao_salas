<?php
// File: index.php
include 'db.php';


// Obter lista de salas com seus estados
$stmt = $conn->query("SELECT name, status FROM rooms");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/index.css">
    <title>Gestão de Salas</title>
</head>
<body>
    <header>
        <h1>Bem-vindo à Gestão de Salas</h1>
        <nav>
            <a href="login.php">Login</a>
            <a href="registo.php">Registrar</a>
        </nav>
    </header>

    <main>
        <ul>
            <?php foreach ($rooms as $room): ?>
                <li><?= htmlspecialchars($room['name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>

