<?php
include 'db.php';


session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Horário de funcionamento
$open_time = "09:00";
$close_time = "19:00";

// Função para verificar lacunas e calcular o próximo horário disponível
function get_next_available_time($reservations, $current_time, $open_time, $close_time) {
    // Ordenar as reservas pelo horário de início
    usort($reservations, function($a, $b) {
        return strtotime($a['reserved_time']) - strtotime($b['reserved_time']);
    });

    // Verificar lacunas entre horários
    $previous_end_time = max($open_time, $current_time); // Começa no horário de abertura ou no horário atual
    foreach ($reservations as $reservation) {
        if ($reservation['reserved_time'] > $previous_end_time) {
            return $previous_end_time; // Lacuna encontrada
        }
        $previous_end_time = max($previous_end_time, $reservation['reserved_end_time']);
    }

    // Se não houver mais lacunas disponíveis hoje, verificar se há disponibilidade antes do horário de fechamento
    if ($previous_end_time < $close_time) {
        return $previous_end_time;
    }

    // Se não há horários disponíveis hoje, retorna "Amanhã às $open_time"
    return "Amanhã às $open_time";
}

// Obter informações do usuário
$stmt = $conn->prepare("SELECT name, level FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Obter lista de salas
$rooms_stmt = $conn->query("SELECT id, name FROM rooms");
$rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

$dashboard_data = [];
$current_time = date('H:i');
$current_date = date('Y-m-d');

foreach ($rooms as $room) {
    // Buscar reservas do dia para a sala atual
    $stmt = $conn->prepare(
        "SELECT reserved_time, reserved_end_time 
        FROM reservations 
        WHERE room_id = ? AND reserved_date = ? 
        ORDER BY reserved_time"
    );
    $stmt->execute([$room['id'], $current_date]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar se a sala está fora do horário de funcionamento
    if ($current_time < $open_time || $current_time >= $close_time) {
        $dashboard_data[] = [
            'name' => $room['name'],
            'status' => 'Indisponível',
            'next_available' => 'Amanhã às ' . $open_time,
        ];
        continue;
    }

    // Verificar se a sala está atualmente sendo usada
    $is_in_use = false;
    foreach ($reservations as $reservation) {
        if ($reservation['reserved_time'] <= $current_time && $reservation['reserved_end_time'] > $current_time) {
            $is_in_use = true;
            break;
        }
    }

    // Calcular próximo horário disponível
    $next_available = get_next_available_time($reservations, $current_time, $open_time, $close_time);

    // Adicionar dados ao array do dashboard
    $dashboard_data[] = [
        'name' => $room['name'],
        'status' => $is_in_use ? 'Em uso' : 'Disponível',
        'next_available' => $next_available,
    ];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/dashboard.css">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .room-status {
            margin-bottom: 20px;
        }

        .in-use {
            color: red;
        }

        .available {
            color: green;
        }

        .unavailable {
            color: gray;
        }

        .room-status h3 {
            margin: 0;
        }

        .room-status p {
            margin: 5px 0;
        }

        nav a {
            margin-right: 10px;
            text-decoration: none;
            color: #007bff;
        }

        nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <h1>Painel Principal</h1>
        <nav>
            <a href="profile.php">Meu Perfil</a>
            <?php if ($user['level'] === 'admin'): ?>
                <a href="admin.php">Administração</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <h2>Salas Disponíveis</h2>
        <div>
            <?php foreach ($dashboard_data as $room): ?>
                <div class="room-status">
                    <h3><?= htmlspecialchars($room['name']) ?></h3>
                    <p>Status: 
                        <span class="<?= $room['status'] === 'Em uso' ? 'in-use' : ($room['status'] === 'Disponível' ? 'available' : 'unavailable') ?>">
                            <?= $room['status'] ?>
                        </span>
                    </p>
                    <p>Próximo horário disponível: <?= htmlspecialchars($room['next_available']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
