<?php
// Garante que o caminho está certo
require_once dirname(__DIR__) . '/config/db.php'; 
include dirname(__DIR__) . '/includes/header.php';
?>

<style>
    /* Modern Hero Section */
    .cinema-hero {
        background: linear-gradient(to bottom, rgba(0,0,0,0.3), #121212), url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1920&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        height: 60vh;
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        margin-bottom: 80px;
        position: relative;
    }
    
    /* Modern Cards */
    .room-card {
        background-color: #1a1a1a;
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
    }
    .room-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .room-card img {
        height: 220px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .room-card:hover img {
        transform: scale(1.05);
    }
    .card-body {
        padding: 25px;
    }

    /* Info Box Moderno */
    .info-box {
        background: rgba(30, 30, 30, 0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 30px;
        height: 100%;
    }

    .map-container {
        height: 450px;
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    .feature-icon {
        font-size: 2rem;
        margin-bottom: 15px;
        background: linear-gradient(45deg, #e50914, #ff4d4d);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
</style>

<div class="cinema-hero">
    <div class="container">
        <h1 class="display-2 fw-bold text-white mb-3" style="text-shadow: 0 4px 10px rgba(0,0,0,0.5);">CinemaHub Central</h1>
        <p class="lead text-white opacity-75 fs-4">A excelência do cinema no coração do Porto.</p>
    </div>
</div>

<div class="container pb-5">
    <!-- Secção Sobre -->
    <div class="row align-items-center mb-5 pb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <h6 class="text-danger text-uppercase fw-bold mb-2">Experiência Premium</h6>
            <h2 class="text-white fw-bold display-5 mb-4">Mais do que um cinema</h2>
            <p class="text-secondary fs-5 lh-lg mb-4">
                Redefinimos a forma como vê filmes. Com tecnologia de ponta e conforto inigualável, cada sessão no CinemaHub é uma imersão total na arte cinematográfica.
            </p>
            <div class="row g-4 mt-2">
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-projector-fill fs-3 text-danger me-3"></i>
                        <div>
                            <h5 class="text-white fw-bold mb-0">Laser 4K</h5>
                            <small class="text-secondary">Imagem Cristalina</small>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-speaker-fill fs-3 text-danger me-3"></i>
                        <div>
                            <h5 class="text-white fw-bold mb-0">Dolby Atmos</h5>
                            <small class="text-secondary">Som 360º</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="position-relative">
                <div class="position-absolute bottom-0 end-0 bg-danger text-white p-4 rounded-4 m-n4 d-none d-md-block shadow">
                    <h3 class="fw-bold mb-0">12</h3>
                    <small>Salas Premium</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Salas -->
    <div class="mb-5 pb-5">
        <div class="text-center mb-5">
            <h6 class="text-danger text-uppercase fw-bold">Nossos Espaços</h6>
            <h2 class="text-white fw-bold display-5">Escolha a sua experiência</h2>
        </div>
        
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="room-card">
                    <img src="https://images.unsplash.com/photo-1595769816263-9b910be24d5f?q=80&w=600&auto=format&fit=crop" class="w-100" alt="IMAX">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="text-white fw-bold mb-0">IMAX</h4>
                            <span class="badge bg-danger rounded-pill">Premium</span>
                        </div>
                        <p class="text-secondary mb-0">A maior tela da cidade para uma imersão visual incomparável nos maiores blockbusters.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="room-card">
                    <img src="https://images.unsplash.com/photo-1575444758702-4a6b9222336e?q=80&w=600&auto=format&fit=crop" class="w-100" alt="Lounge">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="text-white fw-bold mb-0">VIP Lounge</h4>
                            <span class="badge bg-warning text-dark rounded-pill">Exclusivo</span>
                        </div>
                        <p class="text-secondary mb-0">Relaxe antes do filme com cocktails exclusivos e snacks gourmet no nosso bar privado.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="room-card">
                    <img src="https://images.unsplash.com/photo-1485846234645-a62644f84728?q=80&w=600&auto=format&fit=crop" class="w-100" alt="Standard">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="text-white fw-bold mb-0">Atmos Hall</h4>
                            <span class="badge bg-info text-dark rounded-pill">Standard</span>
                        </div>
                        <p class="text-secondary mb-0">Conforto total e qualidade digital superior para toda a família.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Localização -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="info-box">
                <h3 class="text-white fw-bold mb-4">Visite-nos</h3>
                
                <div class="d-flex mb-4">
                    <div class="me-3"><i class="bi bi-geo-alt-fill text-danger fs-4"></i></div>
                    <div>
                        <h5 class="text-white fw-bold mb-1">Morada</h5>
                        <p class="text-secondary mb-0">Avenida dos Aliados, Porto</p>
                    </div>
                </div>
                
                <div class="d-flex mb-4">
                    <div class="me-3"><i class="bi bi-clock-fill text-danger fs-4"></i></div>
                    <div>
                        <h5 class="text-white fw-bold mb-1">Horário</h5>
                        <p class="text-secondary mb-0">Todos os dias: 13:00 - 00:00</p>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="me-3"><i class="bi bi-envelope-fill text-danger fs-4"></i></div>
                    <div>
                        <h5 class="text-white fw-bold mb-1">Contacto</h5>
                        <p class="text-secondary mb-0">geral@cinehub.pt</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="map-container">
                <iframe 
                    width="100%" 
                    height="100%" 
                    style="border:0; filter: grayscale(100%) invert(92%) contrast(83%);" 
                    loading="lazy" 
                    allowfullscreen 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3004.561576437774!2d-8.613567684582352!3d41.14966297928682!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd2464e320579e01%3A0x6296317bc676e73!2sAv.%20dos%20Aliados%2C%20Porto!5e0!3m2!1spt-PT!2spt!4v1620000000000!5m2!1spt-PT!2spt">
                </iframe>
            </div>
        </div>
    </div>
</div>

<?php 
// Rodapé também precisa subir 2 níveis (ajustado para 1 nível conforme estrutura)
include dirname(__DIR__) . '/includes/footer.php'; 
?>