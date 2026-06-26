<?php
session_start();
require_once 'db.php';

$pdo = getConexao();
$mensagem = '';
$imagemPadrao = 'assets/img/produto-sem-foto.svg';
$isAdmin = isset($_SESSION['usuario_is_admin']) && (int) $_SESSION['usuario_is_admin'] === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $produtoId = (int) ($_POST['produto_id'] ?? 0);
    $quantidade = max(1, (int) ($_POST['quantidade'] ?? 1));

    $stmtProduto = $pdo->prepare('SELECT id, nome, estoque FROM produtos WHERE id = :id AND ativo = 1');
    $stmtProduto->execute([':id' => $produtoId]);
    $produto = $stmtProduto->fetch();

    if ($produto) {
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }

        $atual = (int) ($_SESSION['carrinho'][$produtoId] ?? 0);
        $novoTotal = $atual + $quantidade;
        $_SESSION['carrinho'][$produtoId] = min($novoTotal, (int) $produto['estoque']);
        $mensagem = 'Móvel adicionado ao carrinho com sucesso!';
    }
}

$stmt = $pdo->query('SELECT * FROM produtos WHERE ativo = 1 ORDER BY criado_em DESC');
$produtos = $stmt->fetchAll();
$itensCarrinho = array_sum($_SESSION['carrinho'] ?? []);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jelly Home - Móveis de Gelatina</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: radial-gradient(circle at top left, #fff0f5 0%, #e6f2ff 100%);
            min-height: 100vh;
        }

        /* Estilo Gelatina / Glassmorphism para o Banner Hero */
        .hero {
            background: linear-gradient(135deg, rgba(255, 111, 168, 0.85), rgba(111, 193, 255, 0.85));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #fff;
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            margin-bottom: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Efeito translúcido nos Cards */
        .jelly-card {
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 1.25rem !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .jelly-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(255, 111, 168, 0.15) !important;
        }

        .card-img-top {
            height: 220px;
            object-fit: cover;
            border-top-left-radius: 1.25rem !important;
            border-top-right-radius: 1.25rem !important;
            background-color: #fcebf2;
        }

        .btn-jelly {
            background: linear-gradient(135deg, #ff6fa8, #ff4081);
            color: white;
            border: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .btn-jelly:hover {
            color: white;
            opacity: 0.9;
        }

        .badge-flavor {
            background-color: rgba(255, 111, 168, 0.15);
            color: #d81b60;
            font-weight: 600;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-uppercase tracking-wider" href="loja.php">
            ✨ Jelly Home
        </a>
        <div class="d-flex gap-2">
            <a href="carrinho.php" class="btn btn-outline-light position-relative px-3 btn-sm">
                🛒 Carrinho
                <?php if ($itensCarrinho > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= (int) $itensCarrinho ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="dashboard.php" class="btn btn-success btn-sm">Minha conta</a>
                <a href="perfil.php" class="btn btn-outline-light btn-sm">Meu perfil</a>
                <?php if ($isAdmin): ?>
                    <a href="admin_produtos.php" class="btn btn-warning btn-sm">Cadastrar produto</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Entrar</a>
                <a href="cadastro.php" class="btn btn-outline-info btn-sm">Criar conta</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-5">
    
    <section class="hero shadow">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-3">Móveis de gelatina que encantam</h1>
            <p class="lead mb-0">Deixe sua casa mais brilhante e deliciosamente estilosa com a nossa coleção exclusiva de peças em gelatina estrutural.</p>
        </div>
    </section>

    <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            ✨ <?= htmlspecialchars($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($produtos as $produto): ?>
            <?php 
            $imagemProduto = trim((string) ($produto['imagem_url'] ?? '')); 
            if ($imagemProduto === '') {
                $imagemProduto = $imagemPadrao;
            } 
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm jelly-card">
                    
                    <img src="<?= htmlspecialchars($imagemProduto) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="card-img-top" onerror="this.onerror=null;this.src='<?= htmlspecialchars($imagemPadrao) ?>';">
                    
                    <div class="card-body d-flex flex-column p-4">
                        <span class="badge badge-flavor mb-2 align-self-start text-uppercase px-2 py-1">
                            📦 <?= htmlspecialchars($produto['categoria']) ?>
                        </span>
                        
                        <h2 class="h4 fw-bold mb-2 text-dark"><?= htmlspecialchars($produto['nome']) ?></h2>
                        <p class="text-muted flex-grow-1 small"><?= htmlspecialchars($produto['descricao']) ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-4 fw-bold text-dark">
                                R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?>
                            </span>
                            <?php if ((int)$produto['estoque'] > 0): ?>
                                <small class="text-secondary bg-light px-2 py-1 rounded">Estoque: <?= (int) $produto['estoque'] ?></small>
                            <?php else: ?>
                                <small class="text-danger bg-danger-subtle px-2 py-1 rounded fw-bold">Esgotado</small>
                            <?php endif; ?>
                        </div>
                        
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="acao" value="adicionar">
                            <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                            
                            <input type="number" name="quantidade" value="1" min="1" max="<?= (int) $produto['estoque'] ?>" 
                                   class="form-control text-center fw-bold" 
                                   style="max-width: 75px;"
                                   <?= (int)$produto['estoque'] === 0 ? 'disabled' : '' ?>>
                            
                            <button type="submit" class="btn btn-jelly w-100 d-flex align-items-center justify-content-center gap-1" <?= (int)$produto['estoque'] === 0 ? 'disabled' : '' ?>>
                                <span>Adicionar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>