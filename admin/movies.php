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

// Processar Ações (Adicionar, Editar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    try {
        if ($action === 'delete') {
            // Apagar filme
            // Nota: Se houver sessões ou bilhetes associados, pode dar erro de integridade (FK).
            // O ideal seria apagar dependências primeiro, mas aqui tentamos apagar o filme direto.
            $stmt = $pdo->prepare("DELETE FROM AAA_FILMES WHERE ID_FILME = :id");
            $stmt->execute([':id' => $id]);
            $msg = "Filme removido com sucesso.";
        } elseif ($action === 'save') {
            // Dados do formulário
            $titulo = $_POST['titulo'];
            $genero = $_POST['genero'];
            $duracao = $_POST['duracao'];
            $ano = $_POST['ano'];
            $classificacao = $_POST['classificacao'];
            $pais = $_POST['pais'];
            $trailer = $_POST['trailer'];
            $img = $_POST['img'];
            $destaque = isset($_POST['destaque']) ? 'S' : 'N';
            $em_breve = isset($_POST['em_breve']) ? 'S' : 'N';
            $descricao = $_POST['descricao'];
            $ator_principal = $_POST['ator_principal'];
            $elenco = $_POST['elenco'];

            if ($id > 0) {
                // Editar Existente
                $sql = "UPDATE AAA_FILMES SET TITULO = :titulo, GENERO = :genero, DURACAO = :duracao, ANO = :ano, 
                        CLASSIFICACAO = :classificacao, PAIS = :pais, LINK_TRAILER = :trailer, IMG = :img, EM_DESTAQUE = :destaque, 
                        EM_BREVE = :em_breve, DESCRICAO = :descricao, ATOR_PRINCIPAL = :ator_principal, ELENCO = :elenco 
                        WHERE ID_FILME = :id";
                $params = [
                    ':titulo' => $titulo, ':genero' => $genero, ':duracao' => $duracao, ':ano' => $ano, 
                    ':classificacao' => $classificacao, ':pais' => $pais, ':trailer' => $trailer, ':img' => $img, 
                    ':destaque' => $destaque, ':em_breve' => $em_breve, ':descricao' => $descricao, ':ator_principal' => $ator_principal, ':elenco' => $elenco,
                    ':id' => $id
                ];
                $msg = "Filme atualizado com sucesso.";
            } else {
                // Adicionar Novo
                $sql = "INSERT INTO AAA_FILMES (TITULO, GENERO, DURACAO, ANO, CLASSIFICACAO, PAIS, LINK_TRAILER, IMG, EM_DESTAQUE, EM_BREVE, DESCRICAO, ATOR_PRINCIPAL, ELENCO) 
                        VALUES (:titulo, :genero, :duracao, :ano, :classificacao, :pais, :trailer, :img, :destaque, :em_breve, :descricao, :ator_principal, :elenco)";
                $params = [
                    ':titulo' => $titulo, ':genero' => $genero, ':duracao' => $duracao, ':ano' => $ano, 
                    ':classificacao' => $classificacao, ':pais' => $pais, ':trailer' => $trailer, ':img' => $img, 
                    ':destaque' => $destaque, ':em_breve' => $em_breve, ':descricao' => $descricao, ':ator_principal' => $ator_principal, ':elenco' => $elenco
                ];
                $msg = "Filme adicionado com sucesso.";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
    } catch (PDOException $e) {
        $msg = "Erro: " . $e->getMessage();
    }
}

// Buscar Filmes
$search = $_GET['q'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // Filtro das abas
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
$movies = [];
try {
    $sql = "SELECT * FROM AAA_FILMES";
    $whereClauses = [];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "UPPER(TITULO) LIKE UPPER(:search)";
        $params[':search'] = $search . '%';
    }
    
    if ($filter === 'highlights') {
        $whereClauses[] = "EM_DESTAQUE = 'S'";
    } elseif ($filter === 'upcoming') {
        $whereClauses[] = "EM_BREVE = 'S'";
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    $sql .= " ORDER BY ID_FILME DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Tratamento de CLOB para descrição
        if (isset($row['DESCRICAO']) && is_resource($row['DESCRICAO'])) {
            $row['DESCRICAO'] = stream_get_contents($row['DESCRICAO']);
        }
        $movies[] = $row;
    }
} catch (PDOException $e) {
    $msg = "Erro ao carregar filmes.";
}

if ($isAjax) {
    foreach ($movies as $m) {
        echo '<tr>';
        echo '<td class="p-3">#' . $m['ID_FILME'] . '</td>';
        echo '<td class="p-3"><img src="' . htmlspecialchars($m['IMG']) . '" style="height: 50px; width: 35px; object-fit: cover; border-radius: 4px;"></td>';
        echo '<td class="p-3 fw-bold">' . htmlspecialchars($m['TITULO']) . '</td>';
        echo '<td class="p-3">' . htmlspecialchars($m['GENERO']) . '</td>';
        echo '<td class="p-3">' . htmlspecialchars($m['ANO']) . '</td>';
        echo '<td class="p-3 text-center">';
        if ($m['EM_DESTAQUE'] === 'S') {
            echo '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            echo '<span class="text-secondary">-</span>';
        }
        if (isset($m['EM_BREVE']) && $m['EM_BREVE'] === 'S') {
            echo '<i class="bi bi-clock-history text-info ms-2" title="Em Breve"></i>';
        }
        echo '</td>';
        echo '<td class="p-3 text-end">';
        echo '<button type="button" class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick=\'openMovieModal(' . htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8") . ')\'><i class="bi bi-pencil"></i></button>';
        echo '<form method="POST" class="d-inline">';
        echo '<input type="hidden" name="action" value="delete">';
        echo '<input type="hidden" name="id" value="' . $m['ID_FILME'] . '">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm(\'Tem a certeza que deseja eliminar este filme?\')"><i class="bi bi-trash"></i></button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    exit;
}

include dirname(__DIR__) . '/includes/header.php';
?>

<style>
    .nav-pills .nav-link { color: #aaa; border-radius: 50px; padding: 8px 20px; margin-right: 10px; transition: all 0.3s; }
    .nav-pills .nav-link.active { background-color: #e50914; color: #fff; font-weight: bold; }
    .nav-pills .nav-link:hover:not(.active) { background-color: rgba(255,255,255,0.1); color: #fff; }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-white border-start border-4 border-danger ps-3">Gerir Filmes</h2>
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex">
                <input id="adminSearchInput" class="form-control bg-dark text-white border-secondary me-2" type="search" name="q" placeholder="Pesquisar..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            </div>
            <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-2"></i>Voltar</a>
            <button class="btn btn-danger" onclick="openMovieModal()"><i class="bi bi-plus-lg me-2"></i>Novo Filme</button>
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
                    if (alertEl && typeof bootstrap !== 'undefined') {
                        new bootstrap.Alert(alertEl).close();
                    }
                }, 2000);
            });
        </script>
    <?php endif; ?>

    <!-- Abas de Filtro -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item"><a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="?filter=all">Todos</a></li>
        <li class="nav-item"><a class="nav-link <?= $filter === 'highlights' ? 'active' : '' ?>" href="?filter=highlights">Em Destaque</a></li>
        <li class="nav-item"><a class="nav-link <?= $filter === 'upcoming' ? 'active' : '' ?>" href="?filter=upcoming">Em Breve</a></li>
    </ul>

    <div class="table-responsive bg-dark border border-secondary rounded">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="border-bottom border-secondary">
                    <th class="p-3">ID</th>
                    <th class="p-3" style="width: 60px;">Poster</th>
                    <th class="p-3">Título</th>
                    <th class="p-3">Género</th>
                    <th class="p-3">Ano</th>
                    <th class="p-3 text-center">Estado</th>
                    <th class="p-3 text-end">Ações</th>
                </tr>
            </thead>
            <tbody id="moviesTableBody">
                <?php foreach ($movies as $m): ?>
                <tr>
                    <td class="p-3">#<?= $m['ID_FILME'] ?></td>
                    <td class="p-3"><img src="<?= htmlspecialchars($m['IMG']) ?>" style="height: 50px; width: 35px; object-fit: cover; border-radius: 4px;"></td>
                    <td class="p-3 fw-bold"><?= htmlspecialchars($m['TITULO']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($m['GENERO']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($m['ANO']) ?></td>
                    <td class="p-3 text-center">
                        <?php if ($m['EM_DESTAQUE'] === 'S'): ?>
                            <i class="bi bi-star-fill text-warning" title="Destaque"></i>
                        <?php else: ?>
                            <span class="text-secondary">-</span>
                        <?php endif; ?>
                        <?php if (isset($m['EM_BREVE']) && $m['EM_BREVE'] === 'S'): ?>
                            <i class="bi bi-clock-history text-info ms-2" title="Em Breve"></i>
                        <?php endif; ?>
                    </td>
                    <td class="p-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick='openMovieModal(<?= htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8") ?>)'><i class="bi bi-pencil"></i></button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['ID_FILME'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('Tem a certeza que deseja eliminar este filme?')"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Adicionar/Editar Filme -->
<div class="modal fade" id="movieModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTitle">Novo Filme</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="movieForm">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="movie_id" value="0">
                    
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label text-secondary">Título</label><input type="text" class="form-control bg-dark text-white border-secondary" name="titulo" id="movie_titulo" required></div>
                        <div class="col-md-4"><label class="form-label text-secondary">Ano</label><input type="number" class="form-control bg-dark text-white border-secondary" name="ano" id="movie_ano" required></div>
                        
                        <div class="col-md-6"><label class="form-label text-secondary">Género</label><input type="text" class="form-control bg-dark text-white border-secondary" name="genero" id="movie_genero"></div>
                        <div class="col-md-3"><label class="form-label text-secondary">Duração (min)</label><input type="number" class="form-control bg-dark text-white border-secondary" name="duracao" id="movie_duracao"></div>
                        <div class="col-md-3"><label class="form-label text-secondary">Classificação</label><input type="text" class="form-control bg-dark text-white border-secondary" name="classificacao" id="movie_classificacao"></div>
                        
                        <div class="col-md-6"><label class="form-label text-secondary">País</label><input type="text" class="form-control bg-dark text-white border-secondary" name="pais" id="movie_pais"></div>
                        <div class="col-md-6"><label class="form-label text-secondary">Ator Principal</label><input type="text" class="form-control bg-dark text-white border-secondary" name="ator_principal" id="movie_ator_principal"></div>
                        
                        <div class="col-12"><label class="form-label text-secondary">Elenco Completo</label><input type="text" class="form-control bg-dark text-white border-secondary" name="elenco" id="movie_elenco"></div>
                        
                        <div class="col-12"><label class="form-label text-secondary">URL da Imagem (Poster)</label><input type="text" class="form-control bg-dark text-white border-secondary" name="img" id="movie_img" placeholder="https://..."></div>
                        <div class="col-12"><label class="form-label text-secondary">Link do Trailer (YouTube)</label><input type="text" class="form-control bg-dark text-white border-secondary" name="trailer" id="movie_trailer"></div>
                        
                        <div class="col-12"><label class="form-label text-secondary">Sinopse</label><textarea class="form-control bg-dark text-white border-secondary" name="descricao" id="movie_descricao" rows="3"></textarea></div>
                        
                        <div class="col-12 d-flex gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="destaque" id="movie_destaque">
                                <label class="form-check-label text-white" for="movie_destaque">Colocar em Destaque</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="em_breve" id="movie_em_breve">
                                <label class="form-check-label text-white" for="movie_em_breve">Marcar como "Em Breve"</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid mt-4"><button type="submit" class="btn btn-danger">Guardar Filme</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openMovieModal(movie = null) {
    const form = document.getElementById('movieForm');
    form.reset(); // Limpa o formulário
    
    if (movie) {
        document.getElementById('modalTitle').innerText = 'Editar Filme';
        document.getElementById('movie_id').value = movie.ID_FILME;
        document.getElementById('movie_titulo').value = movie.TITULO;
        document.getElementById('movie_ano').value = movie.ANO;
        document.getElementById('movie_genero').value = movie.GENERO;
        document.getElementById('movie_duracao').value = movie.DURACAO;
        document.getElementById('movie_classificacao').value = movie.CLASSIFICACAO;
        document.getElementById('movie_pais').value = movie.PAIS;
        document.getElementById('movie_ator_principal').value = movie.ATOR_PRINCIPAL;
        document.getElementById('movie_elenco').value = movie.ELENCO;
        document.getElementById('movie_img').value = movie.IMG;
        document.getElementById('movie_trailer').value = movie.LINK_TRAILER;
        document.getElementById('movie_descricao').value = movie.DESCRICAO;
        document.getElementById('movie_destaque').checked = (movie.EM_DESTAQUE === 'S');
        document.getElementById('movie_em_breve').checked = (movie.EM_BREVE === 'S');
    } else {
        document.getElementById('modalTitle').innerText = 'Novo Filme';
        document.getElementById('movie_id').value = 0;
    }
    
    new bootstrap.Modal(document.getElementById('movieModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('adminSearchInput');
    const tableBody = document.getElementById('moviesTableBody');

    searchInput.addEventListener('input', function() {
        const query = this.value;
        const url = new URL(window.location);
        query ? url.searchParams.set('q', query) : url.searchParams.delete('q');
        window.history.pushState({}, '', url);

        fetch(`movies.php?q=${encodeURIComponent(query)}&ajax=1`)
            .then(response => response.text())
            .then(html => { tableBody.innerHTML = html; });
    });
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>