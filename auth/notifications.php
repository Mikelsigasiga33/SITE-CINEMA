<?php
require dirname(__DIR__) . '/config/db.php';

// Iniciar sessão se necessário para verificar login antes de enviar HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_name'])) {
    header("Location: /auth/login.php");
    exit;
}

include dirname(__DIR__) . '/includes/header.php';

// Lógica de remoção
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $removeId = $_POST['remove_id'];
    $userId = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM AAA_NOTIFICACOES WHERE ID_NOTIFICACAO = :nid AND ID_UTILIZADOR = :user_id");
        $stmt->execute([':nid' => $removeId, ':user_id' => $userId]);
        $successMsg = "Notificação removida com sucesso.";
    } catch (PDOException $e) {
        // Erro silencioso ou log
    }
}

// Buscar Notificações Reais
$notifications = [];
try {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT n.ID_NOTIFICACAO, f.ID_FILME, f.TITULO, f.IMG, f.ANO, f.GENERO, n.DATA_REGISTO 
            FROM AAA_NOTIFICACOES n 
            JOIN AAA_FILMES f ON n.ID_FILME = f.ID_FILME 
            WHERE n.ID_UTILIZADOR = :user_id 
            ORDER BY n.DATA_REGISTO DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row = array_change_key_case($row, CASE_UPPER);
        
        // Proxy de Imagem
        $fallbackImage = 'https://via.placeholder.com/300x450?text=Sem+Imagem';
        $posterUrl = $fallbackImage;
        
        if (!empty($row['IMG'])) {
            $cleanLink = str_replace(['https://', 'http://'], '', $row['IMG']);
            $posterUrl = "https://images.weserv.nl/?url=" . urlencode($cleanLink) . "&w=300&h=450&fit=cover&errorredirect=" . urlencode($fallbackImage);
        }
        
        $notifications[] = [
            'id' => $row['ID_NOTIFICACAO'],
            'movie_id' => $row['ID_FILME'],
            'titulo' => $row['TITULO'],
            'ano' => $row['ANO'] ?? 'N/A',
            'genero' => $row['GENERO'] ?? 'N/A',
            'poster' => $posterUrl,
            'data_estreia' => 'Brevemente',
            'data_criacao' => date('d/m/Y', strtotime($row['DATA_REGISTO']))
        ];
    }
} catch (PDOException $e) {
    // Erro na BD
}
?>

<style>
    .notification-card {
        background-color: #1f1f1f;
        border: 1px solid #333;
        border-radius: 10px;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    }
    .notification-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        border-color: #e50914;
    }
    .movie-thumb {
        width: 80px;
        height: 120px;
        object-fit: cover;
        border-radius: 5px;
    }
    .btn-remove {
        color: #dc3545;
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.2);
        transition: all 0.2s;
    }
    .btn-remove:hover {
        background: #dc3545;
        color: white;
        box-shadow: 0 0 10px rgba(220, 53, 69, 0.4);
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                <h2 class="fw-bold text-white mb-0">
                    <i class="bi bi-bell-fill text-danger me-2"></i>Gestão de Notificações
                </h2>
                <span class="badge bg-dark border border-secondary text-secondary"><?= count($notifications) ?> alertas ativos</span>
            </div>

            <?php if (isset($successMsg)): ?>
                <div class="alert alert-success bg-dark border-success text-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= $successMsg ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($notifications)): ?>
                <div class="text-center py-5 text-secondary">
                    <i class="bi bi-bell-slash display-1 mb-3 d-block opacity-25"></i>
                    <h4>Sem notificações ativas</h4>
                    <p>Quando subscreveres alertas de estreias, eles aparecerão aqui.</p>
                    <a href="/pages/movies.php?tab=upcoming" class="btn btn-outline-danger mt-3">Ver Próximos Lançamentos</a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-card p-3 d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($notif['poster']) ?>" alt="Poster" class="movie-thumb">
                            <div class="flex-grow-1">
                                <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($notif['titulo']) ?></h5>
                                <p class="text-secondary mb-1 small">
                                    <span class="me-3"><i class="bi bi-calendar me-1 text-danger"></i><?= htmlspecialchars($notif['ano']) ?></span>
                                    <span><i class="bi bi-film me-1 text-danger"></i><?= htmlspecialchars($notif['genero']) ?></span>
                                </p>
                                <p class="text-secondary mb-1 small">
                                    <i class="bi bi-calendar-event me-1 text-danger"></i>Estreia prevista: <span class="text-white fw-bold"><?= htmlspecialchars($notif['data_estreia']) ?></span>
                                </p>
                                <p class="text-secondary mb-0 small opacity-75">
                                    <i class="bi bi-clock-history me-1"></i>Subscrito em: <?= htmlspecialchars($notif['data_criacao']) ?>
                                </p>
                            </div>
                            <form method="POST" onsubmit="return confirm('Tem a certeza que deseja remover o alerta para <?= addslashes($notif['titulo']) ?>?');">
                                <input type="hidden" name="remove_id" value="<?= $notif['id'] ?>">
                                <button type="submit" class="btn btn-remove rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Remover notificação">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
