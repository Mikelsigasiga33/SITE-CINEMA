<?php
require dirname(__DIR__) . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Garante que base_url existe
if (!isset($base_url)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . "://" . $host; 
}

$movieId = $_GET['id'] ?? 0;

if (!$movieId) {
    header("Location: movies.php");
    exit;
}

// Buscar detalhes do filme
try {
    $stmt = $pdo->prepare("SELECT * FROM AAA_FILMES WHERE ID_FILME = :id");
    $stmt->execute([':id' => $movieId]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$movie) {
        die("Filme não encontrado.");
    }
    
    $movie = array_change_key_case($movie, CASE_LOWER);
    if (isset($movie['descricao']) && is_resource($movie['descricao'])) {
        $movie['descricao'] = stream_get_contents($movie['descricao']);
    }
    
    // Tratamento de imagem
    $fallbackImage = 'https://via.placeholder.com/300x450?text=Sem+Imagem';
    $posterUrl = $fallbackImage;
    if (!empty($movie['img'])) {
        $cleanLink = str_replace(['https://', 'http://'], '', $movie['img']);
        $posterUrl = "https://images.weserv.nl/?url=" . urlencode($cleanLink) . "&w=300&h=450&fit=cover&errorredirect=" . urlencode($fallbackImage);
    }
    $movie['poster'] = $posterUrl;
    
    // Verificar subscrição
    $isSubscribed = false;
    if (isset($_SESSION['user_id'])) {
        $stmtSub = $pdo->prepare("SELECT 1 FROM AAA_NOTIFICACOES WHERE ID_UTILIZADOR = :user_id AND ID_FILME = :movie_id");
        $stmtSub->execute([':user_id' => $_SESSION['user_id'], ':movie_id' => $movieId]);
        if ($stmtSub->fetch()) {
            $isSubscribed = true;
        }
    }

    // Lógica de Exibição: Bilheteira vs Notificação
    // Se o filme estiver marcado como "Em Breve", mostra a notificação. Caso contrário, mostra a bilheteira.
    $showBoxOffice = !(isset($movie['em_breve']) && $movie['em_breve'] === 'S');

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

include dirname(__DIR__) . '/includes/header.php';
?>

<style>
    /* Estilos específicos para a página de detalhes */
    .movie-backdrop {
        position: relative;
        height: 400px;
        background: linear-gradient(to bottom, rgba(18,18,18,0.3), #121212), url('<?= $movie['poster'] ?>');
        background-size: cover;
        background-position: center;
        margin-bottom: -100px;
        filter: blur(20px) brightness(0.5);
        z-index: -1;
    }
    .movie-poster-card {
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        width: 100%;
    }
    
    /* Novos Estilos - Layout Cinemas NOS */
    .nos-yellow-text { color: #e50914; }
    .nos-yellow-bg { background-color: #e50914; color: #fff; }
    .nos-yellow-border { border-color: #e50914 !important; }
    
    .date-nav {
        display: flex;
        overflow-x: auto;
        gap: 10px;
        padding-bottom: 15px;
        border-bottom: 1px solid #333;
        margin-bottom: 20px;
        scrollbar-width: thin;
        scrollbar-color: #555 transparent;
    }
    .date-nav::-webkit-scrollbar { height: 4px; }
    .date-nav::-webkit-scrollbar-thumb { background: #555; border-radius: 2px; }
    
    .date-item {
        min-width: 70px;
        text-align: center;
        cursor: pointer;
        padding: 10px 5px;
        border-radius: 8px;
        transition: all 0.2s ease;
        color: #aaa;
        border: 1px solid transparent;
    }
    .date-item:hover { color: #fff; background: rgba(255,255,255,0.05); }
    .date-item.active {
        color: #fff;
        background-color: #e50914;
        font-weight: bold;
        box-shadow: 0 0 15px rgba(229, 9, 20, 0.4);
    }
    .date-item .day-name { font-size: 0.75rem; text-transform: uppercase; display: block; margin-bottom: 2px; }
    .date-item .day-num { font-size: 1.2rem; line-height: 1; }
    
    .region-filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
    .region-pill {
        border: 1px solid #444; color: #aaa; padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; background: transparent;
    }
    .region-pill:hover { border-color: #e50914; color: #fff; }
    .region-pill.active { background-color: #e50914; color: #fff; border-color: #e50914; font-weight: bold; }
    
    .cinema-select { background-color: #1a1a1a; border: 1px solid #333; color: #fff; padding: 12px; border-radius: 8px; width: 100%; cursor: pointer; }
    .cinema-select:focus { border-color: #e50914; box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25); }
    
    .session-block { border-bottom: 1px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
    .session-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    
    .format-badge { background-color: #222; color: #eee; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; margin-right: 10px; border: 1px solid #444; font-weight: 600; }
    
    .time-btn {
        background-color: #1a1a1a; border: 1px solid #444; color: #e0e0e0; padding: 8px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; margin-right: 8px; margin-bottom: 8px; transition: all 0.2s; font-size: 0.9rem;
    }
    .time-btn:hover { background-color: #e50914; color: #fff; border-color: #e50914; transform: translateY(-2px); }
</style>

<div class="movie-backdrop"></div>

<div class="container pb-5" style="margin-top: -50px;">
    <!-- Detalhes do Filme -->
    <div class="row mb-5">
        <div class="col-md-4 col-lg-3 mb-4 mb-md-0">
            <img src="<?= $movie['poster'] ?>" class="movie-poster-card img-fluid" alt="<?= htmlspecialchars($movie['titulo']) ?>">
            <?php if ($movie['link_trailer']): ?>
                <a href="<?= htmlspecialchars($movie['link_trailer']) ?>" target="_blank" class="btn btn-danger w-100 mt-3 py-2 fw-bold shadow"><i class="bi bi-play-circle me-2"></i>Ver Trailer</a>
            <?php endif; ?>
        </div>
        <div class="col-md-8 col-lg-9 text-white pt-md-5">
            <h1 class="fw-bold display-4 mb-2"><?= htmlspecialchars($movie['titulo']) ?></h1>
            
            <!-- 1. Sistema de Classificação -->
            <div class="d-flex align-items-center mb-4">
                <div class="text-warning me-2">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                </div>
                <span class="fw-bold fs-5">4.5</span>
                <span class="text-secondary ms-2 small">/ 5 (Avaliação do Público)</span>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="badge bg-danger"><?= htmlspecialchars($movie['classificacao']) ?></span>
                <span class="badge bg-dark border border-secondary"><?= htmlspecialchars($movie['duracao']) ?> min</span>
                <span class="badge bg-dark border border-secondary"><?= htmlspecialchars($movie['genero']) ?></span>
                <span class="badge bg-dark border border-secondary"><?= htmlspecialchars($movie['ano']) ?></span>
            </div>
            
            <h5 class="text-danger fw-bold mb-2">Sinopse</h5>
            <p class="text-white lead fs-6 mb-4"><?= nl2br(htmlspecialchars($movie['descricao'])) ?></p>
            
            <!-- 2. Informação Técnica Detalhada -->
            <h5 class="text-danger fw-bold mb-3">Ficha Técnica</h5>
            <div class="row g-3 mb-4 text-secondary">
                <div class="col-6 col-md-3">
                    <small class="d-block text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Realizador</small>
                    <span class="text-white">Christopher Nolan</span> <!-- Placeholder -->
                </div>
                <div class="col-6 col-md-3">
                    <small class="d-block text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">País</small>
                    <span class="text-white"><?= htmlspecialchars($movie['pais']) ?></span>
                </div>
                <div class="col-6 col-md-3">
                    <small class="d-block text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Estúdio</small>
                    <span class="text-white">Warner Bros.</span> <!-- Placeholder -->
                </div>
                <div class="col-6 col-md-3">
                    <small class="d-block text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">Orçamento</small>
                    <span class="text-white">$165M</span> <!-- Placeholder -->
                </div>
            </div>

            <!-- 3. Elenco Principal (Cast Cards) -->
            <h5 class="text-danger fw-bold mb-3">Elenco Principal</h5>
            <div class="d-flex flex-wrap gap-3 mb-5">
                <?php 
                $actors = array_filter(array_unique(array_merge([$movie['ator_principal']], explode(',', $movie['elenco']))));
                foreach(array_slice($actors, 0, 5) as $actor): 
                    $actor = trim($actor);
                    if(empty($actor)) continue;
                    // Busca foto dinâmica via pesquisa (Thumbnail automático)
                    $actorImg = "https://tse2.mm.bing.net/th?q=" . urlencode($actor . " actor") . "&w=100&h=100&c=7&rs=1&p=0";
                ?>
                <a href="https://www.google.com/search?q=<?= urlencode($actor) ?>" target="_blank" class="text-decoration-none">
                    <div class="d-flex align-items-center bg-dark border border-secondary rounded-pill pe-3 ps-1 py-1" style="transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="<?= $actorImg ?>" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;" alt="<?= htmlspecialchars($actor) ?>">
                        <span class="text-white small"><?= htmlspecialchars($actor) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Secção de Bilheteira -->
    <div class="row">
        <div class="col-12">
            <div class="bg-dark p-4 p-md-5 rounded-4 border border-secondary shadow-lg">
                <?php if ($showBoxOffice): ?>
                    <!-- MODO BILHETEIRA (Filmes em Cartaz / Destaques) -->
                    <h3 class="text-white fw-bold mb-4 border-start border-4 nos-yellow-border ps-3">Bilheteira</h3>
                    
                    <!-- 1. Navegação de Datas -->
                    <div class="date-nav" id="dateNav">
                        <!-- Gerado via JS -->
                    </div>

                    <!-- 2. Filtros de Região -->
                    <div class="region-filters">
                        <button class="region-pill active">Grande Porto</button>
                    </div>
                    
                    <div class="row g-5">
                        <!-- 4. Listagem de Sessões (Largura Total) -->
                        <div class="col-12">
                            <div id="sessionsList">
                                <!-- Conteúdo gerado via JS -->
                                <div class="text-center py-5 text-secondary">
                                    <div class="spinner-border text-danger mb-3" role="status"></div>
                                    <p>A carregar sessões...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- MODO NOTIFICAÇÃO (Filmes Em Breve) -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-calendar-event display-1 text-secondary opacity-25"></i>
                        </div>
                        <h3 class="text-white fw-bold mb-3">Estreia Brevemente</h3>
                        <p class="text-secondary mb-4 fs-5">Este filme ainda não tem sessões disponíveis.<br>Ative a notificação para saber quando os bilhetes estiverem à venda.</p>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button id="btnNotify" class="btn <?= $isSubscribed ? 'btn-success' : 'btn-outline-light' ?> btn-lg rounded-pill px-5 py-3 fw-bold" onclick="toggleNotification()" <?= $isSubscribed ? 'disabled' : '' ?>>
                                <?php if ($isSubscribed): ?>
                                    <i class="bi bi-check-circle-fill me-2"></i>Notificação Ativada
                                <?php else: ?>
                                    <i class="bi bi-bell me-2"></i>Notificar-me
                                <?php endif; ?>
                            </button>
                        <?php else: ?>
                            <a href="<?= $base_url ?>/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-danger btn-lg rounded-pill px-5 py-3 fw-bold">
                                <i class="bi bi-person me-2"></i>Faça login para Receber Alerta
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = "<?= $base_url ?>";
    const movieId = <?= $movieId ?>;
    const movieTitle = "<?= addslashes($movie['titulo']) ?>";

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('dateNav')) {
            renderDateNav();
            updateSessions(); // Carrega sessões iniciais
        }
    });

    function renderDateNav() {
        const nav = document.getElementById('dateNav');
        nav.innerHTML = "";
        const today = new Date();
        const weekDays = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
        const months = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"]; // Para uso futuro se necessário

        for (let i = 0; i < 10; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            
            let dayName = weekDays[date.getDay()];
            if (i === 0) dayName = "Hoje";
            if (i === 1) dayName = "Amanhã";
            
            const dayNum = date.getDate();

            const div = document.createElement('div');
            div.className = `date-item ${i === 0 ? 'active' : ''}`;
            div.innerHTML = `<span class="day-name">${dayName}</span><span class="day-num">${dayNum}</span>`;
            
            div.onclick = () => {
                document.querySelectorAll('.date-item').forEach(d => d.classList.remove('active'));
                div.classList.add('active');
                updateSessions(date);
            };
            nav.appendChild(div);
        }
    }
    
    function filterRegion(region, btn) {
        document.querySelectorAll('.region-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        // Aqui poderia filtrar o dropdown de cinemas
        updateSessions();
    }

    function updateSessions(date = new Date()) {
        const container = document.getElementById('sessionsList');
        // Simulação de carregamento
        container.innerHTML = '<div class="text-center py-5 text-secondary"><div class="spinner-border text-danger mb-3"></div><p>A atualizar horários...</p></div>';
        
        setTimeout(() => {
            // Mock de dados - Em produção viria de AJAX
            // Gera um número de sala aleatório entre 1 e 12
            const randomRoom = Math.floor(Math.random() * 12) + 1;
            
            container.innerHTML = `
                <div class="session-block">
                    <h5 class="text-white fw-bold mb-2">CinemaHub Porto (Aliados)</h5>
                    <div class="d-flex align-items-center flex-wrap mb-3">
                        <span class="format-badge">IMAX Laser</span>
                        <span class="text-secondary small">Sala ${randomRoom}</span>
                    </div>
                    <div>
                        <a href="${baseUrl}/pages/seat_selection.php?movie_id=${movieId}&time=14:30&title=${encodeURIComponent(movieTitle)}" class="time-btn">14:30</a>
                        <a href="${baseUrl}/pages/seat_selection.php?movie_id=${movieId}&time=17:45&title=${encodeURIComponent(movieTitle)}" class="time-btn">17:45</a>
                        <a href="${baseUrl}/pages/seat_selection.php?movie_id=${movieId}&time=21:30&title=${encodeURIComponent(movieTitle)}" class="time-btn">21:30</a>
                    </div>
                </div>
            `;
        }, 300);
    }

    function toggleNotification() {
        const btn = document.getElementById('btnNotify');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A processar...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('movie_id', movieId);

        fetch(`${baseUrl}/auth/subscribe_notification.php`, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Notificação Ativada';
                btn.className = 'btn btn-success btn-lg rounded-pill px-5 py-3 fw-bold';
            } else {
                btn.innerHTML = originalContent;
                btn.disabled = false;
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = originalContent;
            btn.disabled = false;
        });
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>