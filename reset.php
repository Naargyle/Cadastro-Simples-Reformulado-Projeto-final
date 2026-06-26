<?php
// ============================================================
// reset.php — Redefinição de senha a partir do token de recuperação
// ============================================================

session_start();
require_once 'db.php';

$erro = '';
$sucesso = '';
$mostrarFormulario = true;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$usuario = null;

if ($token === '') {
    $erro = 'Token inválido ou expirado.';
    $mostrarFormulario = false;
} else {
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE reset_token = :token');
    $stmt->execute([':token' => $token]);
    $usuario = $stmt->fetch();

    if (!$usuario || empty($usuario['reset_expires_at']) || $usuario['reset_expires_at'] < date('Y-m-d H:i:s')) {
        $erro = 'Token inválido ou expirado.';
        $mostrarFormulario = false;
    }
}

if ($mostrarFormulario && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma'] ?? '';

    if (empty($senha) || empty($confirma)) {
        $erro = 'Preencha todos os campos.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE usuarios SET senha = :senha, reset_token = NULL, reset_expires_at = NULL WHERE id = :id');
        $update->execute([
            ':senha' => $hash,
            ':id' => $usuario['id'],
        ]);

        $sucesso = 'Senha redefinida com sucesso. Agora você pode fazer login.';
        $mostrarFormulario = false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redefinir senha</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4">
            <h4 class="card-title text-center mb-4">Redefinir senha</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <?php if ($mostrarFormulario): ?>
                <form method="post" action="reset.php" novalidate>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label for="senha" class="form-label">Nova senha</label>
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            class="form-control"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-3">
                        <label for="confirma" class="form-label">Confirmar senha</label>
                        <input
                            type="password"
                            id="confirma"
                            name="confirma"
                            class="form-control"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Redefinir senha</button>
                </form>
            <?php endif; ?>

            <?php if (!$mostrarFormulario && !$erro && $sucesso): ?>
                <div class="text-center mt-3">
                    <a href="login.php">Voltar ao login</a>
                </div>
            <?php endif; ?>

            <?php if (!$mostrarFormulario && $erro): ?>
                <div class="text-center mt-3">
                    <a href="forgot.php">Solicitar novo link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
