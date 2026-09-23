<?php
require __DIR__ . '/config/db.php';

// Buscar Top 10 Filmes da Base de Dados
$top10Movies = [];
try {
    // Consulta para obter 10 filmes (compatível com Oracle usando ROWNUM)
    // Ordenado por Título para exemplo, mas poderia ser por classificação
    $sql = "SELECT * FROM (
                SELECT ID_FILME, TITULO, IMG, DESCRICAO, DURACAO, CLASSIFICACAO, GENERO, LINK_TRAILER, ANO, PAIS, ATOR_PRINCIPAL, ELENCO 
                FROM AAA_FILMES 
                ORDER BY TITULO ASC
            ) WHERE ROWNUM <= 10";
            
    $stmt = $pdo->query($sql);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row = array_change_key_case($row, CASE_LOWER);

        if (isset($row['descricao']) && is_resource($row['descricao'])) {
            $row['descricao'] = stream_get_contents($row['descricao']);
        }

        // Proxy de Imagem (Igual ao movies.php)
        $fallbackImage = 'https://via.placeholder.com/300x450?text=Sem+Imagem';
        $posterUrl = $fallbackImage;
        
        if (!empty($row['img'])) {
            $cleanLink = str_replace(['https://', 'http://'], '', $row['img']);
            $posterUrl = "https://images.weserv.nl/?url=" . urlencode($cleanLink) . 
                         "&w=300&h=450&fit=cover&errorredirect=" . urlencode($fallbackImage);
        }

        $top10Movies[] = [
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
            'is_upcoming' => false
        ];
    }
} catch (Exception $e) {
    // Em caso de erro, a lista fica vazia
}

// Buscar IDs para os filmes fixos do Slider (Avatar e Top Gun)
$idSlide1 = 0;
$idSlide2 = 0;
try {
    // Busca ID para Avatar: O Caminho da Água
    $stmt = $pdo->prepare("SELECT ID_FILME FROM AAA_FILMES WHERE UPPER(TITULO) LIKE '%AVATAR%' AND ROWNUM = 1");
    $stmt->execute();
    $idSlide1 = $stmt->fetchColumn();

    // Busca ID para Top Gun: Maverick
    $stmt = $pdo->prepare("SELECT ID_FILME FROM AAA_FILMES WHERE UPPER(TITULO) LIKE '%TOP GUN: MAVERICK%' AND ROWNUM = 1");
    $stmt->execute();
    $idSlide2 = $stmt->fetchColumn();
} catch (Exception $e) {}

// Buscar Filmes em Destaque (Lista Horizontal)
$highlightList = [];
try {
    $sql = "SELECT * FROM (
                SELECT ID_FILME, TITULO, IMG, GENERO 
                FROM AAA_FILMES 
                WHERE EM_DESTAQUE = 'S'
                ORDER BY DBMS_RANDOM.VALUE
            ) WHERE ROWNUM <= 10";
            
    $stmt = $pdo->query($sql);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row = array_change_key_case($row, CASE_LOWER);
        
        // Proxy de Imagem
        $posterUrl = 'https://via.placeholder.com/300x450?text=Sem+Imagem';
        if (!empty($row['img'])) {
            $cleanLink = str_replace(['https://', 'http://'], '', $row['img']);
            $posterUrl = "https://images.weserv.nl/?url=" . urlencode($cleanLink) . "&w=300&h=450&fit=cover&errorredirect=" . urlencode($fallbackImage);
        }

        $highlightList[] = ['id' => $row['id_filme'], 'titulo' => $row['titulo'], 'poster' => $posterUrl, 'genero' => $row['genero']];
    }
} catch (Exception $e) {}

include __DIR__ . '/includes/header.php';

?>

<!-- Estilos da Página Inicial -->
<style>
    :root {
        /* Ajuste esta cor para combinar com o seu header.php */
        --primary-color: #e50914; 
        --bg-color: #141414;
        --text-color: #ffffff;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        margin: 0;
    }

    /* Slider Destaque */
    .hero-slider {
        position: relative;
        width: 100%;
        height: 600px;
        overflow: hidden;
        margin-bottom: 40px;
    }

    .slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 0.8s ease-in-out;
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: flex-end;
    }

    .slide.active {
        opacity: 1;
        z-index: 1;
    }

    .slide::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to top, var(--bg-color) 10%, transparent 100%);
    }

    .slide-content {
        position: relative;
        z-index: 2;
        padding: 40px 5%;
        max-width: 600px;
        margin-bottom: 40px;
    }

    .slide-title {
        font-size: 3.5rem;
        font-weight: bold;
        margin-bottom: 15px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
    }

    .slide-desc {
        font-size: 1.1rem;
        margin-bottom: 25px;
        line-height: 1.5;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.3s;
        display: inline-block;
    }

    .btn-primary:hover {
        filter: brightness(1.2);
    }

    /* Seção Top 10 */
    .section-container {
        padding: 20px 5%;
        margin-bottom: 60px;
        position: relative;
    }

    .section-title {
        font-size: 1.8rem;
        font-weight: 600;
        border-left: 4px solid var(--primary-color);
        padding-left: 15px;
        margin-bottom: 20px;
    }

    .top10-grid {
        display: flex;
        overflow-x: auto;
        gap: 25px;
        padding: 30px 5%; /* Aumentei o topo para 50px */
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
        scroll-snap-type: x mandatory;
    }
    .top10-grid::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }

    .movie-card {
        min-width: 180px;
        position: relative;
        transition: transform 0.3s ease;
        cursor: pointer;
        scroll-snap-align: start;
    }

    .movie-card:hover {
        transform: scale(1.05);
    }


    .movie-poster {
        width: 100%;
        height: 270px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    }

    .rank-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: var(--primary-color);
        color: white;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        z-index: 10;
    }

    .movie-title {
        margin-top: 10px;
        font-size: 1rem;
        text-align: center;
        font-weight: 500;
    }

    /* Destaques Estilo NOS (Moderno e Maior) */
    .highlights-grid {
        display: flex;
        overflow-x: auto;
        gap: 30px;
        padding: 20px 5px; /* Espaço para a sombra não cortar */
        scrollbar-width: none;
        scroll-snap-type: x mandatory;
    }
    .highlights-grid::-webkit-scrollbar { display: none; }

    .highlight-card {
        min-width: 260px; /* Cartões mais largos */
        position: relative;
        transition: transform 0.3s ease;
        cursor: pointer;
        scroll-snap-align: start;
    }
    .highlight-card:hover {
        transform: translateY(-10px);
    }

    .highlight-poster {
        width: 100%;
        height: 390px; /* Cartazes bem maiores */
        object-fit: cover;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }
    .highlight-card:hover .highlight-poster {
        box-shadow: 0 20px 40px rgba(229, 9, 20, 0.25); /* Sombra vermelha suave */
    }

    .highlight-info { margin-top: 15px; padding-left: 5px; }
    .highlight-title { font-size: 1.2rem; font-weight: 700; color: #fff; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .highlight-genre { color: #aaa; font-size: 0.95rem; font-weight: 500; }

    /* Botões de navegação lateral do Top 10 */
    .top10-nav-btn {
        position: absolute;
        top: 55%; /* Ajustado para ficar no meio dos cartazes */
        transform: translateY(-50%);
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        z-index: 20;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s;
    }
    .top10-nav-btn:hover {
        background-color: var(--primary-color);
    }
    .top10-prev { left: 10px; }
    .top10-next { right: 10px; }

    /* Indicadores (Bolinhas) do Top 10 */
    .top10-indicators {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 15px;
    }
    .dot {
        width: 12px;
        height: 12px;
        background-color: #444;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .dot.active {
        background-color: var(--primary-color);
        transform: scale(1.2);
        box-shadow: 0 0 8px var(--primary-color);
    }

    /* Botão Cinema */
    .cinema-section {
        background: linear-gradient(45deg, #1a1a1a, #2a2a2a);
        padding: 80px 20px;
        text-align: center;
        border-top: 1px solid #333;
        border-bottom: 1px solid #333;
    }

    .cinema-title {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }

    .cinema-text {
        font-size: 1.2rem;
        color: #ccc;
        margin-bottom: 30px;
    }

    .btn-outline {
        background-color: transparent;
        border: 2px solid white;
        color: white;
        padding: 15px 40px;
        border-radius: 50px;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-block;
    }

    .btn-outline:hover {
        background-color: white;
        color: var(--bg-color);
    }

    /* Botões de Navegação do Slider */
    .slider-controls {
        position: absolute;
        bottom: 30px;
        width: 100%;
        display: flex;
        justify-content: center;
        gap: 20px;
        z-index: 10;
        pointer-events: none;
    }

    .slider-btn {
        background-color: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 2rem;
        padding: 10px 20px;
        transition: background 0.3s;
        user-select: none;
        border-radius: 5px;
        pointer-events: auto;
    }
    .slider-btn:hover {
        background-color: var(--primary-color);
    }

    /* --- Estilos do Modal e Calendário (Copiados de movies.php) --- */
    .modal-poster {
        width: 100%;
        border-radius: 8px;
    }
</style>

<main>
    <!-- Slider de Destaques -->
    <section class="hero-slider">
        <!-- Slide 1 -->
        <div class="slide active" style="background-image: url('https://images.weserv.nl/?url=https://image.tmdb.org/t/p/original/s16H6tpK2utvwDtzZ8Qy4qm5Emw.jpg&w=1920&h=800&fit=cover');">
            <div class="slide-content">
                <h2 class="slide-title">Avatar: O Caminho da Água</h2>
                <p class="slide-desc">Jake Sully vive com a sua nova família no planeta Pandora. Quando uma ameaça familiar regressa, Jake deve trabalhar com Neytiri e o exército da raça Na'vi para proteger o seu planeta.</p>
                <a href="pages/movie_details.php?id=<?= $idSlide1 ?>" class="btn-primary">Ver Detalhes</a>
            </div>
        </div>
        <!-- Slide 2 -->
        <div class="slide" style="background-image: url('https://images.weserv.nl/?url=https://image.tmdb.org/t/p/original/628Dep6AxEtDxjZoGP78TsOxYbK.jpg&w=1920&h=800&fit=cover');">
            <div class="slide-content">
                <h2 class="slide-title">Top Gun: Maverick</h2>
                <p class="slide-desc">Depois de mais de 30 anos de serviço como um dos melhores aviadores da Marinha, Pete 'Maverick' Mitchell está onde devia estar: a voar nos limites como piloto de testes e a treinar um destacamento de graduados Top Gun.</p>
                <a href="pages/movie_details.php?id=<?= $idSlide2 ?>" class="btn-primary">Ver Detalhes</a>
            </div>
        </div>
        
        <!-- Botões de Navegação -->
        <div class="slider-controls">
            <button class="slider-btn prev-btn" onclick="moveSlide(-1)">&#10094;</button>
            <button class="slider-btn next-btn" onclick="moveSlide(1)">&#10095;</button>
        </div>

        <!-- Script para rotação automática e manual -->
        <script>
            let slides = document.querySelectorAll('.slide');
            let currentSlide = 0;
            let slideInterval = setInterval(nextSlide, 5000);

            function nextSlide() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }

            function moveSlide(direction) {
                clearInterval(slideInterval);
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + direction + slides.length) % slides.length;
                slides[currentSlide].classList.add('active');
                slideInterval = setInterval(nextSlide, 5000);
            }
        </script>
    </section>

    <!-- Top 10 Filmes -->
    <section class="section-container">
        <h2 class="section-title">Top 10 Filmes</h2>
        
        <!-- Botões Laterais -->
        <button class="top10-nav-btn top10-prev" id="btnPrevTop10">&#10094;</button>
        <button class="top10-nav-btn top10-next" id="btnNextTop10">&#10095;</button>

        <div class="top10-grid" id="top10Grid">
            <?php 
            if (empty($top10Movies)) {
                echo '<div class="text-white ms-3">Nenhum filme encontrado.</div>';
            } else {
                foreach($top10Movies as $index => $movieData): 
                    $rank = $index + 1;
            ?>
            <div class="movie-card" onclick='window.location.href="pages/movie_details.php?id=<?= $movieData["id"] ?>"'>
                <div class="rank-badge"><?php echo $rank; ?></div>
                <img src="<?php echo $movieData['poster']; ?>" alt="<?php echo $movieData['titulo']; ?>" class="movie-poster">
                <div class="movie-title"><?php echo $movieData['titulo']; ?></div>
            </div>
            <?php 
                endforeach; 
            }
            ?>
        </div>
        
        <!-- Container das Bolinhas -->
        <div class="top10-indicators" id="top10Dots"></div>

        <script>
            // Script para controlar a paginação do Top 10
            document.addEventListener('DOMContentLoaded', function() {
                const grid = document.getElementById('top10Grid');
                const dotsContainer = document.getElementById('top10Dots');
                const btnPrev = document.getElementById('btnPrevTop10');
                const btnNext = document.getElementById('btnNextTop10');

                // Funcionalidade dos botões laterais
                btnPrev.addEventListener('click', () => {
                    grid.scrollBy({ left: -300, behavior: 'smooth' });
                });
                btnNext.addEventListener('click', () => {
                    grid.scrollBy({ left: 300, behavior: 'smooth' });
                });

                function updateDots() {
                    // Calcula quantos itens cabem na tela e quantas páginas são necessárias
                    const itemWidth = grid.children[0].offsetWidth + 20; // largura + gap
                    const itemsPerPage = Math.floor(grid.offsetWidth / itemWidth);
                    const totalItems = grid.children.length;
                    const pageCount = Math.ceil(totalItems / Math.max(1, itemsPerPage));

                    dotsContainer.innerHTML = '';
                    
                    for (let i = 0; i < pageCount; i++) {
                        const dot = document.createElement('div');
                        dot.className = 'dot';
                        if (i === 0) dot.classList.add('active');
                        
                        dot.onclick = () => {
                            grid.scrollTo({
                                left: i * grid.offsetWidth,
                                behavior: 'smooth'
                            });
                            // Atualiza visualmente a classe active
                            document.querySelectorAll('.dot').forEach(d => d.classList.remove('active'));
                            dot.classList.add('active');
                        };
                        dotsContainer.appendChild(dot);
                    }
                }

                // Atualiza ao carregar e ao redimensionar a tela
                updateDots();
                window.addEventListener('resize', updateDots);

                // Sincroniza as bolinhas se o usuário fizer scroll manual (touch/swipe)
                grid.addEventListener('scroll', () => {
                    const pageIndex = Math.round(grid.scrollLeft / grid.offsetWidth);
                    const dots = document.querySelectorAll('.dot');
                    dots.forEach((d, index) => {
                        if (index === pageIndex) d.classList.add('active');
                        else d.classList.remove('active');
                    });
                });
            });
        </script>
    </section>

    <!-- Filmes em Destaque (Estilo NOS) -->
    <section class="section-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Em Destaque</h2>
            <a href="pages/movies.php?tab=highlights" class="btn btn-sm btn-outline-light rounded-pill px-3">Ver Todos <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        
        <!-- Botões Laterais -->
        <button class="top10-nav-btn top10-prev" id="btnPrevHighlights">&#10094;</button>
        <button class="top10-nav-btn top10-next" id="btnNextHighlights">&#10095;</button>

        <div class="highlights-grid" id="highlightsGrid">
            <?php if (empty($highlightList)): ?>
                <div class="text-white ms-3">Nenhum destaque encontrado.</div>
            <?php else: ?>
                <?php foreach($highlightList as $movie): ?>
                <div class="highlight-card" onclick='window.location.href="pages/movie_details.php?id=<?= $movie["id"] ?>"'>
                    <img src="<?= $movie['poster']; ?>" alt="<?= $movie['titulo']; ?>" class="highlight-poster">
                    <div class="highlight-info">
                        <div class="highlight-title"><?= $movie['titulo']; ?></div>
                        <div class="highlight-genre"><?= $movie['genero']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const grid = document.getElementById('highlightsGrid');
                const btnPrev = document.getElementById('btnPrevHighlights');
                const btnNext = document.getElementById('btnNextHighlights');
                btnPrev.addEventListener('click', () => { grid.scrollBy({ left: -350, behavior: 'smooth' }); });
                btnNext.addEventListener('click', () => { grid.scrollBy({ left: 350, behavior: 'smooth' }); });
            });
        </script>
    </section>

    <!-- Botão Cinema -->
    <section class="cinema-section" style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.9)), url('https://images.weserv.nl/?url=https://image.tmdb.org/t/p/original/xJHokMbljvjADYdit5fK5VQsXEG.jpg&w=1920&h=800&fit=cover'); background-size: cover; background-position: center; background-attachment: fixed;">
        <h2 class="cinema-title">Filmes em Destaque</h2>
        <p class="cinema-text">Descubra as produções que estão a marcar o ano. Grandes histórias e emoções fortes esperam por si.</p>
        <a href="/pages/movies.php?tab=highlights" class="btn-outline">Ver Destaques</a>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
