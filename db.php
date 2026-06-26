<?php
// ============================================================
// db.php — Conexão com o banco de dados SQLite via PDO
// ============================================================

define('DB_PATH', __DIR__ . '/database.db');

function getConexao(): PDO {
    static $pdo = null; // Reutiliza a conexão durante a requisição

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            // Lança exceções em caso de erro (bom para depuração em aula)
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Retorna resultados como array associativo por padrão
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Cria a tabela de usuários se ainda não existir
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS usuarios (
                    id       INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome     TEXT    NOT NULL,
                    email    TEXT    NOT NULL UNIQUE,
                    senha    TEXT    NOT NULL,
                    is_admin INTEGER NOT NULL DEFAULT 0,
                    cpf      TEXT,
                    endereco TEXT,
                    numero   TEXT,
                    bairro   TEXT,
                    cidade   TEXT,
                    estado   TEXT,
                    cep      TEXT,
                    telefone TEXT,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            // ==========================================
// CÓDIGO TEMPORÁRIO PARA POPULAR A LOJA DE GELATINA
// (Você pode apagar este bloco após abrir a página no navegador uma vez)
// ==========================================

// 1. Limpa os produtos antigos de circuitos para não misturar
$pdo->exec("DELETE FROM produtos");

// 2. Insere os móveis de gelatina com descrições e preços correspondentes
$produtosGelatina = [
    [
        'nome' => 'Sofá Poltrona de Morango',
        'descricao' => 'Poltrona inflável preenchida com gelatina estrutural sabor morango. Conforto ergonômico com aroma sutil e brilho translúcido impecável.',
        'categoria' => 'Salas',
        'preco' => 849.90,
        'estoque' => 4,
        'imagem_url' => 'https://m.media-amazon.com/images/S/aplus-media/sc/56ef445e-b546-448e-a615-4f9cf39c9d94.__CR0,0,1600,1600_PT0_SX300_V1___.jpg' 
    ],
];

$stmtInsert = $pdo->prepare("INSERT INTO produtos (nome, descricao, categoria, preco, estoque, imagem_url, ativo) VALUES (:nome, :descricao, :categoria, :preco, :estoque, :imagem_url, 1)");

foreach ($produtosGelatina as $prod) {
    $stmtInsert->execute([
        ':nome'       => $prod['nome'],
        ':descricao'  => $prod['descricao'],
        ':categoria'  => $prod['categoria'],
        ':preco'      => $prod['preco'],
        ':estoque'    => $prod['estoque'],
        ':imagem_url' => $prod['imagem_url']
    ]);
}
// ==========================================

            garantirColunaAdminUsuarios($pdo);
            garantirColunasPerfilUsuarios($pdo);
            garantirColunasRecuperacaoSenhaUsuarios($pdo);

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS produtos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    descricao TEXT NOT NULL,
                    categoria TEXT NOT NULL,
                    preco REAL NOT NULL CHECK (preco >= 0),
                    estoque INTEGER NOT NULL DEFAULT 0 CHECK (estoque >= 0),
                    imagem_url TEXT,
                    ativo INTEGER NOT NULL DEFAULT 1,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS pedidos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    usuario_id INTEGER NOT NULL,
                    valor_total REAL NOT NULL CHECK (valor_total >= 0),
                    status TEXT NOT NULL DEFAULT 'novo',
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS pedido_itens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    pedido_id INTEGER NOT NULL,
                    produto_id INTEGER NOT NULL,
                    nome_produto TEXT NOT NULL,
                    preco_unitario REAL NOT NULL CHECK (preco_unitario >= 0),
                    quantidade INTEGER NOT NULL CHECK (quantidade > 0),
                    subtotal REAL NOT NULL CHECK (subtotal >= 0),
                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
                    FOREIGN KEY (produto_id) REFERENCES produtos(id)
                )
            ");

            popularProdutosIniciais($pdo);
            garantirAdminInicial($pdo);
        } catch (PDOException $e) {
            // Em produção, nunca exiba detalhes do erro para o usuário
            die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    return $pdo;
}

function popularProdutosIniciais(PDO $pdo): void {
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM produtos');
    $total = (int) ($stmt->fetch()['total'] ?? 0);

    if ($total > 0) {
        return;
    }

    $produtos = [
        [
            'Poltrona Gelatina Coral',
            'Poltrona macia e acolchoada, com acabamento brilhante e visual irresistível para salas modernas.',
            'Poltronas',
            489.90,
            4,
            'https://images.unsplash.com/photo-1519947486511-46149fa0a254?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'Mesa de Centro Gelatina',
            'Mesa low profile com tampo translúcido e base colorida, perfeita para ambientes sofisticados.',
            'Mesas',
            329.00,
            6,
            'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'Estante Flutuante Aurora',
            'Estante elegante e leve, com estrutura em tom pastel e destaque para decorar paredes.',
            'Estantes',
            599.50,
            3,
            'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'Cadeira de Jantar Jujuba',
            'Cadeira charmosa, confortável e cheia de personalidade para compor a sua cozinha ou área gourmet.',
            'Cadeiras',
            249.00,
            8,
            'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80',
        ],
    ];

    $insert = $pdo->prepare('
        INSERT INTO produtos (nome, descricao, categoria, preco, estoque, imagem_url)
        VALUES (:nome, :descricao, :categoria, :preco, :estoque, :imagem_url)
    ');

    foreach ($produtos as $produto) {
        $insert->execute([
            ':nome' => $produto[0],
            ':descricao' => $produto[1],
            ':categoria' => $produto[2],
            ':preco' => $produto[3],
            ':estoque' => $produto[4],
            ':imagem_url' => $produto[5],
        ]);
    }
}

function garantirColunaAdminUsuarios(PDO $pdo): void {
    $stmt = $pdo->query("PRAGMA table_info('usuarios')");
    $colunas = $stmt->fetchAll();

    foreach ($colunas as $coluna) {
        if (($coluna['name'] ?? '') === 'is_admin') {
            return;
        }
    }

    $pdo->exec('ALTER TABLE usuarios ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0');
}

function garantirColunasPerfilUsuarios(PDO $pdo): void {
    $stmt = $pdo->query("PRAGMA table_info('usuarios')");
    $colunas = $stmt->fetchAll();
    $nomesColunas = [];

    foreach ($colunas as $coluna) {
        $nomesColunas[] = $coluna['name'] ?? '';
    }

    $faltantes = [
        'cpf' => 'ALTER TABLE usuarios ADD COLUMN cpf TEXT',
        'endereco' => 'ALTER TABLE usuarios ADD COLUMN endereco TEXT',
        'numero' => 'ALTER TABLE usuarios ADD COLUMN numero TEXT',
        'bairro' => 'ALTER TABLE usuarios ADD COLUMN bairro TEXT',
        'cidade' => 'ALTER TABLE usuarios ADD COLUMN cidade TEXT',
        'estado' => 'ALTER TABLE usuarios ADD COLUMN estado TEXT',
        'cep' => 'ALTER TABLE usuarios ADD COLUMN cep TEXT',
        'telefone' => 'ALTER TABLE usuarios ADD COLUMN telefone TEXT',
    ];

    foreach ($faltantes as $coluna => $sql) {
        if (!in_array($coluna, $nomesColunas, true)) {
            $pdo->exec($sql);
        }
    }
}

function garantirColunasRecuperacaoSenhaUsuarios(PDO $pdo): void {
    $stmt = $pdo->query("PRAGMA table_info('usuarios')");
    $colunas = $stmt->fetchAll();
    $nomesColunas = [];

    foreach ($colunas as $coluna) {
        $nomesColunas[] = $coluna['name'] ?? '';
    }

    $faltantes = [
        'reset_token' => 'ALTER TABLE usuarios ADD COLUMN reset_token TEXT',
        'reset_expires_at' => 'ALTER TABLE usuarios ADD COLUMN reset_expires_at DATETIME',
    ];

    foreach ($faltantes as $coluna => $sql) {
        if (!in_array($coluna, $nomesColunas, true)) {
            $pdo->exec($sql);
        }
    }
}

function garantirAdminInicial(PDO $pdo): void {
    $stmt = $pdo->query('SELECT COUNT(*) AS total_admin FROM usuarios WHERE is_admin = 1');
    $totalAdmin = (int) ($stmt->fetch()['total_admin'] ?? 0);

    if ($totalAdmin > 0) {
        return;
    }

    $stmtPrimeiroUsuario = $pdo->query('SELECT id FROM usuarios ORDER BY id ASC LIMIT 1');
    $primeiroUsuario = $stmtPrimeiroUsuario->fetch();

    if (!$primeiroUsuario) {
        return;
    }

    $update = $pdo->prepare('UPDATE usuarios SET is_admin = 1 WHERE id = :id');
    $update->execute([':id' => (int) $primeiroUsuario['id']]);
}
