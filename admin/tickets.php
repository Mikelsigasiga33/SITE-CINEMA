<?php
require dirname(__DIR__) . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificação de Segurança
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['tipo_permissao']) !== 'ADMINISTRADOR') {
    header("Location: /index.php");
    exit;
}

$msg = '';

// Buscar Bilhetes
$search = $_GET['q'] ?? '';
$tickets = [];
try {
    $sql = "SELECT 
                b.ID_BILHETE,
                u.NOME, u.SEGUNDO_NOME, u.EMAIL,
                f.TITULO,
                TO_CHAR(s.DATA_HORA, 'YYYY-MM-DD HH24:MI') as DATA_SESSAO,
                b.ASSENTO,
                b.PRECO,
                b.STATUS_PAGAMENTO,
                TO_CHAR(b.DATA_COMPRA, 'YYYY-MM-DD HH24:MI') as DATA_COMPRA
            FROM AAA_BILHETES b
            JOIN AAA_UTILIZADORES u ON b.ID_CLIENTE = u.ID_UTILIZADOR
            JOIN AAA_SESSOES s ON b.ID_SESSAO = s.ID_SESSAO
            JOIN AAA_FILMES f ON s.ID_FILME = f.ID_FILME
            WHERE 1=1";
    
    $params = [];
    if (!empty($search)) {
        // Pesquisa por filme, nome do cliente ou email
        $sql .= " AND (UPPER(f.TITULO) LIKE UPPER(:search) OR UPPER(u.NOME) LIKE UPPER(:search) OR UPPER(u.EMAIL) LIKE UPPER(:search))";
        $params[':search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY b.DATA_COMPRA DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = "Erro ao carregar bilhetes: " . $e->getMessage();
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-white border-start border-4 border-warning ps-3">Controlo de Bilhetes</h2>
        <div class="d-flex align-items-center gap-2">
            <form class="d-flex" method="GET">
                <input class="form-control bg-dark text-white border-secondary me-2" type="search" name="q" placeholder="Pesquisar cliente ou filme..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-light" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-2"></i>Voltar</a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive bg-dark border border-secondary rounded">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="border-bottom border-secondary">
                    <th class="p-3">ID</th>
                    <th class="p-3">Cliente</th>
                    <th class="p-3">Filme</th>
                    <th class="p-3">Sessão</th>
                    <th class="p-3">Lugar</th>
                    <th class="p-3">Preço</th>
                    <th class="p-3">Compra</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td class="p-3">#<?= $t['ID_BILHETE'] ?></td>
                    <td class="p-3">
                        <div class="fw-bold"><?= htmlspecialchars($t['NOME'] . ' ' . ($t['SEGUNDO_NOME'] ?? '')) ?></div>
                        <small class="text-secondary"><?= htmlspecialchars($t['EMAIL']) ?></small>
                    </td>
                    <td class="p-3 fw-bold"><?= htmlspecialchars($t['TITULO']) ?></td>
                    <td class="p-3 text-warning"><i class="bi bi-clock me-2"></i><?= date('d/m/Y H:i', strtotime($t['DATA_SESSAO'])) ?></td>
                    <td class="p-3"><span class="badge bg-secondary"><?= htmlspecialchars($t['ASSENTO']) ?></span></td>
                    <td class="p-3"><?= number_format($t['PRECO'], 2, ',', '.') ?> €</td>
                    <td class="p-3 text-secondary small"><?= date('d/m/Y H:i', strtotime($t['DATA_COMPRA'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>