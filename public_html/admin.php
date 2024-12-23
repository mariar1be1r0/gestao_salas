<?php
// File: admin.php
include 'auth.php';
include 'db.php';


// Verificar se o usuário é administrador
if ($_SESSION['user_level'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Editar ou excluir usuários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $email, $user_id]);
    echo "Usuário atualizado com sucesso!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    echo "Usuário excluído com sucesso!";
}

// Manutenção de salas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_room'])) {
    $room_id = $_POST['room_id'] ?? null;
    $name = $_POST['name'];
    $status = $_POST['status'];

    if ($room_id) {
        $stmt = $conn->prepare("UPDATE rooms SET name = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $status, $room_id]);
        echo "Sala atualizada com sucesso!";
    } else {
        $stmt = $conn->prepare("INSERT INTO rooms (name, status) VALUES (?, ?)");
        $stmt->execute([$name, $status]);
        echo "Sala adicionada com sucesso!";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id = $_POST['room_id'];
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    echo "Sala excluída com sucesso!";
}

// Listar usuários
$users_stmt = $conn->query("SELECT * FROM users");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar salas
$rooms_stmt = $conn->query("SELECT * FROM rooms");
$rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/admin.css">
    <title>Administração</title>
</head>
<body>
    <header>
        <h1>Administração</h1>
        <nav>
            <a href="profile.php">Voltar para Perfil</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <h2>Manutenção de Usuários</h2>
        <ul>
            <?php foreach ($users as $user): ?>
                <li>
                    <strong><?= htmlspecialchars($user['name']) ?></strong> (<?= htmlspecialchars($user['email']) ?>)
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <label>Nome:</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        <label>Email:</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        <button type="submit" name="edit_user">Salvar</button>
                        <button type="submit" name="delete_user">Excluir</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Manutenção de Salas</h2>
        <form method="POST" action="">
            <label>Nome da Sala:</label>
            <input type="text" name="name" required>
            <label>Status:</label>
            <select name="status">
                <option value="disponivel">Disponível</option>
                <option value="indisponivel">Indisponível</option>
                <option value="brevemente">Brevemente</option>
            </select>
            <button type="submit" name="manage_room">Adicionar Sala</button>
        </form>

        <h3>Lista de Salas</h3>
        <ul>
            <?php foreach ($rooms as $room): ?>
                <li>
                    <strong><?= htmlspecialchars($room['name']) ?></strong> (<?= htmlspecialchars($room['status']) ?>)
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                        <label>Nome:</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($room['name']) ?>" required>
                        <label>Status:</label>
                        <select name="status">
                            <option value="disponivel" <?= $room['status'] === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                            <option value="indisponivel" <?= $room['status'] === 'indisponivel' ? 'selected' : '' ?>>Indisponível</option>
                            <option value="brevemente" <?= $room['status'] === 'brevemente' ? 'selected' : '' ?>>Brevemente</option>
                        </select>
                        <button type="submit" name="manage_room">Salvar</button>
                        <button type="submit" name="delete_room">Excluir</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
