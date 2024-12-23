<?php

include __DIR__ . '/db.php'; // Caminho absoluto para evitar erros

$token = $_GET['token'] ?? '';

$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'];

    // Validação da senha
    if (strlen($new_password) < 8) {
        $error_message = "A senha deve ter pelo menos 8 caracteres.";
    } else {
        $salt = bin2hex(random_bytes(16));
        $hashed_password = hash('sha256', $salt . $new_password);

        $stmt = $conn->prepare("UPDATE users SET password = ?, salt = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $salt, $reset['user_id']]);

        $stmt = $conn->prepare("DELETE FROM password_resets WHERE id = ?");
        $stmt->execute([$reset['id']]);

        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/reset.css">
    <title>Redefinir Senha</title>
</head>
<body>
    <?php if ($reset): ?>
        <form method="POST" action="">
            <label>Nova Senha:</label>
            <input type="password" name="password" required>
            <button type="submit">Redefinir</button>

            <div class="back-to-login">
                <a href="login.php">Voltar para Login</a>
            </div>
        </form>
    <?php else: ?>
        <p class="error-message">Token inválido ou expirado.</p>
    <?php endif; ?>
</body>
</html>
