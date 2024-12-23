<?php
include 'auth.php';
include 'db.php';


// Horário de funcionamento
$open_time = "09:00";
$close_time = "19:00";
$time_interval = 60; // Intervalo de 60 minutos para reservas

// Inicializar variáveis
$current_date = date('Y-m-d');
$current_time = date('H:00');
$selected_date = $_POST['selected_date'] ?? null;
$selected_room_id = $_POST['room_id'] ?? null;
$available_time_slots = [];

// Buscar lista de salas
$rooms_stmt = $conn->query("SELECT id, name FROM rooms");
$rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar a seleção de data e sala
if ($selected_date && $selected_room_id) {
    // Verificar se a data é válida (não pode ser antes da data atual)
    if ($selected_date < $current_date) {
        $error_message = "Você não pode fazer reservas para datas anteriores.";
    } else {
        // Buscar horários reservados para a sala selecionada na data escolhida
        $stmt = $conn->prepare(
            "SELECT reserved_time, reserved_end_time 
            FROM reservations 
            WHERE room_id = ? AND reserved_date = ?"
        );
        $stmt->execute([$selected_room_id, $selected_date]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gerar todos os horários possíveis
        $current_time_check = ($selected_date === $current_date) ? max(strtotime($current_time), strtotime($open_time)) : strtotime($open_time);
        $end_time = strtotime($close_time);

        while ($current_time_check < $end_time) {
            $time_slot_start = date('H:i', $current_time_check);
            $time_slot_end = date('H:i', $current_time_check + $time_interval * 60);

            // Verificar se o horário está reservado
            $is_reserved = false;
            foreach ($reservations as $reservation) {
                if (
                    ($time_slot_start >= $reservation['reserved_time'] && $time_slot_start < $reservation['reserved_end_time']) ||
                    ($time_slot_end > $reservation['reserved_time'] && $time_slot_end <= $reservation['reserved_end_time'])
                ) {
                    $is_reserved = true;
                    break;
                }
            }

            // Adicionar horário à lista de disponíveis, se não reservado e válido
            if (!$is_reserved) {
                $available_time_slots[] = [
                    'start' => $time_slot_start,
                    'end' => $time_slot_end
                ];
            }

            // Avançar para o próximo intervalo
            $current_time_check += $time_interval * 60;
        }
    }
}

// Processar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_time'])) {
    $reservation_date = $_POST['selected_date'];
    $reservation_room_id = $_POST['room_id'];
    $reservation_time_start = $_POST['reserve_time_start'];
    $reservation_time_end = date('H:i', strtotime($reservation_time_start) + $time_interval * 60);

    // Verificar se a data e horário são válidos
    if ($reservation_date < $current_date || ($reservation_date === $current_date && $reservation_time_start <= $current_time)) {
        $error_message = "Você só pode fazer reservas para horários futuros.";
    } elseif ($reservation_time_start < $open_time || $reservation_time_end > $close_time) {
        $error_message = "As reservas só podem ser feitas entre $open_time e $close_time.";
    } else {
        // Verificar se já existe uma reserva no mesmo horário
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM reservations 
            WHERE room_id = ? AND reserved_date = ? AND reserved_time = ?"
        );
        $stmt->execute([$reservation_room_id, $reservation_date, $reservation_time_start]);
        $existing_reservations = $stmt->fetchColumn();

        if ($existing_reservations > 0) {
            $error_message = "Este horário já está reservado. Por favor, escolha outro.";
        } else {
            // Inserir reserva no banco de dados
            $stmt = $conn->prepare(
                "INSERT INTO reservations (user_id, room_id, reserved_date, reserved_time, reserved_end_time) 
                VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user_id'],
                $reservation_room_id,
                $reservation_date,
                $reservation_time_start,
                $reservation_time_end
            ]);
            $success_message = "Reserva realizada com sucesso!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/salas.css">
    <title>Reservar Salas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .form-container form {
            margin-bottom: 20px;
        }

        .time-slot {
            display: inline-block;
            margin: 5px;
        }

        .time-slot.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .time-slot input[type="radio"] {
            display: none;
        }

        .time-slot label {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }

        .time-slot input[type="radio"]:checked + label {
            background-color: #007bff;
            color: #fff;
        }
    </style>
</head>
<body>
    <header>
        <h1>Reservar Salas</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <form method="POST" action="">
                <?php if (!$selected_date): ?>
                    <label>Escolha uma data:</label>
                    <input type="date" name="selected_date" min="<?= $current_date ?>" required>
                    <button type="submit">Continuar</button>
                <?php elseif (!$selected_room_id): ?>
                    <input type="hidden" name="selected_date" value="<?= htmlspecialchars($selected_date) ?>">
                    <label>Escolha uma sala:</label>
                    <select name="room_id" required>
                        <option value="">Selecione uma sala</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Ver horários</button>
                <?php else: ?>
                    <input type="hidden" name="selected_date" value="<?= htmlspecialchars($selected_date) ?>">
                    <input type="hidden" name="room_id" value="<?= htmlspecialchars($selected_room_id) ?>">
                    <h3>Horários Disponíveis para <?= htmlspecialchars($selected_date) ?> - Sala: <?= htmlspecialchars($rooms[array_search($selected_room_id, array_column($rooms, 'id'))]['name']) ?></h3>
                    <div>
                        <?php foreach ($available_time_slots as $time_slot): ?>
                            <div class="time-slot">
                                <input type="radio" id="slot-<?= htmlspecialchars($time_slot['start']) ?>" name="reserve_time_start" value="<?= htmlspecialchars($time_slot['start']) ?>" required>
                                <label for="slot-<?= htmlspecialchars($time_slot['start']) ?>">
                                    <?= htmlspecialchars($time_slot['start']) ?> - <?= htmlspecialchars($time_slot['end']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($available_time_slots)): ?>
                            <p>Não há horários disponíveis para esta sala na data selecionada.</p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="reserve_time">Confirmar Reserva</button>
                <?php endif; ?>
            </form>

            <?php if (!empty($success_message)): ?>
                <p style="color: green;"><?= htmlspecialchars($success_message) ?></p>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
