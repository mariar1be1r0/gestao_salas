<?php



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Ajuste o caminho do autoload do PHPMailer
include __DIR__ . '/db.php'; // Caminho absoluto para evitar erros

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Validação do e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "E-mail inválido!";
    } else {
        // Verificar se o e-mail existe no banco de dados
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Gerar token e definir validade
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Inserir token na tabela `password_resets`
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires_at]);

            // Configurar PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.sapo.pt';
                $mail->SMTPAuth = true;
                $mail->Username = 'saw_trabalho@sapo.pt'; // Substitua pelo e-mail
                $mail->Password = '9mT9?6FyWHE&&es'; // Substitua pela senha do aplicativo
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('saw_trabalho@sapo.pt', 'Gestão de Salas');
                $mail->addAddress($email);

                // Conteúdo do e-mail
                $reset_link = "http://saw.pt/reset.php?token=$token";
                $mail->isHTML(true);
                $mail->Subject = 'Redefinição de Senha - Gestão de Salas';
                $mail->Body = "
                    <p>Olá,</p>
                    <p>Você solicitou a redefinição de sua senha. Clique no link abaixo:</p>
                    <p><a href='$reset_link'>$reset_link</a></p>
                    <p>O link expira em 1 hora. Caso não tenha solicitado, ignore este e-mail.</p>
                ";

                $mail->send();
                $success_message = "E-mail enviado com sucesso! Verifique sua caixa de entrada.";
            } catch (Exception $e) {
                $error_message = "Erro ao enviar o e-mail: {$mail->ErrorInfo}";
            }
        } else {
            $error_message = "E-mail não encontrado!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/esqueceu_senha.css">
    <title>Recuperar Senha</title>
</head>
<body>
    <form method="POST" action="">
        <h1>Recuperar Senha</h1>
        <label>Email:</label>
        <input type="email" name="email" required>
        <button type="submit">Recuperar Senha</button>

        <?php if (isset($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php elseif (isset($success_message)): ?>
            <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>

        <div class="back-to-login">
            <a href="login.php">Voltar para o login</a>
        </div>
    </form>
</body>
</html>
