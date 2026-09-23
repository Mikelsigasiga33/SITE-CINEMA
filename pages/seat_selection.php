<?php
require dirname(__DIR__) . '/config/db.php';
include dirname(__DIR__) . '/includes/header.php';

// Captura dados da URL (simulação)
$movieId = $_GET['movie_id'] ?? 0;
$sessionTime = $_GET['time'] ?? '00:00';
$movieTitle = $_GET['title'] ?? 'Filme Selecionado'; // Em produção, buscaria na BD pelo ID
?>

<style>
    .seat-container {
        perspective: 1000px;
        margin-bottom: 30px;
    }
    
    .screen {
        background-color: #fff;
        height: 70px;
        width: 100%;
        margin: 15px 0 40px;
        transform: rotateX(-45deg);
        box-shadow: 0 30px 60px rgba(255, 255, 255, 0.4);
        border-radius: 10px;
        text-align: center;
        line-height: 70px;
        color: #000;
        font-weight: bold;
        letter-spacing: 5px;
        text-transform: uppercase;
        font-size: 0.8rem;
        opacity: 0.8;
        max-width: 600px;
    }

    .seats-grid {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .seat-row {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .row-label {
        color: #666;
        font-weight: bold;
        width: 25px;
        text-align: center;
    }

    .seat {
        background-color: #444;
        height: 35px;
        width: 40px;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }

    /* Corredor Central (aplica margem ao 6º elemento - 5º assento + label) */
    .seat:nth-of-type(6) {
        margin-left: 40px;
    }

    .seat:hover:not(.occupied) {
        transform: scale(1.1);
        background-color: #666;
    }

    .seat.selected {
        background-color: #e50914;
        box-shadow: 0 0 10px rgba(229, 9, 20, 0.5);
    }

    .seat.occupied {
        background-color: #222;
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Legenda */
    .showcase {
        background: rgba(0, 0, 0, 0.3);
        padding: 15px;
        border-radius: 10px;
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-bottom: 40px;
    }
    .showcase li {
        display: flex;
        align-items: center;
        gap: 10px;
        list-style: none;
        color: #ccc;
        font-size: 0.9rem;
    }
    .seat-small {
        height: 20px;
        width: 25px;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
        display: inline-block;
    }

    /* Painel de Resumo */
    .summary-panel {
        background-color: #1f1f1f;
        border-top: 1px solid #333;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 20px;
        z-index: 100;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.5);
    }
</style>

<div class="container py-5 mb-5">
    <div class="text-center mb-4">
        <h4 class="text-secondary text-uppercase small">Seleção de Lugares</h4>
        <h2 class="fw-bold text-white"><?= htmlspecialchars($movieTitle) ?></h2>
        <p class="text-danger"><i class="bi bi-clock me-2"></i>Sessão das <?= htmlspecialchars($sessionTime) ?></p>
    </div>

    <!-- Legenda -->
    <ul class="showcase">
        <li><div class="seat-small" style="background-color: #444;"></div> Disponível</li>
        <li><div class="seat-small" style="background-color: #e50914;"></div> Selecionado</li>
        <li><div class="seat-small" style="background-color: #222;"></div> Ocupado</li>
    </ul>

    <!-- Sala de Cinema -->
    <div class="seat-container d-flex flex-column align-items-center justify-content-center">
        <div class="screen">Ecrã</div>
        
        <div class="seats-grid">
            <?php 
            $rows = 8;
            $cols = 10;
            for ($r = 0; $r < $rows; $r++): ?>
                <div class="seat-row">
                    <div class="row-label"><?= chr(65 + $r) ?></div>
                    <?php for ($c = 0; $c < $cols; $c++): 
                        // Simula lugares ocupados aleatoriamente
                        $isOccupied = (rand(0, 10) > 7) ? 'occupied' : '';
                        $seatLabel = chr(65 + $r) . ($c + 1); // Ex: A1, B5
                    ?>
                        <div class="seat <?= $isOccupied ?>" data-seat="<?= $seatLabel ?>" title="<?= $seatLabel ?>"></div>
                    <?php endfor; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Painel Fixo de Resumo -->
<div class="summary-panel">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <p class="mb-0 text-secondary small">Lugares Selecionados:</p>
            <h5 class="text-white fw-bold mb-0" id="selectedSeatsDisplay">-</h5>
        </div>
        <button class="btn btn-gradient px-5 py-2 fw-bold rounded-pill" id="btnCheckout" disabled>Seleção de Bilhete</button>
    </div>
</div>

<script>
    const container = document.querySelector('.seats-grid');
    const seats = document.querySelectorAll('.seat:not(.occupied)');
    const countDisplay = document.getElementById('selectedSeatsDisplay');
    const btnCheckout = document.getElementById('btnCheckout');

    container.addEventListener('click', (e) => {
        if (e.target.classList.contains('seat') && !e.target.classList.contains('occupied')) {
            e.target.classList.toggle('selected');
            updateSelectedCount();
        }
    });

    function updateSelectedCount() {
        const selectedSeats = document.querySelectorAll('.seat.selected');
        const selectedSeatsCount = selectedSeats.length;
        
        // Obter nomes dos lugares
        const seatLabels = Array.from(selectedSeats).map(seat => seat.getAttribute('data-seat'));
        
        countDisplay.innerText = seatLabels.length > 0 ? seatLabels.join(', ') : '-';
        
        btnCheckout.disabled = selectedSeatsCount === 0;
    }

    btnCheckout.addEventListener('click', () => {
        const selectedSeats = document.querySelectorAll('.seat.selected');
        if (selectedSeats.length === 0) return;
        
        const seatLabels = Array.from(selectedSeats).map(seat => seat.getAttribute('data-seat')).join(',');
        const count = selectedSeats.length;
        
        // Redireciona para a página de pagamento (buyticket.php)
        // A verificação de login será feita lá automaticamente
        window.location.href = `/pages/buyticket.php?movie_id=<?= $movieId ?>&title=${encodeURIComponent('<?= $movieTitle ?>')}&time=${encodeURIComponent('<?= $sessionTime ?>')}&seats=${seatLabels}&count=${count}`;
    });
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
