<?php
require dirname(__DIR__) . '/config/db.php';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

include dirname(__DIR__) . '/includes/header.php';

$groupedTickets = [];
$error = '';

try {
    $userId = $_SESSION['user_id'];
    
    // Consulta à tabela AAA_BILHETES
    // Faz JOIN com AAA_SESSOES e depois com AAA_FILMES para obter os dados corretos
    $sql = "SELECT 
                b.ID_BILHETE, 
                b.ID_SESSAO, 
                b.ASSENTO, 
                b.TIPO_BILHETE, 
                b.PRECO, 
                b.STATUS_PAGAMENTO, 
                TO_CHAR(b.DATA_COMPRA, 'YYYY-MM-DD HH24:MI:SS') as DATA_COMPRA_STR,
                f.TITULO,
                f.IMG,
                TO_CHAR(s.DATA_HORA, 'DD/MM/YYYY HH24:MI') as SESSAO_DATA
            FROM AAA_BILHETES b
            LEFT JOIN AAA_SESSOES s ON b.ID_SESSAO = s.ID_SESSAO
            LEFT JOIN AAA_FILMES f ON s.ID_FILME = f.ID_FILME
            WHERE b.ID_CLIENTE = :user_id 
            ORDER BY b.DATA_COMPRA DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    
    // Agrupar bilhetes por ID_SESSAO
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row = array_change_key_case($row, CASE_UPPER);
        
        // Tratamento de imagem (Proxy)
        $posterUrl = 'https://via.placeholder.com/100x150?text=Filme';
        if (!empty($row['IMG'])) {
            $cleanLink = str_replace(['https://', 'http://'], '', $row['IMG']);
            $posterUrl = "https://images.weserv.nl/?url=" . urlencode($cleanLink) . "&w=100&h=150&fit=cover";
        }

        $sessionId = $row['ID_SESSAO'];
        // Chave de agrupamento: Sessão + Data da Compra (para separar compras feitas em momentos diferentes)
        $groupKey = $sessionId . '_' . $row['DATA_COMPRA_STR'];
        
        if (!isset($groupedTickets[$groupKey])) {
            $groupedTickets[$groupKey] = [
                'sessao_id' => $sessionId,
                'titulo' => $row['TITULO'] ?? 'Sessão #' . $sessionId,
                'poster' => $posterUrl,
                'data_sessao' => $row['SESSAO_DATA'] ?? 'Data N/A',
                'total_preco' => 0,
                'bilhetes' => []
            ];
        }

        $groupedTickets[$groupKey]['bilhetes'][] = [
            'id' => $row['ID_BILHETE'],
            'assento' => $row['ASSENTO'],
            'tipo' => $row['TIPO_BILHETE'],
            'status' => $row['STATUS_PAGAMENTO']
        ];
        
        $groupedTickets[$groupKey]['total_preco'] += $row['PRECO'];
    }

} catch (PDOException $e) {
    $error = "Erro ao carregar bilhetes: " . $e->getMessage();
}
?>

<style>
    .ticket-group-card {
        background-color: #1f1f1f;
        border: 1px solid #333;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
    }
    .ticket-group-card:hover {
        transform: translateY(-5px);
        border-color: #e50914;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    /* Efeito de "pilha" de bilhetes */
    .ticket-group-card::before {
        content: ''; position: absolute; top: 5px; left: 5px; right: 5px; bottom: -5px;
        background: rgba(255,255,255,0.05); border-radius: 16px; z-index: -1;
        transition: bottom 0.3s;
    }
    .ticket-poster {
        width: 100px;
        height: 150px;
        object-fit: cover;
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .count-badge {
        background-color: #e50914;
        color: white;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 12px;
        position: absolute;
        top: 10px;
        right: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.5);
    }

    /* Estilo do Bilhete Digital (Modal) */
    .digital-ticket {
        background-color: #fff;
        color: #333;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.9);
        position: relative;
    }
    .ticket-header {
        background-color: #000;
        color: white;
        padding: 30px 20px 20px;
        text-align: center;
        border-bottom: 2px dashed #333;
        position: relative;
    }
    /* Recortes laterais do bilhete */
    .ticket-header::after, .ticket-header::before {
        content: ''; position: absolute; bottom: -10px; width: 20px; height: 20px;
        background-color: #212529; /* Cor do fundo do modal */
        border-radius: 50%;
    }
    .ticket-header::after { right: -10px; }
    .ticket-header::before { left: -10px; }

    .ticket-item-row {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }
    .ticket-body { padding: 25px; }
    .barcode {
        height: 40px;
        background: repeating-linear-gradient(90deg, #333, #333 2px, #fff 2px, #fff 4px);
        margin: 15px auto;
        width: 80%;
    }
</style>

<div class="container py-5">
    <h2 class="fw-bold text-white mb-4 border-start border-4 border-danger ps-3">Minha Carteira</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if (empty($groupedTickets)): ?>
        <div class="text-center py-5 text-secondary">
            <i class="bi bi-ticket-perforated display-1 mb-3 d-block opacity-25"></i>
            <h4>Ainda não comprou bilhetes</h4>
            <p>As suas compras aparecerão aqui.</p>
            <a href="/pages/movies.php" class="btn btn-outline-danger mt-3">Ver Filmes</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-lg-2 g-4">
            <?php foreach ($groupedTickets as $group): ?>
                <div class="col">
                    <div class="ticket-group-card d-flex align-items-center" onclick='openGroupModal(<?= htmlspecialchars(json_encode($group), ENT_QUOTES, "UTF-8") ?>)'>
                        <span class="count-badge"><?= count($group['bilhetes']) ?> Bilhetes</span>
                        <img src="<?= htmlspecialchars($group['poster']) ?>" alt="Poster" class="ticket-poster">
                    <div class="p-3 flex-grow-1">
                            <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($group['titulo']) ?></h5>
                            <p class="text-secondary mb-2 small"><i class="bi bi-calendar-event me-2 text-danger"></i><?= htmlspecialchars($group['data_sessao']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="text-white fw-bold fs-5"><?= number_format($group['total_preco'], 2, ',', '.') ?> €</span>
                                <button class="btn btn-sm btn-outline-light rounded-pill px-3">Ver Bilhetes</button>
                            </div>
                    </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Bilhete Digital -->
<div class="modal fade" id="ticketGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="digital-ticket">
                <div class="ticket-header">
                    <img src="<?= $base_url ?>/assets/logo.png" alt="CineHub" height="50" class="mb-3">
                    <h5 class="fw-bold m-0 text-uppercase text-white" style="letter-spacing: 1px;" id="tktTitle">Filme</h5>
                    <div class="mt-2 opacity-75 small">
                        <i class="bi bi-calendar me-1"></i><span id="tktDate">--/--/----</span>
                        <span class="mx-2">|</span>
                        <i class="bi bi-geo-alt me-1"></i>Sala 01
                    </div>
                </div>
                <div class="ticket-body">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Bilhetes</h6>
                    <div id="tktList">
                        <!-- Lista de bilhetes gerada via JS -->
                    </div>
                    
                    <div class="text-center">
                        <div class="barcode"></div>
                        <small class="text-muted">Apresente este código à entrada</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openGroupModal(group) {
        document.getElementById('tktTitle').innerText = group.titulo;
        document.getElementById('tktDate').innerText = group.data_sessao || 'Data N/A';
        
        const listContainer = document.getElementById('tktList');
        listContainer.innerHTML = '';

        group.bilhetes.forEach(ticket => {
            listContainer.innerHTML += `
                <div class="ticket-item-row d-flex justify-content-between align-items-center">
                    <div><span class="badge bg-dark text-white me-2">${ticket.assento}</span> <span class="fw-bold text-dark">${ticket.tipo}</span></div>
                    <small class="text-muted">#${ticket.id}</small>
                </div>
            `;
        });
        
        new bootstrap.Modal(document.getElementById('ticketGroupModal')).show();
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
