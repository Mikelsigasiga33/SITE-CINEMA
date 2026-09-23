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

// Processar Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    try {
        if ($action === 'delete') {
            // Apagar sessão
            $stmt = $pdo->prepare("DELETE FROM AAA_SESSOES WHERE ID_SESSAO = :id");
            $stmt->execute([':id' => $id]);
            $msg = "Sessão removida com sucesso.";
        } elseif ($action === 'save') {
            $id_filme = $_POST['id_filme'];
            $sala = $_POST['sala'];
            $data_hora = $_POST['data_hora']; // Formato HTML: YYYY-MM-DDTHH:MM
            
            // Converter para formato Oracle (YYYY-MM-DD HH:MM)
            $data_hora_oracle = str_replace('T', ' ', $data_hora);

            if ($id > 0) {
                // Editar
                $sql = "UPDATE AAA_SESSOES SET ID_FILME = :fid, SALA = :sala, DATA_HORA = TO_DATE(:dh, 'YYYY-MM-DD HH24:MI') WHERE ID_SESSAO = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':fid' => $id_filme, ':sala' => $sala, ':dh' => $data_hora_oracle, ':id' => $id]);
                $msg = "Sessão atualizada com sucesso.";
            } else {
                // Adicionar
                $sql = "INSERT INTO AAA_SESSOES (ID_FILME, SALA, DATA_HORA) VALUES (:fid, :sala, TO_DATE(:dh, 'YYYY-MM-DD HH24:MI'))";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':fid' => $id_filme, ':sala' => $sala, ':dh' => $data_hora_oracle]);
                $msg = "Sessão agendada com sucesso.";
            }
        }
    } catch (PDOException $e) {
        $msg = "Erro: " . $e->getMessage();
    }
}

// Buscar Sessões
$search = $_GET['q'] ?? '';
$sessions = [];
try {
    $sql = "SELECT s.ID_SESSAO, s.SALA, TO_CHAR(s.DATA_HORA, 'YYYY-MM-DD HH24:MI') as DATA_HORA_FMT, f.TITULO, f.ID_FILME 
            FROM AAA_SESSOES s 
            JOIN AAA_FILMES f ON s.ID_FILME = f.ID_FILME 
            WHERE 1=1";
    
    $params = [];
    if (!empty($search)) {
        $sql .= " AND UPPER(f.TITULO) LIKE UPPER(:search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY s.DATA_HORA DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = "Erro ao carregar sessões: " . $e->getMessage();
}

// Buscar Filmes para o Dropdown
$movies = [];
try {
    $movies = $pdo->query("SELECT ID_FILME, TITULO FROM AAA_FILMES ORDER BY TITULO")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-white border-start border-4 border-success ps-3">Gerir Sessões</h2>
        <div class="d-flex align-items-center gap-2">
            <form class="d-flex" method="GET">
                <input class="form-control bg-dark text-white border-secondary me-2" type="search" name="q" placeholder="Pesquisar filme..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-light" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-2"></i>Voltar</a>
            <button class="btn btn-success" onclick="openSessionModal()"><i class="bi bi-plus-lg me-2"></i>Nova Sessão</button>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert" id="autoCloseAlert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    var alertEl = document.getElementById('autoCloseAlert');
                    if (alertEl && typeof bootstrap !== 'undefined') { new bootstrap.Alert(alertEl).close(); }
                }, 2000);
            });
        </script>
    <?php endif; ?>

    <div class="table-responsive bg-dark border border-secondary rounded">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="border-bottom border-secondary">
                    <th class="p-3">ID</th>
                    <th class="p-3">Filme</th>
                    <th class="p-3">Data e Hora</th>
                    <th class="p-3">Sala</th>
                    <th class="p-3 text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                <tr>
                    <td class="p-3">#<?= $s['ID_SESSAO'] ?></td>
                    <td class="p-3 fw-bold"><?= htmlspecialchars($s['TITULO']) ?></td>
                    <td class="p-3 text-warning"><i class="bi bi-clock me-2"></i><?= date('d/m/Y H:i', strtotime($s['DATA_HORA_FMT'])) ?></td>
                    <td class="p-3"><span class="badge bg-secondary">Sala <?= htmlspecialchars($s['SALA']) ?></span></td>
                    <td class="p-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick='openSessionModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, "UTF-8") ?>)'><i class="bi bi-pencil"></i></button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['ID_SESSAO'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('Tem a certeza? Se houver bilhetes vendidos, não será possível apagar.')"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Sessão -->
<div class="modal fade" id="sessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTitle">Nova Sessão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="sessionForm">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="sessao_id" value="0">
                    
                    <div class="mb-3"><label class="form-label text-secondary">Filme</label><select class="form-select bg-dark text-white border-secondary" name="id_filme" id="sessao_filme" required><option value="">Selecione um filme...</option><?php foreach ($movies as $m): ?><option value="<?= $m['ID_FILME'] ?>"><?= htmlspecialchars($m['TITULO']) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label text-secondary">Data e Hora</label><input type="datetime-local" class="form-control bg-dark text-white border-secondary" name="data_hora" id="sessao_data" required></div>
                    <div class="mb-3"><label class="form-label text-secondary">Sala</label><input type="number" class="form-control bg-dark text-white border-secondary" name="sala" id="sessao_sala" value="1" required></div>
                    
                    <div class="d-grid mt-4"><button type="submit" class="btn btn-success">Guardar Sessão</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openSessionModal(session = null) {
    document.getElementById('sessionForm').reset();
    if (session) {
        document.getElementById('modalTitle').innerText = 'Editar Sessão';
        document.getElementById('sessao_id').value = session.ID_SESSAO;
        document.getElementById('sessao_filme').value = session.ID_FILME;
        document.getElementById('sessao_data').value = session.DATA_HORA_FMT.replace(' ', 'T');
        document.getElementById('sessao_sala').value = session.SALA;
    } else { document.getElementById('modalTitle').innerText = 'Nova Sessão'; document.getElementById('sessao_id').value = 0; }
    new bootstrap.Modal(document.getElementById('sessionModal')).show();
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>