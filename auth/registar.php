<?php
require dirname(__DIR__) . '/config/db.php';

// Variáveis para mensagens
$erro = '';
$sucesso = '';
$redirect = $_GET['redirect'] ?? '';

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolha e limpeza dos dados
    $nome = trim($_POST['nome']);
    $apelido = trim($_POST['apelido']);
    $data_nascimento = $_POST['data_nascimento']; // Já vem em YYYY-MM-DD do JS
    $telemovel = trim($_POST['telemovel']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $redirect = $_POST['redirect'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($apelido) || empty($email) || empty($password)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif ($password !== $confirm_password) {
        $erro = "As palavras-pass não coincidem.";
    } else {
        try {
            // 1. Verificar se o email já existe
            // NOTA: Verifique se o nome da sua tabela é 'AAA_UTILIZADORES' ou 'users'
            $stmt = $pdo->prepare("SELECT id_utilizador FROM AAA_UTILIZADORES WHERE email = :email OR n_telemovel = :telemovel");
            $stmt->execute([':email' => $email, ':telemovel' => $telemovel]);

            if ($stmt->fetch()) {
                $erro = "Este email ou número de telemóvel já se encontra registado.";
            } else {
                // 2. Encriptar a password (Nunca guarde passwords em texto limpo!)
                $senha_hash = password_hash($password, PASSWORD_DEFAULT);

                // 3. Inserir na Base de Dados
                // CORREÇÃO: Mudámos para 'Cliente' (igual ao que está na tua imagem da BD)
                $sql = "INSERT INTO AAA_UTILIZADORES (
                            nome, 
                            SEGUNDO_NOME, 
                            DATA_NASCIMENTO, 
                            n_telemovel, 
                            email, 
                            senha, 
                            tipo_permissao
                        ) VALUES (
                            :nome, 
                            :apelido, 
                            TO_DATE(:data_nascimento, 'YYYY-MM-DD'), 
                            :telemovel, 
                            :email, 
                            :senha, 
                            'Cliente'
                        )";
                
                // NOTA SOBRE ORACLE E DATAS:
                // Se estiveres a usar Oracle, é mais seguro usar TO_DATE no SQL se a coluna for do tipo DATE.
                
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([
                    ':nome' => $nome,
                    ':apelido' => $apelido, // O valor vem do formulário (variável $apelido)
                    ':data_nascimento' => !empty($data_nascimento) ? $data_nascimento : null,
                    ':telemovel' => $telemovel,
                    ':email' => $email,
                    ':senha' => $senha_hash
                ]);

                // Redireciona para o login após sucesso
                $loginUrl = "/auth/login.php";
                if (!empty($redirect)) {
                    $loginUrl .= "?redirect=" . urlencode($redirect);
                }
                header("Location: " . $loginUrl);
                exit;
            }
        } catch (PDOException $e) {
            // Mostra o erro exato para ajudar no debug
            if (strpos($e->getMessage(), 'ORA-00001') !== false) {
                $erro = "Erro: Já existe uma conta com este Email ou Telemóvel.";
            } else {
                $erro = "Erro ao registar: " . $e->getMessage();
            }
        }
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<style>
    body {
        background-color: #121212;
        /* Fundo com imagem cinematográfica, sobreposição escura e desfoque */
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.9)), url('https://picsum.photos/1920/1080?grayscale&blur=2');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
    }

    .register-container {
        max-width: 600px;
        margin: 60px auto;
        background-color: #1f1f1f;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border: 1px solid #333;
    }
    
    .form-control {
        background-color: #121212;
        border: 1px solid #333;
        color: #fff;
        padding: 12px 15px;
    }
    
    .form-control:focus {
        background-color: #121212;
        border-color: #e50914;
        color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
    }

    .btn-register {
        background: linear-gradient(135deg, #e50914 0%, #ff4d4d 100%);
        border: none;
        color: white;
        padding: 12px;
        font-weight: bold;
        width: 100%;
        border-radius: 8px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
        color: white;
    }

    .link-danger-custom {
        color: #e50914;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .link-danger-custom:hover {
        color: #ff4d4d;
        text-decoration: underline;
    }

    /* Calendário Personalizado */
    .custom-calendar {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        max-width: 350px;
        background-color: #181818;
        border: 1px solid #333;
        border-radius: 16px;
        padding: 20px;
        z-index: 1000;
        box-shadow: 0 20px 50px rgba(0,0,0,0.9);
        display: none;
        font-family: 'Segoe UI', sans-serif;
        margin-top: 5px;
    }
    .custom-calendar.show {
        display: block;
        animation: fadeInCal 0.3s ease-out;
    }
    @keyframes fadeInCal {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #2a2a2a;
    }
    .calendar-header span {
        font-weight: 700;
        font-size: 1.1rem;
        text-transform: capitalize;
        color: #fff;
        cursor: pointer; /* Indica que é clicável */
    }
    .calendar-nav-btn {
        background: #2a2a2a;
        border: none;
        color: white;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .calendar-nav-btn:hover {
        background-color: #e50914;
        transform: scale(1.1);
    }
    .calendar-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: #666;
        margin-bottom: 10px;
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }
    .calendar-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 50%;
        font-size: 0.9rem;
        color: #e0e0e0;
        transition: all 0.2s;
    }
    .calendar-day:hover:not(.empty) {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .calendar-day.selected {
        background-color: #e50914;
        color: white;
        font-weight: bold;
        box-shadow: 0 0 10px rgba(229, 9, 20, 0.4);
    }
    .calendar-day.empty {
        cursor: default;
        pointer-events: none;
    }
    /* Seleção de Ano */
    .calendar-years {
        display: none;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 5px;
    }
    .calendar-years.show {
        display: grid;
    }
    .year-item {
        padding: 10px 5px;
        text-align: center;
        border-radius: 8px;
        cursor: pointer;
        color: #e0e0e0;
        transition: all 0.2s;
        font-size: 0.9rem;
        border: 1px solid transparent;
    }
    .year-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }
    .year-item.selected {
        background-color: #e50914;
        color: white;
        font-weight: bold;
        box-shadow: 0 0 10px rgba(229, 9, 20, 0.4);
    }
    .calendar-years::-webkit-scrollbar { width: 5px; }
    .calendar-years::-webkit-scrollbar-track { background: #2a2a2a; border-radius: 3px; }
    .calendar-years::-webkit-scrollbar-thumb { background: #555; border-radius: 3px; }

    /* Seleção de Mês */
    .calendar-months {
        display: none;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        padding: 10px 0;
    }
    .calendar-months.show {
        display: grid;
    }
    .month-item {
        padding: 15px 5px;
        text-align: center;
        border-radius: 8px;
        cursor: pointer;
        color: #e0e0e0;
        transition: all 0.2s;
        font-size: 0.9rem;
        border: 1px solid transparent;
        text-transform: capitalize;
    }
    .month-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }
    .month-item.selected {
        background-color: #e50914;
        color: white;
        font-weight: bold;
        box-shadow: 0 0 10px rgba(229, 9, 20, 0.4);
    }
</style>

<div class="container">
    <div class="register-container">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-white">Criar Conta</h2>
            <p class="text-secondary">Preencha os dados para se registar</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $erro; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="alert alert-success" role="alert">
                <?= $sucesso; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="registerForm" onsubmit="return validatePassword()">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label text-secondary">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Seu nome" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="apelido" class="form-label text-secondary">Apelido</label>
                    <input type="text" class="form-control" id="apelido" name="apelido" placeholder="Seu apelido" required value="<?= isset($_POST['apelido']) ? htmlspecialchars($_POST['apelido']) : '' ?>">
                </div>
            </div>

            <div class="mb-3 position-relative">
                <label for="data_nascimento" class="form-label text-secondary">Data de Nascimento</label>
                <input type="hidden" id="data_nascimento" name="data_nascimento" required value="<?= isset($_POST['data_nascimento']) ? htmlspecialchars($_POST['data_nascimento']) : '' ?>">
                
                <div class="form-control d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="toggleCalendar()">
                    <span id="dateDisplay" class="<?= isset($_POST['data_nascimento']) ? 'text-white' : 'text-secondary' ?>">
                        <?= isset($_POST['data_nascimento']) ? date('d/m/Y', strtotime($_POST['data_nascimento'])) : 'Selecione a data...' ?>
                    </span>
                    <i class="bi bi-calendar-event text-danger"></i>
                </div>

                <div id="customCalendar" class="custom-calendar">
                    <div class="calendar-header">
                        <button type="button" class="calendar-nav-btn" onclick="changeMonth(-1)"><i class="bi bi-chevron-left"></i></button>
                        <span id="calendarMonthYear"></span>
                        <button type="button" class="calendar-nav-btn" onclick="changeMonth(1)"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div id="calendarBody">
                        <div class="calendar-weekdays">
                            <div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div>
                        </div>
                        <div id="calendarGrid" class="calendar-grid"></div>
                    </div>
                    <div id="calendarYears" class="calendar-years"></div>
                    <div id="calendarMonths" class="calendar-months"></div>
                </div>
            </div>

            <div class="mb-3">
                <label for="telemovel" class="form-label text-secondary">Nº de Telemóvel</label>
                <input type="tel" class="form-control" id="telemovel" name="telemovel" placeholder="912345678" required value="<?= isset($_POST['telemovel']) ? htmlspecialchars($_POST['telemovel']) : '' ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label text-secondary">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="exemplo@email.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label text-secondary">Palavra-pass</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                    <button class="btn btn-outline-secondary bg-dark border-secondary text-secondary" type="button" onclick="togglePass('password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label text-secondary">Confirmar Palavra-pass</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="********" required>
                    <button class="btn btn-outline-secondary bg-dark border-secondary text-secondary" type="button" onclick="togglePass('confirm_password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div id="passwordError" class="text-danger small mt-1" style="display:none;">As palavras-pass não coincidem.</div>
            </div>

            <button type="submit" class="btn btn-register mt-3 mb-4">Registar</button>

            <div class="text-center text-secondary">
                Já tem conta? <a href="/auth/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="link-danger-custom fw-bold">Entrar</a>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        btn.querySelector('i').classList.toggle('bi-eye');
        btn.querySelector('i').classList.toggle('bi-eye-slash');
    }

    function validatePassword() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const errorDiv = document.getElementById('passwordError');

        if (password !== confirmPassword) {
            errorDiv.style.display = 'block';
            return false;
        }
        errorDiv.style.display = 'none';
        return true;
    }

    // Lógica do Calendário
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();
    let selectedDateObj = null;

    function toggleCalendar() {
        const cal = document.getElementById('customCalendar');
        cal.classList.toggle('show');
        if (cal.classList.contains('show')) {
            renderCalendar();
        }
    }

    function changeMonth(step) {
        currentMonth += step;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        } else if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    }
    
    function toggleYearView() {
        const body = document.getElementById('calendarBody');
        const months = document.getElementById('calendarMonths');
        const years = document.getElementById('calendarYears');
        const navBtns = document.querySelectorAll('.calendar-nav-btn');
        
        if (years.classList.contains('show')) {
            years.classList.remove('show');
            body.style.display = 'block';
            navBtns.forEach(btn => btn.style.visibility = 'visible');
        } else {
            body.style.display = 'none';
            months.classList.remove('show'); // Garante que os meses fecham
            years.classList.add('show');
            navBtns.forEach(btn => btn.style.visibility = 'hidden');
            renderYears();
        }
    }

    function renderYears() {
        const yearsContainer = document.getElementById('calendarYears');
        yearsContainer.innerHTML = '';
        const currentY = new Date().getFullYear();
        const startY = 1900;
        
        for (let y = currentY; y >= startY; y--) {
            const div = document.createElement('div');
            div.className = 'year-item';
            if (y === currentYear) {
                div.classList.add('selected');
                setTimeout(() => div.scrollIntoView({ block: "center" }), 0);
            }
            div.innerText = y;
            div.onclick = () => {
                currentYear = y;
                toggleYearView(); // Volta para a vista de dias
                renderCalendar(); // Atualiza o calendário com o novo ano
            };
            yearsContainer.appendChild(div);
        }
    }

    function toggleMonthView() {
        const body = document.getElementById('calendarBody');
        const years = document.getElementById('calendarYears');
        const months = document.getElementById('calendarMonths');
        const navBtns = document.querySelectorAll('.calendar-nav-btn');
        
        if (months.classList.contains('show')) {
            months.classList.remove('show');
            body.style.display = 'block';
            navBtns.forEach(btn => btn.style.visibility = 'visible');
        } else {
            body.style.display = 'none';
            years.classList.remove('show'); // Garante que os anos fecham
            months.classList.add('show');
            navBtns.forEach(btn => btn.style.visibility = 'hidden');
            renderMonths();
        }
    }

    function renderMonths() {
        const monthsContainer = document.getElementById('calendarMonths');
        monthsContainer.innerHTML = '';
        const months = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        
        months.forEach((m, index) => {
            const div = document.createElement('div');
            div.className = 'month-item';
            if (index === currentMonth) {
                div.classList.add('selected');
            }
            div.innerText = m;
            div.onclick = () => {
                currentMonth = index;
                toggleMonthView(); // Volta para a vista de dias
                renderCalendar();
            };
            monthsContainer.appendChild(div);
        });
    }

    function renderCalendar() {
        const monthYear = document.getElementById('calendarMonthYear');
        const grid = document.getElementById('calendarGrid');
        const months = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        
        // Cria spans separados para Mês e Ano com eventos de clique distintos
        monthYear.innerHTML = `<span onclick="toggleMonthView()" title="Mudar Mês">${months[currentMonth]}</span> <span onclick="toggleYearView()" title="Mudar Ano">${currentYear}</span>`;
        
        grid.innerHTML = "";

        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'calendar-day empty';
            grid.appendChild(empty);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const day = document.createElement('div');
            day.className = 'calendar-day';
            day.innerText = i;
            
            if (selectedDateObj && 
                selectedDateObj.getDate() === i && 
                selectedDateObj.getMonth() === currentMonth && 
                selectedDateObj.getFullYear() === currentYear) {
                day.classList.add('selected');
            }

            day.onclick = (e) => {
                e.stopPropagation();
                selectDate(i);
            };
            grid.appendChild(day);
        }
    }

    function selectDate(day) {
        selectedDateObj = new Date(currentYear, currentMonth, day);
        // Ajusta a data para formato ISO sem sofrer com timezone (truque simples)
        const year = selectedDateObj.getFullYear();
        const month = String(selectedDateObj.getMonth() + 1).padStart(2, '0');
        const d = String(selectedDateObj.getDate()).padStart(2, '0');
        const formattedDate = `${year}-${month}-${d}`;
        
        document.getElementById('data_nascimento').value = formattedDate;
        
        const display = document.getElementById('dateDisplay');
        display.innerText = selectedDateObj.toLocaleDateString('pt-BR');
        display.classList.remove('text-secondary');
        display.classList.add('text-white');
        
        document.getElementById('customCalendar').classList.remove('show');
    }

    document.addEventListener('click', function(e) {
        const cal = document.getElementById('customCalendar');
        const trigger = document.querySelector('.form-control[onclick="toggleCalendar()"]');
        if (cal.classList.contains('show') && !cal.contains(e.target) && !trigger.contains(e.target)) {
            cal.classList.remove('show');
        }
    });
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
