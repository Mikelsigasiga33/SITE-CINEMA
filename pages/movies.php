<?php
// 1. RESOLUÇÃO DE CAMINHOS
$paths = [
    dirname(__DIR__, 2) . '/config/db.php',
    dirname(__DIR__) . '/config/db.php',
    $_SERVER['DOCUMENT_ROOT'] . '/config/db.php'
];

$dbLoaded = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $dbLoaded = true;
        break;
    }
}

// 1.1 CORREÇÃO DE LINKS (Garante que base_url existe para evitar 404 nos links)
if (!isset($base_url)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // Ajusta isto se o teu site estiver numa subpasta
    $base_url = $protocol . "://" . $host; 
}

// Verifica se é um pedido AJAX (Pesquisa em tempo real)
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

// Só inclui o cabeçalho se NÃO for AJAX
if (!$isAjax) {
    include dirname(__DIR__) . '/includes/header.php';
}

// 2. VERIFICAÇÃO CRÍTICA
if (!$dbLoaded || !isset($pdo)) {
    die("<div class='alert alert-danger m-5'>
            <h3>Erro Crítico de Conexão</h3>
            <p>Configuração da base de dados não encontrada.</p>
         </div>");
}

// Inicializa arrays
$movies = [];
$highlightMovies = [];
$upcomingMovies = [];
$genresList = [];

// 3. BUSCA DE DADOS
try {
    // Buscar Géneros
    $stmtGenres = $pdo->query("SELECT DISTINCT GENERO FROM AAA_FILMES WHERE GENERO IS NOT NULL ORDER BY GENERO");
    $genresList = $stmtGenres->fetchAll(PDO::FETCH_COLUMN);

    // Buscar subscrições do utilizador (para bloquear botão se já notificado)
    $userSubscribedMovies = [];
    if (isset($_SESSION['user_id'])) {
        $stmtSub = $pdo->prepare("SELECT ID_FILME FROM AAA_NOTIFICACOES WHERE ID_UTILIZADOR = :user_id");
        $stmtSub->execute([':user_id' => $_SESSION['user_id']]);
        $userSubscribedMovies = $stmtSub->fetchAll(PDO::FETCH_COLUMN);
    }

    // Configurações de Filtro
    $sortOrder = isset($_GET['sort']) && $_GET['sort'] === 'desc' ? 'DESC' : 'ASC';
    $selectedGenres = isset($_GET['genres']) ? (is_array($_GET['genres']) ? $_GET['genres'] : [$_GET['genres']]) : [];
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
    $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'highlights';

    // Se houver pesquisa, força a aba "Todos os Filmes" para mostrar os resultados
    if (!empty($searchQuery)) {
        $activeTab = 'movies';
    }

    // Query Principal
    $sql = "SELECT ID_FILME, TITULO, IMG, DESCRICAO, DURACAO, CLASSIFICACAO, GENERO, LINK_TRAILER, ANO, PAIS, ATOR_PRINCIPAL, ELENCO, EM_DESTAQUE, EM_BREVE FROM AAA_FILMES WHERE 1=1";

    if (!empty($selectedGenres)) {
        $placeholders = [];
        foreach ($selectedGenres as $k => $g) {
            $placeholders[] = ":genre_$k";
        }
        $sql .= " AND GENERO IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($searchQuery)) {
        $sql .= " AND UPPER(TITULO) LIKE UPPER(:search)";
    }

    $sql .= " ORDER BY TITULO " . $sortOrder;

    $stmt = $pdo->prepare($sql);
    
    if (!empty($selectedGenres)) {
        foreach ($selectedGenres as $k => $g) {
            $stmt->bindValue(":genre_$k", $g);
        }
    }

    if (!empty($searchQuery)) {
        // Pesquisa por filmes que COMEÇAM com o termo (ex: 'T%')
        $stmt->bindValue(':search', $searchQuery . '%');
    }
    
    $stmt->execute();
    
    // 4. PROCESSAMENTO
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row = array_change_key_case($row, CASE_LOWER);

        if (isset($row['descricao']) && is_resource($row['descricao'])) {
            $row['descricao'] = stream_get_contents($row['descricao']);
        }

        // --- CORREÇÃO DO ERRO 404 NAS IMAGENS ---
        $fallbackImage = 'https://via.placeholder.com/300x450?text=Sem+Imagem';
        $posterUrl = $fallbackImage;
        
        if (!empty($row['img'])) {
            $cleanLink = str_replace(['https://', 'http://'], '', $row['img']);
            
            // ADICIONADO '&errorredirect': Se a imagem original não existir, 
            // o weserv redireciona para o placeholder em vez de dar erro 404.
            $posterUrl = "https://images.weserv.nl/?url=" . urlencode($cleanLink) . 
                         "&w=300&h=450&fit=cover&errorredirect=" . urlencode($fallbackImage);
        }
        // ----------------------------------------

        $movieFormatted = [
            'id' => $row['id_filme'],
            'titulo' => $row['titulo'],
            'poster' => $posterUrl,
            'sinopse' => $row['descricao'],
            'duracao' => ($row['duracao'] ?? '??') . ' min',
            'classificacao' => $row['classificacao'],
            'genero' => $row['genero'],
            'trailer' => $row['link_trailer'],
            'ano' => $row['ano'],
            'pais' => $row['pais'],
            'realizador' => 'N/A',
            'atores' => ($row['ator_principal'] ?? '') . ', ' . ($row['elenco'] ?? ''),
            'is_upcoming' => false,
            'em_destaque' => isset($row['em_destaque']) ? $row['em_destaque'] : 'N',
            'em_breve' => isset($row['em_breve']) ? $row['em_breve'] : 'N'
        ];

        $movies[] = $movieFormatted;

        if ($movieFormatted['em_destaque'] === 'S') {
            $highlightMovies[] = $movieFormatted;
        }

        if ($movieFormatted['em_breve'] === 'S') {
            $movieFormatted['is_upcoming'] = true;
            $movieFormatted['data_estreia'] = 'Brevemente';
            $upcomingMovies[] = $movieFormatted;
        }
    }

} catch (PDOException $e) {
    die("<div class='alert alert-danger m-5'>Erro na Base de Dados: " . $e->getMessage() . "</div>");
}

// --- RESPOSTA AJAX PARA PESQUISA EM TEMPO REAL ---
if ($isAjax) {
    if (empty($movies)) {
        echo '<div class="col-12 text-center text-muted py-5">Nenhum filme encontrado.</div>';
    } else {
        foreach ($movies as $movie) {
            $jsonMovie = htmlspecialchars(json_encode($movie), ENT_QUOTES, 'UTF-8');
            $poster = htmlspecialchars($movie['poster']);
            $title = htmlspecialchars($movie['titulo']);
            
            echo '<div class="col">';
            echo '<div class="movie-grid-card" onclick=\'window.location.href="movie_details.php?id=' . $movie['id'] . '"\'>';
            echo '<img src="' . $poster . '" alt="' . $title . '" class="movie-grid-poster">';
            echo '<div class="movie-grid-info"><h5 class="movie-grid-title">' . $title . '</h5></div>';
            echo '</div></div>';
        }
    }
    exit; // Interrompe o script para não carregar o resto da página
}
?>

<style>
    /* CSS MANTIDO */
    .movie-grid-card { background-color: #1f1f1f; border-radius: 8px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100%; cursor: pointer; }
    .movie-grid-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.5); }
    .movie-grid-poster { width: 100%; aspect-ratio: 2/3; object-fit: cover; }
    .movie-grid-info { padding: 15px; text-align: center; }
    .movie-grid-title { font-size: 1rem; font-weight: 600; margin: 0; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .modal-poster { width: 100%; border-radius: 8px; aspect-ratio: 2/3; object-fit: cover; }

    .nav-pills .nav-link { color: #aaa; border-radius: 50px; padding: 10px 30px; margin: 0 10px; transition: all 0.3s; border: none; }
    .nav-pills .nav-link::after { display: none !important; }
    .nav-pills .nav-link.active { background-color: #e50914; color: #fff; font-weight: bold; box-shadow: 0 4px 15px rgba(229, 9, 20, 0.4); }
    .nav-pills .nav-link:hover:not(.active) { background-color: rgba(255,255,255,0.1); color: #fff; }

    /* Estilos do Modal de Filtros Moderno */
    .sort-radio { display: none; }
    .sort-option {
        background-color: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        padding: 15px 20px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #ccc;
    }
    .sort-option:hover {
        background-color: rgba(255,255,255,0.1);
        color: #fff;
    }
    .sort-radio:checked + .sort-option {
        border-color: #e50914;
        background-color: rgba(229, 9, 20, 0.1);
        color: #fff;
    }
    .sort-option .check-icon { opacity: 0; transform: scale(0.5); transition: all 0.2s; }
    .sort-radio:checked + .sort-option .check-icon { opacity: 1; transform: scale(1); }

    .genre-checkbox { display: none; }
    .genre-pill {
        display: inline-block;
        padding: 8px 18px;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.2s;
        background-color: rgba(255,255,255,0.05);
        color: #aaa;
        user-select: none;
        font-size: 0.9rem;
    }
    .genre-pill:hover {
        background-color: rgba(255,255,255,0.15);
        color: #fff;
    }
    .genre-checkbox:checked + .genre-pill {
        background-color: #e50914;
        color: #fff;
        border-color: #e50914;
        box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
    }
    .hover-white:hover { color: #fff !important; }
</style>

<div class="container mt-4">
    <ul class="nav nav-pills justify-content-center mb-4" id="pills-tab" role="tablist">
        <li class="nav-item"><button class="nav-link <?= $activeTab === 'highlights' ? 'active' : '' ?>" id="pills-highlights-tab" data-bs-toggle="pill" data-bs-target="#pills-highlights" type="button" role="tab">Em Destaque</button></li>
        <li class="nav-item"><button class="nav-link <?= $activeTab === 'upcoming' ? 'active' : '' ?>" id="pills-upcoming-tab" data-bs-toggle="pill" data-bs-target="#pills-upcoming" type="button" role="tab">Em Breve</button></li>
        <li class="nav-item"><button class="nav-link <?= $activeTab === 'movies' ? 'active' : '' ?>" id="pills-movies-tab" data-bs-toggle="pill" data-bs-target="#pills-movies" type="button" role="tab">Todos os Filmes</button></li>
    </ul>
</div>

<div class="tab-content" id="pills-tabContent">
    
    <div class="tab-pane fade <?= $activeTab === 'highlights' ? 'show active' : '' ?>" id="pills-highlights">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold border-start border-4 border-danger ps-3">Filmes em Destaque</h2>
                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-sliders me-2"></i>Filtros e Ordenação</button>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                <?php if (empty($highlightMovies)): ?>
                    <div class="col-12 text-center text-muted py-5">Nenhum destaque encontrado.</div>
                <?php else: ?>
                    <?php foreach ($highlightMovies as $movie): ?>
                    <div class="col">
                        <div class="movie-grid-card" onclick='window.location.href="movie_details.php?id=<?= $movie["id"] ?>"'>
                            <img src="<?= htmlspecialchars($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>" class="movie-grid-poster">
                            <div class="movie-grid-info">
                                <h5 class="movie-grid-title"><?= htmlspecialchars($movie['titulo']) ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $activeTab === 'upcoming' ? 'show active' : '' ?>" id="pills-upcoming">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold border-start border-4 border-danger ps-3">Próximos Lançamentos</h2>
                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-sliders me-2"></i>Filtros e Ordenação</button>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                <?php if (empty($upcomingMovies)): ?>
                    <div class="col-12 text-center text-muted py-5">Nenhum lançamento encontrado.</div>
                <?php else: ?>
                    <?php foreach ($upcomingMovies as $movie): ?>
                    <div class="col">
                        <div class="movie-grid-card" onclick='window.location.href="movie_details.php?id=<?= $movie["id"] ?>"'>
                            <img src="<?= htmlspecialchars($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>" class="movie-grid-poster">
                            <div class="movie-grid-info">
                                <h5 class="movie-grid-title"><?= htmlspecialchars($movie['titulo']) ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $activeTab === 'movies' ? 'show active' : '' ?>" id="pills-movies">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold border-start border-4 border-danger ps-3">Catálogo de Filmes</h2>
                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-sliders me-2"></i>Filtros e Ordenação</button>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                <?php if (empty($movies)): ?>
                    <div class="col-12 text-center text-muted py-5">Nenhum filme encontrado.</div>
                <?php else: ?>
                    <?php foreach ($movies as $movie): ?>
                    <div class="col">
                        <div class="movie-grid-card" onclick='window.location.href="movie_details.php?id=<?= $movie["id"] ?>"'>
                            <img src="<?= htmlspecialchars($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>" class="movie-grid-poster">
                            <div class="movie-grid-info">
                                <h5 class="movie-grid-title"><?= htmlspecialchars($movie['titulo']) ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white border-secondary shadow-lg">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold"><i class="bi bi-sliders me-2 text-danger"></i>Filtros e Ordenação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="GET" action="">
                    <input type="hidden" name="tab" id="activeTabInput" value="<?= htmlspecialchars($activeTab) ?>">
                    
                    <div class="row mb-4">
                        <div class="col-12 mb-2">
                            <label class="form-label text-uppercase text-secondary fw-bold small">Ordenar por</label>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="radio" class="sort-radio" name="sort" value="asc" id="sortAsc" <?= $sortOrder === 'ASC' ? 'checked' : '' ?>>
                            <label class="sort-option w-100" for="sortAsc">
                                <span><i class="bi bi-sort-alpha-down me-2"></i>A - Z</span>
                                <i class="bi bi-check-circle-fill text-danger check-icon"></i>
                            </label>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="radio" class="sort-radio" name="sort" value="desc" id="sortDesc" <?= $sortOrder === 'DESC' ? 'checked' : '' ?>>
                            <label class="sort-option w-100" for="sortDesc">
                                <span><i class="bi bi-sort-alpha-down-alt me-2"></i>Z - A</span>
                                <i class="bi bi-check-circle-fill text-danger check-icon"></i>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-uppercase text-secondary fw-bold small mb-3">Géneros</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($genresList as $genre): ?>
                                <input type="checkbox" class="genre-checkbox" name="genres[]" value="<?= htmlspecialchars($genre) ?>" id="genre_<?= md5($genre) ?>" <?= in_array($genre, $selectedGenres) ? 'checked' : '' ?>>
                                <label class="genre-pill" for="genre_<?= md5($genre) ?>">
                                    <?= htmlspecialchars($genre) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top border-secondary">
                        <a href="movies.php?tab=<?= htmlspecialchars($activeTab) ?>" id="clearFiltersBtn" class="text-secondary text-decoration-none small hover-white">Limpar Filtros</a>
                        <button type="submit" class="btn btn-danger px-5 rounded-pill fw-bold">Aplicar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualiza o input hidden do formulário de filtros com a aba ativa atual
    const tabInput = document.getElementById('activeTabInput');
    const clearBtn = document.getElementById('clearFiltersBtn');
    const tabButtons = document.querySelectorAll('button[data-bs-toggle="pill"]');

    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(event) {
            const target = event.target.getAttribute('data-bs-target'); // ex: #pills-movies
            const tabName = target.replace('#pills-', '');
            if (tabInput) tabInput.value = tabName;
            if (clearBtn) clearBtn.href = 'movies.php?tab=' + tabName;
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

  
