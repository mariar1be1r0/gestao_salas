<?php
// File: register.php
include 'db.php';


$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $level = $_POST['level']; // user or admin

    // Validar senha para garantir que não seja duplicada
    $stmt = $conn->prepare("SELECT * FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $password_exists = false;
    foreach ($users as $user) {
        $existing_hashed_password = $user['password'];
        $salt = $user['salt'];
        if (hash('sha256', $salt . $password) === $existing_hashed_password) {
            $password_exists = true;
            break;
        }
    }

    if ($password_exists) {
        $error_message = "A senha já está em uso. Escolha outra senha.";
    } else {
        // Inserir usuário no banco de dados
        $salt = bin2hex(random_bytes(16));
        $hashed_password = hash('sha256', $salt . $password);

        $stmt = $conn->prepare("INSERT INTO users (name, login, email, password, salt, level) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $login, $email, $hashed_password, $salt, $level])) {
            header("Location: login.php");
            exit;
        } else {
            $error_message = "Erro ao criar o perfil. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/registo.css">
    <title>Registo</title>
    <script>
        // Função para validar campos obrigatórios antes de enviar o formulário
        function validateForm() {
            const name = document.forms["registerForm"]["name"].value;
            const login = document.forms["registerForm"]["login"].value;
            const email = document.forms["registerForm"]["email"].value;
            const password = document.forms["registerForm"]["password"].value;
            const level = document.forms["registerForm"]["level"].value;

            if (!name || !login || !email || !password || !level) {
                alert("Todos os campos devem ser preenchidos!");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <form name="registerForm" method="POST" action="" onsubmit="return validateForm()">
        <label>Nome:</label>
        <input type="text" name="name" required>
        <label>Login:</label>
        <input type="text" name="login" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Senha:</label>
        <input type="password" name="password" required>
        <label>Nível:</label>
        <select name="level" required>
            <option value="user">Usuário</option>
            <option value="admin">Administrador</option>
        </select>
        <button type="submit">Registo</button>
    </form>

    <?php if ($error_message): ?>
        <script>
            alert("<?= htmlspecialchars($error_message) ?>");
        </script>
    <?php endif; ?>
</body>
</html>
