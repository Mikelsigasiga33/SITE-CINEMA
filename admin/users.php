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

// Processar Ações
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($id) {
        try {
            if ($action === 'delete') {
                // 1. Apagar Notificações (Dependência)
                $stmt = $pdo->prepare("DELETE FROM AAA_NOTIFICACOES WHERE ID_UTILIZADOR = :id");
                $stmt->execute([':id' => $id]);

                // 2. Apagar Bilhetes (Dependência)
                $stmt = $pdo->prepare("DELETE FROM AAA_BILHETES WHERE ID_CLIENTE = :id");
                $stmt->execute([':id' => $id]);

                // 3. Apagar Utilizador
                $stmt = $pdo->prepare("DELETE FROM AAA_UTILIZADORES WHERE ID_UTILIZADOR = :id");
                $stmt->execute([':id' => $id]);
                $msg = "Utilizador e todos os seus dados associados foram removidos.";
            } elseif ($action === 'promote') {
                $stmt = $pdo->prepare("UPDATE AAA_UTILIZADORES SET TIPO_PERMISSAO = 'Administrador' WHERE ID_UTILIZADOR = :id");
                $stmt->execute([':id' => $id]);
                $msg = "Utilizador promovido a Administrador.";
            } elseif ($action === 'demote') {
                $stmt = $pdo->prepare("UPDATE AAA_UTILIZADORES SET TIPO_PERMISSAO = 'Cliente' WHERE ID_UTILIZADOR = :id");
                $stmt->execute([':id' => $id]);
                $msg = "Utilizador despromovido a Cliente.";
            } elseif ($action === 'edit') {
                $nome = trim($_POST['nome']);
                $segundo_nome = trim($_POST['segundo_nome']);
                $email = trim($_POST['email']);
                $telemovel = trim($_POST['telemovel']);
                
                $stmt = $pdo->prepare("UPDATE AAA_UTILIZADORES SET NOME = :nome, SEGUNDO_NOME = :segundo_nome, EMAIL = :email, N_TELEMOVEL = :telemovel WHERE ID_UTILIZADOR = :id");
                $stmt->execute([':nome' => $nome, ':segundo_nome' => $segundo_nome, ':email' => $email, ':telemovel' => $telemovel, ':id' => $id]);
                $msg = "Dados do utilizador atualizados com sucesso.";
            }
        } catch (PDOException $e) {
            $msg = "Erro: " . $e->getMessage();
        }
    }
}

// Buscar Utilizadores
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM AAA_UTILIZADORES ORDER BY ID_UTILIZADOR DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = "Erro ao carregar utilizadores.";
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-white border-start border-4 border-primary ps-3">Gerir Utilizadores</h2>
        <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-2"></i>Voltar</a>
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
                    if (alertEl && typeof bootstrap !== 'undefined') {
                        var alert = new bootstrap.Alert(alertEl);
                        alert.close();
                    }
                }, 2000);
            });
        </script>
    <?php endif; ?>

    <div class="table-responsive bg-dark border border-secondary rounded">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="border-bottom border-secondary">
                    <th class="p-3">ID</th>
                    <th class="p-3">Nome</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Telemóvel</th>
                    <th class="p-3">Permissão</th>
                    <th class="p-3 text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="p-3">#<?= $u['ID_UTILIZADOR'] ?></td>
                    <td class="p-3 fw-bold"><?= htmlspecialchars($u['NOME'] . ' ' . ($u['SEGUNDO_NOME'] ?? '')) ?></td>
                    <td class="p-3"><?= htmlspecialchars($u['EMAIL']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($u['N_TELEMOVEL']) ?></td>
                    <td class="p-3">
                        <?php if (strtoupper($u['TIPO_PERMISSAO']) === 'ADMINISTRADOR'): ?>
                            <span class="badge bg-warning text-dark">ADMINISTRADOR</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Cliente</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick='openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, "UTF-8") ?>)'><i class="bi bi-pencil"></i></button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= $u['ID_UTILIZADOR'] ?>">
                            <?php if (strtoupper($u['TIPO_PERMISSAO']) !== 'ADMINISTRADOR'): ?>
                                <button type="submit" name="action" value="promote" class="btn btn-sm btn-outline-warning me-1" title="Promover a Admin"><i class="bi bi-arrow-up-circle"></i></button>
                            <?php else: ?>
                                <button type="submit" name="action" value="demote" class="btn btn-sm btn-outline-secondary me-1" title="Despromover"><i class="bi bi-arrow-down-circle"></i></button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('Tem a certeza? Esta ação é irreversível.')"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Editar Utilizador -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Editar Utilizador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary">Nome</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="nome" id="edit_nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Apelido</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="segundo_nome" id="edit_segundo_nome">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Email</label>
                        <input type="email" class="form-control bg-dark text-white border-secondary" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Telemóvel</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="telemovel" id="edit_telemovel">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Guardar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit_id').value = user.ID_UTILIZADOR;
    document.getElementById('edit_nome').value = user.NOME;
    document.getElementById('edit_segundo_nome').value = user.SEGUNDO_NOME || '';
    document.getElementById('edit_email').value = user.EMAIL;
    document.getElementById('edit_telemovel').value = user.N_TELEMOVEL || '';
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>