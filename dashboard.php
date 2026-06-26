<?php
session_start();
require_once 'db.php';

// 1. Bloqueio de Segurança: Se não estiver logado, vai para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = getConexao();
$usuarioNome = $_SESSION['usuario_nome'];

// 2. Verifica se o usuário é Administrador
$isAdmin = isset($_SESSION['usuario_is_admin']) && (int)$_SESSION['usuario_is_admin'] === 1;

// Lógica do Admin: Se ele quiser deletar um produto pelo painel
if ($isAdmin && isset($_GET['deletar'])) {
    $idDeletar = (int)$_GET['deletar'];
    $stmt = $pdo->prepare("UPDATE produtos SET ativo = 0 WHERE id = ?");
    $stmt->execute([$idDeletar]);
    header("Location: dashboard.php?sucesso=1");
    exit;
}

// Busca dados para exibir na tela conforme o nível de acesso
if ($isAdmin) {
    // Admin vê a lista completa de produtos cadastrados para gerenciar
    $stmt = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY criado_em DESC");
    $produtos = $stmt->fetchAll();
} else {
    // Cliente vê o histórico de compras ou dados do perfil (vamos puxar os dados dele)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $dadosCliente = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel - Jelly Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; }
        .panel-header { background: linear-gradient(135deg, #343a40, #212529); color: white; border-radius: 1rem; padding: 2rem; margin-bottom: 2rem; }
        .jelly-badge { background-color: rgba(255, 111, 168, 0.15); color: #d81b60; font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="loja.php">✨ Jelly Home</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Sair do Sistema</a>
    </div>
</nav>

<div class="container py-5">
    
    <div class="panel-header shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Olá, <?= htmlspecialchars($usuarioNome) ?>! 👋</h1>
                <p class="mb-0 text-muted-light text-white-50">
                    Nível de Acesso: 
                    <span class="badge <?= $isAdmin ? 'bg-warning text-dark' : 'bg-info' ?>">
                        <?= $isAdmin ? '👑 Administrador' : '🛒 Cliente' ?>
                    </span>
                </p>
            </div>
            <a href="loja.php" class="btn btn-outline-light btn-sm">Voltar para a Loja</a>
        </div>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">Operação realizada com sucesso!</div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0 fw-bold">Gerenciamento de Estoque de Gelatina</h2>
                    <a href="admin_produtos.php" class="btn btn-success btn-sm">+ Cadastrar Novo Móvel</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome do Móvel</th>
                                <th>Sabor / Categoria</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum produto cadastrado.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($p['nome']) ?></td>
                                    <td><span class="badge jelly-badge"><?= htmlspecialchars($p['categoria']) ?></span></td>
                                    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                                    <td><?= (int)$p['estoque'] ?> unid.</td>
                                    <td class="text-center">
                                        <a href="dashboard.php?deletar=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tem certeza que deseja remover este móvel?')">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body p-4 text-center">
                        <div class="bg-light rounded-circle d-inline-block p-3 mb-3">👤</div>
                        <h3 class="h5 fw-bold mb-1">Meus Dados</h3>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($dadosCliente['email']) ?></p>
                        <a href="perfil.php" class="btn btn-outline-secondary btn-sm w-100">Editar Perfil</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold mb-3">🛍️ Minhas Compras de Gelatina</h3>
                        <div class="text-center py-4 text-muted">
                            <p class="mb-0">Você ainda não realizou nenhuma compra.</p>
                            <small>Navegue pela loja para escolher seus móveis!</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>