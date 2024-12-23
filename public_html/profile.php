<?php
include 'auth.php';
include 'db.php';


// Atualizar dados do perfil do usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $image_path = null;

    // Upload de imagem
    if (!empty($_FILES['profile_image']['name'])) {
        $image_path = 'uploads/' . basename($_FILES['profile_image']['name']);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $image_path);
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_image = IFNULL(?, profile_image) WHERE id = ?");
    $stmt->execute([$name, $email, $image_path, $_SESSION['user_id']]);
    echo "<p class='success'>Perfil atualizado com sucesso!</p>";
}

// Excluir uma reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
    $reservation_id = $_POST['reservation_id'];

    $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?");
    $stmt->execute([$reservation_id, $_SESSION['user_id']]);
    echo "<p class='success'>Reserva desmarcada com sucesso!</p>";
}

// Obter dados do usuário
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Listar reservas do usuário
$reservations_stmt = $conn->prepare("SELECT r.name, res.reserved_date, res.reserved_time, res.reserved_end_time, res.id 
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    WHERE res.user_id = ?");
$reservations_stmt->execute([$_SESSION['user_id']]);
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/profile.css">
    <title>Meu Perfil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            text-align: center;
        }

        .profile-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-container img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-container h2 {
            font-size: 24px;
            margin: 10px 0;
        }

        .profile-container p {
            font-size: 18px;
        }

        .profile-container button {
            margin-top: 15px;
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .profile-container button:hover {
            background-color: #0056b3;
        }

        .reservations-section {
            margin-top: 30px;
        }

        .reservation {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
        }

        .reservation button {
            background-color: #dc3545;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .reservation button:hover {
            background-color: #a71d2a;
        }

        /* Estilo do modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            width: 400px;
        }

        .modal-content h3 {
            font-size: 22px;
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-around;
        }

        .modal-buttons button {
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .modal-buttons .confirm {
            background-color: #dc3545;
            color: white;
        }

        .modal-buttons .cancel {
            background-color: #007bff;
            color: white;
        }

        .modal-buttons button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <header>
        <h1>Meu Perfil</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="salas.php">Ir para Salas</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <div class="profile-container">
            <img src="<?= htmlspecialchars($user['profile_image'] ?? 'uploads/default-avatar.png') ?>" alt="Foto de Perfil">
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <button onclick="document.getElementById('edit-profile-form').style.display = 'block'">Editar Perfil</button>
        </div>

        <form id="edit-profile-form" style="display: none;" method="POST" enctype="multipart/form-data" action="">
            <label>Nome:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <label>Foto de Perfil:</label>
            <input type="file" name="profile_image" accept="image/*">
            <button type="submit" name="update_profile">Salvar Alterações</button>
        </form>

        <div class="reservations-section">
            <h2>Minhas Reservas</h2>
            <?php if (!empty($reservations)): ?>
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation">
                        <p><strong>Sala:</strong> <?= htmlspecialchars($reservation['name']) ?></p>
                        <p><strong>Data:</strong> <?= htmlspecialchars($reservation['reserved_date']) ?></p>
                        <p><strong>Horário:</strong> <?= htmlspecialchars($reservation['reserved_time']) ?> - <?= htmlspecialchars($reservation['reserved_end_time']) ?></p>
                        <button type="button" onclick="showModal(<?= $reservation['id'] ?>)">Desmarcar Reserva</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Você ainda não tem reservas.</p>
            <?php endif; ?>
        </div>

        <!-- Modal -->
        <div id="modal" class="modal">
            <div class="modal-content">
                <h3>Tem certeza que deseja desmarcar esta reserva?</h3>
                <div class="modal-buttons">
                    <button class="confirm" onclick="confirmCancel()">Confirmar</button>
                    <button class="cancel" onclick="closeModal()">Cancelar</button>
                </div>
            </div>
        </div>

        <form id="cancel-form" method="POST" style="display: none;">
            <input type="hidden" name="reservation_id" id="reservation-id">
            <input type="hidden" name="delete_reservation">
        </form>
    </main>

    <script>
        const modal = document.getElementById('modal');
        const cancelForm = document.getElementById('cancel-form');
        const reservationIdInput = document.getElementById('reservation-id');

        function showModal(reservationId) {
            reservationIdInput.value = reservationId;
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function confirmCancel() {
            cancelForm.submit();
        }
    </script>
</body>
</html>
