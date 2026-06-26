<?php
// ============================================================
// forgot.php — Solicitação de recuperação de senha
// ============================================================

session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';

$erro = '';
$sucesso = '';
$linkRecuperacao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $erro = 'Informe seu e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $pdo = getConexao();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $token = bin2hex(random_bytes(16));
            $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            $update = $pdo->prepare('UPDATE usuarios SET reset_token = :token, reset_expires_at = :expires WHERE id = :id');
            $update->execute([
                ':token' => $token,
                ':expires' => $expires,
                ':id' => $usuario['id'],
            ]);

            $linkRecuperacao = 'reset.php?token=' . urlencode($token);
        }

        $sucesso = 'Se este e-mail estiver cadastrado, um link de recuperação foi gerado.';

        if ($linkRecuperacao) {
            $sucesso .= ' Use o link abaixo para redefinir sua senha.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4">
            <h4 class="card-title text-center mb-4">Recuperar senha</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="post" action="forgot.php" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail cadastrado</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
                <button type="submit" class="btn btn-primary w-100">Gerar recuperação</button>
            </form>

            <?php if ($linkRecuperacao): ?>
                <div class="mt-4 p-3 bg-white border rounded">
                    <p class="mb-2"><strong>Link de recuperação gerado:</strong></p>
                    <a href="<?= htmlspecialchars($linkRecuperacao) ?>"><?= htmlspecialchars($linkRecuperacao) ?></a>
                    <p class="mt-2 text-muted small">Este link não foi enviado por e-mail. Use-o diretamente para redefinir sua senha.</p>
                </div>
            <?php endif; ?>

            <hr>
            <p class="text-center mb-0">
                Já lembra sua senha? <a href="login.php">Fazer login</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
