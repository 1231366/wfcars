<?php
/**
 * HOMEPAGE - INDEX.PHP
 * 1. Processamento do Formulário de Contacto (AJAX)
 * 2. Carregamento de Veículos
 */

include 'db_connect.php';

// --- LÓGICA DE ENVIO DE EMAIL (AJAX) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'send_contact') {
    // Limpa qualquer output anterior
    ob_clean();
    header('Content-Type: application/json');

    // 1. VERIFICAÇÃO MANUAL (CHECKBOX)
    // Se a checkbox não vier marcada, bloqueia.
    if (!isset($_POST['human_verification']) || $_POST['human_verification'] !== '1') {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, confirme a verificação manual (checkbox).']);
        exit;
    }

    $nome = strip_tags(trim($_POST['nome']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $telefone = strip_tags(trim($_POST['telefone']));
    $detalhes = strip_tags(trim($_POST['detalhes']));
    
    // Email de destino
    $to = "geral@wfcars.pt";
    $subject = "Novo Contacto Web: $nome";

    // Conteúdo do Email
    $message = "
    <html>
    <head>
      <title>Novo Contacto do Site WFCARS</title>
    </head>
    <body>
      <h2 style='color: #333;'>Novo Pedido de Contacto</h2>
      <p><strong>Nome:</strong> $nome</p>
      <p><strong>Email:</strong> $email</p>
      <p><strong>Telefone:</strong> $telefone</p>
      <hr>
      <p><strong>Mensagem:</strong><br>$detalhes</p>
      <br>
      <small style='color: #888;'>Enviado via formulário do site wfcars.pt</small>
    </body>
    </html>
    ";

    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: WFCars Site <noreply@wfcars.pt>" . "\r\n";
    $headers .= "Reply-To: $email" . "\r\n";

    // Tenta enviar
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'Mensagem enviada com sucesso!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Falha ao enviar email. Verifique a configuração do servidor.']);
    }
    exit; 
}

// --- 1. Aggregation for Filters ---

// a. Get unique Brands
$sql_brands = "SELECT DISTINCT marca FROM anuncios WHERE status = 'Ativo' ORDER BY marca ASC";
$result_brands = $conn->query($sql_brands);
$brands = $result_brands ? $result_brands->fetch_all(MYSQLI_ASSOC) : [];

// b. Get Min/Max Price, Min/Max KM, Min/Max Year
$sql_stats = "SELECT 
    MIN(preco) as min_price, MAX(preco) as max_price,
    MIN(quilometragem) as min_km, MAX(quilometragem) as max_km,
    MIN(modelo_ano) as min_year, MAX(modelo_ano) as max_year
FROM anuncios WHERE status = 'Ativo'";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// c. Get fixed options
$fuel_types = ['Diesel', 'Gasolina', 'Híbrido', 'Elétrico'];
$transmissions = ['Automática', 'Manual'];


// --- 2. Filtering Logic ---
$where_clauses = ["a.status = 'Ativo'"];
$bind_types = '';
$bind_params = [];

if (isset($_GET['filter'])) {
    if (!empty($_GET['marca']) && $_GET['marca'] !== 'all') {
        $where_clauses[] = "a.marca = ?";
        $bind_types .= 's';
        $bind_params[] = $_GET['marca'];
    }
    if (!empty($_GET['preco_max'])) {
        $where_clauses[] = "a.preco <= ?";
        $bind_types .= 'd';
        $bind_params[] = (float)$_GET['preco_max'];
    }
    if (!empty($_GET['km_max'])) {
        $where_clauses[] = "a.quilometragem <= ?";
        $bind_types .= 'i';
        $bind_params[] = (int)$_GET['km_max'];
    }
    if (!empty($_GET['combustivel']) && $_GET['combustivel'] !== 'all') {
        $where_clauses[] = "a.tipo_combustivel = ?";
        $bind_types .= 's';
        $bind_params[] = $_GET['combustivel'];
    }
    if (!empty($_GET['transmissao']) && $_GET['transmissao'] !== 'all') {
        $where_clauses[] = "a.transmissao = ?";
        $bind_types .= 's';
        $bind_params[] = $_GET['transmissao'];
    }
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

// --- 3. Main Inventory Query ---
$listings = []; 
$display_listings = []; 

$is_initial_load = !isset($_GET['filter']);
$filter_applied = isset($_GET['filter']) && (count($bind_params) > 0 || !empty($_GET['marca']) && $_GET['marca'] !== 'all' || !empty($_GET['combustivel']) && $_GET['combustivel'] !== 'all' || !empty($_GET['transmissao']) && $_GET['transmissao'] !== 'all');
$filter_successful = false; 
$fallback_message = false; 

if ($filter_applied) {
    $stmt_inventory = $conn->prepare("
        SELECT 
            a.id, a.titulo, a.marca, a.modelo_ano, a.quilometragem, a.preco, a.potencia_hp, a.transmissao, a.destaque, a.tipo_combustivel,
            (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
        FROM 
            anuncios a
        {$where_sql}
        ORDER BY 
            a.destaque DESC, a.data_criacao DESC
    ");
    
    if ($stmt_inventory) {
        if (!empty($bind_types)) { 
            $ref_bind_params = [];
            foreach ($bind_params as $key => $value) {
                $ref_bind_params[$key] = &$bind_params[$key];
            }
            array_unshift($ref_bind_params, $bind_types);
            call_user_func_array([$stmt_inventory, 'bind_param'], $ref_bind_params);
        }
        
        $stmt_inventory->execute();
        $result_inventory = $stmt_inventory->get_result();
        $listings = $result_inventory ? $result_inventory->fetch_all(MYSQLI_ASSOC) : [];
        $stmt_inventory->close();
    }
} 

if (empty($listings)) {
    $sql_all_active_inventory = "
        SELECT 
            a.id, a.titulo, a.marca, a.modelo_ano, a.quilometragem, a.preco, a.potencia_hp, a.transmissao, a.destaque, a.tipo_combustivel,
            (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
        FROM 
            anuncios a
        WHERE a.status = 'Ativo'
        ORDER BY 
            a.destaque DESC, a.data_criacao DESC
    ";
    $result_all_active = $conn->query($sql_all_active_inventory);
    $display_listings = $result_all_active ? $result_all_active->fetch_all(MYSQLI_ASSOC) : [];
    $fallback_message = $filter_applied && empty($listings) && !empty($display_listings);
} else {
    $display_listings = $listings;
    $filter_successful = !empty($listings);
    $fallback_message = false;
}

$is_filtered = isset($_GET['filter']);
$current_filters = $_GET;

// Prepare slider data
$min_price_val = floor($stats['min_price'] ?? 0);
$max_price_val = ceil($stats['max_price'] ?? 100000);
$min_km_val = floor($stats['min_km'] ?? 0);
$max_km_val = ceil($stats['km_max'] ?? 300000);

$slider_max_price = isset($current_filters['preco_max']) ? (int)$current_filters['preco_max'] : $max_price_val;
$slider_max_km = isset($current_filters['km_max']) ? (int)$current_filters['km_max'] : $max_km_val;

function format_price_filter($value) { return '€ ' . number_format($value, 0, ',', '.'); }
function format_km_filter($value) { return number_format($value, 0, ',', '.') . ' km'; }
function format_km_card($value) { return number_format($value, 0, ',', '.') . ' km'; }
function format_price_card($value) { return '€ ' . number_format($value, 0, ',', '.'); }

$testimonials = [
    ['name' => 'Marcos Antônio de Souza Monteiro', 'time' => 'há um ano', 'text' => 'Experiência muito boa. Carro de boa procedência, em excelente estado de conservação de lataria e mecânica. Muito bem atendido e já sai com seguro. Recomendo!!!'],
    ['name' => 'Renato Ribeiro', 'time' => 'há 2 anos', 'text' => 'Experiência premium! Assiduidade, pontualidade, conforto e segurança, são algumas das muitas qualidades da WFCARS.'],
    ['name' => 'Silva', 'time' => 'há um ano', 'text' => 'Total profissionalismo em todo o processo de compra, excelência no serviço e atendimento, recomendo 100%.'],
    ['name' => 'Vitor Amorim', 'time' => 'há 2 anos', 'text' => 'Atendimento de primeira, sempre dando atenção as exigências e dúvidas do cliente durante todo processo.'],
    ['name' => 'Gilda Alves', 'time' => 'há 2 anos', 'text' => 'Muito satisfeita com a minha nova viatura! Atendimento excelente.'],
    ['name' => 'Carlos Miguel Sousa', 'time' => 'há 2 anos', 'text' => 'Muito contente com o meu novo carro! Empresa de confianca !!!'],
    ['name' => 'Armando Pedro Pereira', 'time' => 'há um ano', 'text' => 'Recomendo do melhor que já vi em todos os aspectos. Máxima confiança e transparência.'],
    ['name' => 'Ivan Rocha', 'time' => 'há 7 meses', 'text' => '5 estrelas! Profissionalismo e qualidade garantida.'],
    ['name' => 'Igor Rocha', 'time' => 'há 11 meses', 'text' => 'Serviço de alta qualidade e veículos de excelência. Recomendo.'],
    ['name' => 'Beatriz Santos', 'time' => 'há um ano', 'text' => 'Excelente negócio! Transparência e rapidez no processo.'],
    ['name' => 'Jose Galguinho', 'time' => 'há um ano', 'text' => 'Profissionais sérios e de confiança. A repetir.'],
    ['name' => 'Sergio Manuel Ribeiro Martins', 'time' => 'há um ano', 'text' => 'Melhor stand! Atendimento e viaturas top.'],
];
?>
<!DOCTYPE html>
<html lang="pt-PT" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>WFCARS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark-primary': 'var(--color-dark-primary)',
                        'dark-card': 'var(--color-dark-card)',
                        'highlight': 'var(--color-highlight)',
                        'subtle': 'var(--color-subtle)',
                        'light-accent': 'var(--color-light-accent)'
                    },
                    keyframes: {
                        'shine': {
                            '0%': { "transform": 'translateX(-100%) skewX(-20deg)' },
                            '100%': { "transform": 'translateX(200%) skewX(-20deg)' } 
                        },
                        'floatY': {
                            '0%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-6px)' },
                            '100%': { "transform": 'translateY(0)' }
                        }
                    },
                    animation: {
                        'shine': 'shine 1.8s linear infinite',
                        'floatY': 'floatY 6s ease-in-out infinite'
                    }
                }
            }
        }
    </script>

    <style>
        :root{
            --color-dark-primary: #070708;
            --color-dark-card: #0f1114;
            --color-highlight: #C8C8CA; /* prata brilhante */
            --color-subtle: #9aa0a6;
            --color-light-accent: #141618;
        }

        html,body{height:100%;}
        body{font-family:'Poppins',sans-serif;background:var(--color-dark-primary);color:#EFEFEF;-webkit-font-smoothing:antialiased;}

        /* --- PRELOADER LUXURY --- */
        #preloader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background-color: #000000;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 1s cubic-bezier(0.7, 0, 0.3, 1), visibility 1s linear;
        }

        .loader-container {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .logo-reveal-box {
            position: relative;
            overflow: hidden;
            padding: 10px;
            margin-bottom: 20px;
        }

        .loader-logo {
            height: 80px; 
            width: auto;
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            filter: drop-shadow(0 0 0px rgba(200,200,200,0));
            animation: logoFadeUp 1.2s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .logo-shine {
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(
                to right, 
                transparent 0%, 
                rgba(255, 255, 255, 0.1) 40%, 
                rgba(255, 255, 255, 0.6) 50%, 
                rgba(255, 255, 255, 0.1) 60%, 
                transparent 100%
            );
            transform: skewX(-25deg);
            z-index: 10;
            pointer-events: none;
            mix-blend-mode: overlay;
            animation: lightPass 2.5s infinite 0.5s;
        }

        .loader-line {
            width: 0%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--color-highlight), transparent);
            box-shadow: 0 0 15px var(--color-highlight);
            border-radius: 2px;
            animation: loadProgress 2s ease-in-out forwards;
        }

        @keyframes logoFadeUp {
            0% { opacity: 0; transform: translateY(20px) scale(0.95); filter: drop-shadow(0 0 0 rgba(200,200,200,0));}
            100% { opacity: 1; transform: translateY(0) scale(1); filter: drop-shadow(0 0 15px rgba(200,200,200,0.2)); }
        }

        @keyframes lightPass {
            0% { left: -100%; opacity: 0; }
            40% { opacity: 1; }
            60% { opacity: 1; }
            100% { left: 200%; opacity: 0; }
        }

        @keyframes loadProgress {
            0% { width: 0%; opacity: 0.5; }
            50% { width: 60%; opacity: 1; }
            100% { width: 100%; opacity: 0; }
        }

        @media(max-width: 768px) {
            .loader-logo { height: 60px; }
        }
        /* --- FIM PRELOADER --- */

        /* ESTILO MERCEDES (DIDONE / BODONI) */
        .font-mercedes {
            font-family: 'Bodoni Moda', serif;
            letter-spacing: -0.02em; /* Letras ligeiramente juntas, estilo premium */
        }

        /* UTILITY: Chrome Text */
        .chrome-text{background:linear-gradient(90deg,#fff,#c8c8c8,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 0 8px rgba(200,200,200,0.12)}

        /* Card shimmer border (chrome frame) */
        .chrome-frame{position:relative;border-radius:14px}
        .chrome-frame::before{content:'';position:absolute;inset:-1px;border-radius:15px;padding:1px;background:linear-gradient(90deg,#bfbfbf44,#fff8,#bfbfbf44);-webkit-mask:linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);mask-composite:exclude;pointer-events:none}

        /* Hero tweaks */
        .hero-background{background-size:cover;background-position:center 35%;min-height:72vh}
        @media(min-width:1024px){.hero-background{min-height:92vh;background-position:center 55%}}

        /* Buttons */
        .btn-silver{background:linear-gradient(180deg,var(--color-highlight),#f5f5f5);color:#0b0b0b;font-weight:800}
        .btn-silver:focus{outline:2px solid rgba(200,200,200,0.18);outline-offset:2px}

        /* Footer subtle */
        footer a{color:var(--color-subtle)}
        
        /* Menu PC - Mais fino e elegante (Usando Playfair aqui para contraste subtil) */
        .header-nav-link {
            font-weight: 300; 
            letter-spacing: 0.05em; 
            text-shadow: 0 0 10px rgba(200,200,200,0.1);
            transition: color 0.3s;
            font-size: 1.1rem; 
            font-family: 'Playfair Display', serif; 
        }
        .header-nav-link:hover {
            color: var(--color-highlight);
        }
        
        /* Swiper Arrows */
        .mySwiper .swiper-button-next,
        .mySwiper .swiper-button-prev {
            color: var(--color-highlight) !important;
            background-color: var(--color-dark-card) !important;
            width: 48px; 
            height: 48px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s;
            opacity: 0.8;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.8);
        }

        .mySwiper .swiper-button-next:hover,
        .mySwiper .swiper-button-prev:hover {
            opacity: 1;
            background: linear-gradient(180deg, var(--color-highlight), #fff);
            color: var(--color-dark-primary) !important;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.4);
        }
        
        /* Link de Detalhes Premium */
        .premium-details-link {
            color: #0b0b0b; 
            font-weight: 800;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center; 
            gap: 0.5rem;
            text-shadow: none;
        }
        .premium-details-link:hover {
            color: var(--color-dark-primary) !important;
            transform: none; 
        }
        
        /* Estilo para Inputs Range (Desktop) */
        input[type=range] {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            background: #333;
            border-radius: 5px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--color-highlight);
            cursor: pointer;
            box-shadow: 0 0 5px rgba(255, 255, 255, 0.4);
            transition: background 0.15s ease-in-out;
        }
        .range-label {
            font-size: 0.8rem;
            color: var(--color-subtle);
            display: flex;
            justify-content: space-between;
        }
        
        /* Preço Button */
        .price-tag-highlight-button {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(180deg, var(--color-highlight), #f5f5f5);
            color: #0b0b0b; 
            font-weight: 800;
            font-size: 0.875rem; 
            padding: 0.4rem 0.8rem; 
            border-radius: 9999px; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
            z-index: 10; 
            text-shadow: none;
        }

        /* ESTILOS DE COMPACTAÇÃO DE CARD (HERDADOS DO INVENTORY.PHP) */
        .card-footer-pc {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--color-highlight)/10;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
        }

        .featured-tag-mobile { /* Destaque no canto superior esquerdo */
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 0.3rem 0.6rem;
            font-size: 0.7rem;
            z-index: 10;
            background-color: #facc15;
            color: black;
            font-weight: 700;
            border-radius: 4px;
            text-transform: uppercase;
        }
        
        .year-highlight-desktop {
            font-size: 1.25rem; /* text-xl */
            font-weight: 600; /* font-semibold */
            color: var(--color-highlight);
        }
        .year-highlight-mobile {
            display: none; 
        }
        
        /* ================================================= */
        /* == MENU ESTILO ROLLS ROYCE (MOBILE APENAS) == */
        /* ================================================= */
        
        .mobile-menu-overlay {
            /* Estado Inicial (Escondido) */
            position: fixed;
            inset: 0;
            z-index: 50;
            background: #000000; /* Fundo Preto Puro */
            display: flex; 
            flex-direction: column;
            justify-content: center;
            align-items: flex-start; /* Alinhado à esquerda como no vídeo mobile */
            padding-left: 2.5rem; /* Padding esquerdo */
            
            /* Transição */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Navegação Interna */
        .mobile-menu-overlay nav {
            display: flex;
            flex-direction: column;
            gap: 1.5rem; /* Espaçamento vertical */
            width: 100%;
        }

        .mobile-menu-overlay nav a {
            font-family: 'Manrope', sans-serif; /* Fonte Geométrica Sans-Serif Limpa (Estilo RR) */
            font-size: 1.2rem;
            font-weight: 400; 
            letter-spacing: 0.2em; /* Tracking muito largo */
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6); /* Cor base */
            transition: all 0.4s ease;
            
            /* Animação Stagger */
            opacity: 0;
            transform: translateY(20px);
        }

        /* Estado Ativo do Menu (Animação de Entrada) */
        .mobile-menu-overlay.active nav a {
            opacity: 1;
            transform: translateY(0);
        }

        /* Delays para efeito cascata */
        .mobile-menu-overlay.active nav a:nth-child(1) { transition-delay: 0.1s; }
        .mobile-menu-overlay.active nav a:nth-child(2) { transition-delay: 0.15s; }
        .mobile-menu-overlay.active nav a:nth-child(3) { transition-delay: 0.2s; }
        .mobile-menu-overlay.active nav a:nth-child(4) { transition-delay: 0.25s; }

        /* Hover Effect */
        .mobile-menu-overlay nav a:hover,
        .mobile-menu-overlay nav a:active {
            color: #fff;
            padding-left: 10px; /* Ligeiro movimento */
            text-shadow: 0 0 15px rgba(255,255,255,0.4);
        }

        /* Botão Fechar Customizado (Estilo RR Video) - CORRIGIDO PARA A DIREITA */
        .rr-close-btn {
            position: absolute;
            top: 25px;
            right: 25px; /* TOPO DIREITO */
            left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-family: 'Manrope', sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            background: none;
            border: none;
            cursor: pointer;
            z-index: 60;
            flex-direction: row-reverse; /* Inverte a ordem para ficar Texto - Icone */
        }

        .rr-close-icon {
            width: 32px;
            height: 32px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: border-color 0.3s;
        }
        
        .rr-close-btn:hover .rr-close-icon {
            border-color: white;
        }
        
        /* FAQ Icon */
        .faq-icon {
            font-size: 1.5rem; 
            transition: transform 0.3s;
            display: block; 
            line-height: 1; 
        }
        
        /* Testimonial Card */
        .testimonial-card {
            position: relative;
            background: var(--color-dark-card); /* Cor de fundo */
            border: 1px solid var(--color-highlight)/20; /* Borda subtil */
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            overflow: hidden;
            min-height: 220px; /* Altura base para desktop */
            display: flex; /* Flexbox para centralizar conteúdo verticalmente */
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        .quote-icon {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 8rem; /* Tamanho grande */
            font-family: serif; /* Aspas clássicas */
            color: var(--color-highlight);
            opacity: 0.08; /* Muito subtil */
            pointer-events: none;
            z-index: 0;
            line-height: 1;
        }
        
        /* WhatsApp Button */
        #whatsapp-float-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 55px;
            height: 55px;
            background: linear-gradient(180deg, #b0b0b0, #9a9a9a); 
            color: #0b0b0b; 
            border-radius: 50%;
            text-align: center;
            font-size: 28px;
            font-weight: 800; 
            line-height: 55px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s, background 0.3s;
        }

        #whatsapp-float-btn:hover {
            transform: scale(1.1);
            background: linear-gradient(180deg, #d4d4d4, #b0b0b0);
            box-shadow: 0 6px 16px rgba(255, 255, 255, 0.15); 
        }
        
        /* Custom Checkbox (Manual Verification) */
        .custom-checkbox-input {
            width: 1.2rem;
            height: 1.2rem;
            cursor: pointer;
            border: 1px solid var(--color-highlight);
            background: transparent;
            border-radius: 4px;
            accent-color: var(--color-highlight);
        }
        
        /* ================================================= */
        /* == MOBILE HYPER-OPTIMIZATION (Max 767px) == */
        /* ================================================= */

        @media(max-width: 767px) {
            /* == GERAL & LAYOUT == */
            .antialiased h2 {
                font-size: 2rem !important;
                margin-bottom: 1rem !important;
            }
            .antialiased p.text-subtle {
                 font-size: 0.9rem;
            }
            .py-20 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
            .py-28 { padding-top: 3rem !important; padding-bottom: 3rem !important; }
            .lg\:py-28 { padding-top: 3rem !important; padding-bottom: 3rem !important; }
            
            /* == HEADER == */
            header .py-4 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
            header .h-16 { height: 4rem !important; } 
            
            /* == HERO (AJUSTE PRINCIPAL) */
            .hero-background{ 
                min-height: 75vh; 
                background-position: center 70%; 
            } 
            
            .hero-background .h-full.max-w-7xl { 
                align-items: center !important; 
                text-align: center !important; 
                padding-top: 5rem !important; 
                padding-bottom: 0 !important; 
            }
            
            .hero-background .lg\:w-1\/2 {
                width: 100% !important;
                text-align: center !important;
            }
            
            #search-bar-mobile {
                margin-top: 0 !important;
                padding-top: 1rem !important; 
            }
            #search-bar-mobile > div {
                 padding: 1rem !important;
            }
            
            /* == SLIDER CARD COMPACTATION == */
            .swiper-slide .img-cover { 
                height: 180px !important; 
                object-fit: cover;
                object-position: center 50%;
            } 
            
            .swiper-slide {
                width: 90% !important; 
                padding-left: 0.5rem; 
                padding-right: 0.5rem;
            }
            .swiper-slide .card-inner { padding: 0.75rem !important; } 
            
            /* Títulos e Texto: Redução de tamanho */
            .swiper-slide .text-xl { 
                font-size: 0.9rem !important; 
                font-weight: 800 !important;
                margin-top: 0.5rem !important;
                line-height: 1.2;
            }
            .swiper-slide .text-2xl {
                 font-size: 0.9rem !important; 
            }
            .swiper-slide .text-subtle {
                font-size: 0.75rem !important;
            }
            
            /* Footer do Card: Compactar e Alinhar (mobile-footer style) */
            .swiper-slide .card-footer-pc { 
                margin-top: 0.75rem !important; 
                padding-top: 0.5rem;
            }
            .swiper-slide .card-footer-pc a.details-button {
                 padding: 0.5rem 1rem !important;
                 font-size: 0.85rem !important;
            }

            .price-tag-highlight-button {
                top: 8px;
                right: 8px;
                font-size: 0.7rem; 
                padding: 0.3rem 0.6rem;
            }
            
            .featured-tag-mobile {
                top: 8px;
                left: 8px;
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }

            .year-highlight-desktop { display: none !important; }
            .year-highlight-mobile {
                display: block !important;
                font-size: 0.9rem !important;
                font-weight: 700;
                color: var(--color-highlight);
            }
            
            /* == FILTROS (#search-bar-mobile) == */
            .search-input { padding: 0.5rem !important; font-size: 0.8rem !important; }
            .range-label { font-size: 0.65rem !important; }
            .md\:col-span-6 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            
            /* Testimonials Swiper Fixes */
            .mySwiperTestimonials .swiper-wrapper {
                align-items: stretch; 
            }

            .mySwiperTestimonials .swiper-slide {
                width: 100% !important; 
                padding-left: 0; 
                padding-right: 0;
            }
            
            .testimonial-card {
                min-height: 250px !important; 
                padding: 1.5rem !important; 
            }
            
            .testimonial-card .quote-icon {
                font-size: 5rem !important;
                opacity: 0.05;
                top: -10px;
            }
            
            .testimonial-card p {
                font-size: 0.95rem !important;
            }
            
            #whatsapp-float-btn {
                width: 50px; 
                height: 50px;
                font-size: 25px;
                line-height: 50px;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(180deg, #b0b0b0, #9a9a9a) !important; 
                color: #0b0b0b !important;
                font-weight: 800;
            }
        }
    </style>
</head>
<body class="antialiased bg-dark-primary text-white" data-filtered="<?php echo $is_filtered ? 'true' : 'false'; ?>">

    <div id="preloader">
        <div class="loader-container">
            <div class="logo-reveal-box">
                <div class="logo-shine"></div>
                <img src="logo.png" alt="WF Cars" class="loader-logo" />
            </div>
            <div class="w-24 md:w-32 h-1 bg-white/10 rounded-full overflow-hidden mt-4">
                <div class="loader-line"></div>
            </div>
        </div>
    </div>

    <header class="fixed w-full z-40 top-0 left-0 bg-dark-primary/80 backdrop-blur-md border-b border-highlight/20">
        <nav class="max-w-7xl mx-auto flex items-center justify-between px-6 lg:px-12 py-4">
            <a href="index.php" class="flex items-center gap-4">
                <img src="logo.png" alt="WF Cars" class="h-16 header-logo-img" />
                </a>

            <div class="hidden lg:flex items-center gap-8">
                <a href="#about-faq" class="header-nav-link text-subtle hover:text-highlight transition">SOBRE NÓS</a>
                <a href="inventory.php" class="header-nav-link text-highlight transition">VIATURAS EM STOCK</a> 
                <a href="#contact" class="ml-4 btn-silver px-5 py-2 rounded-md shadow">FALE CONNOSCO</a>
            </div>

            <button id="open-menu" class="lg:hidden text-2xl text-subtle"><i class="fa fa-bars"></i></button>
        </nav>
    </header>

    <div id="mobile-menu-overlay" class="mobile-menu-overlay">
        
        <button id="close-menu" class="rr-close-btn">
            <div class="rr-close-icon"><i class="fas fa-times text-xs"></i></div>
            CLOSE
        </button>
        
        <nav>
            <a href="#about-faq" onclick="closeMobileMenu()">SOBRE NÓS</a>
            <a href="inventory.php" onclick="closeMobileMenu()">VIATURAS EM STOCK</a>
            <a href="#contact" onclick="closeMobileMenu()">CONTACTO</a>
            <a href="https://wa.me/351910291038" target="_blank" onclick="closeMobileMenu()">WHATSAPP</a>
        </nav>
        
        <div class="absolute bottom-10 left-10 text-subtle text-xs tracking-widest font-sans opacity-50">WF CARS © 2025</div>
    </div>

    <main class="pt-20">

        <section id="hero" class="relative hero-background" style="background-image:url('heroimage2.jpeg')">
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-transparent"></div>

            <div class="h-full max-w-7xl mx-auto px-6 lg:px-12 relative z-20 flex flex-col lg:flex-row items-end lg:justify-between pt-20 lg:py-20">
                
                <div class="lg:w-1/2 text-center lg:text-left">
                    <p class="uppercase text-subtle tracking-widest mb-4">A Sua Próxima História.</p>
                    
                    <h1 class="text-lg sm:text-xl md:text-2xl font-mercedes font-medium leading-relaxed max-w-4xl">
                        <span class="block chrome-text">
                            Não sejais administradores de medos, mas empreendedores de sonhos!
                        </span>
                    </h1>

                    <div class="mt-8 hidden lg:flex flex-row gap-3 sm:gap-4 items-center justify-start max-w-xs mx-auto lg:mx-0">
                        <a href="#inventory" class="btn-silver px-6 py-2 rounded-md text-base shadow-lg w-full lg:w-auto lg:text-sm">VER COLEÇÃO</a>
                        <a href="#contact" class="border border-highlight px-6 py-2 rounded-md text-base tracking-wide hover:bg-white/5 transition w-full lg:w-auto lg:text-sm">SOLICITAR CONSULTA</a>
                    </div>
                </div>
                
            </div> 
        </section>

        <section id="search-bar-mobile" class="px-6 relative z-30 lg:hidden">
            <div class="max-w-6xl mx-auto bg-dark-card/90 border border-highlight/20 rounded-xl p-4 shadow-xl">
                <form method="GET" action="#inventory" class="grid grid-cols-2 md:grid-cols-6 lg:grid-cols-12 gap-3">
                    <input type="hidden" name="filter" value="1">
                    
                    <select name="marca" class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white col-span-2 md:col-span-3 lg:col-span-2">
                        <option value="all" <?php echo empty($current_filters['marca']) || $current_filters['marca'] === 'all' ? 'selected' : ''; ?>>MARCA</option>
                        <?php foreach ($brands as $brand): ?>
                            <?php $brand_name = htmlspecialchars($brand['marca']); ?>
                            <option value="<?php echo $brand_name; ?>" <?php echo isset($current_filters['marca']) && $current_filters['marca'] === $brand_name ? 'selected' : ''; ?>>
                                <?php echo strtoupper($brand_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="combustivel" class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white col-span-1 md:col-span-1 lg:col-span-2">
                        <option value="all" <?php echo empty($current_filters['combustivel']) || $current_filters['combustivel'] === 'all' ? 'selected' : ''; ?>>COMB.</option>
                        <?php foreach ($fuel_types as $fuel): ?>
                            <option value="<?php echo $fuel; ?>" <?php echo isset($current_filters['combustivel']) && $current_filters['combustivel'] === $fuel ? 'selected' : ''; ?>>
                                <?php echo $fuel; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="transmissao" class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white col-span-1 md:col-span-2 lg:col-span-2">
                        <option value="all" <?php echo empty($current_filters['transmissao']) || $current_filters['transmissao'] === 'all' ? 'selected' : ''; ?>>TRANS.</option>
                        <?php foreach ($transmissions as $trans): ?>
                            <option value="<?php echo $trans; ?>" <?php echo isset($current_filters['transmissao']) && $current_filters['transmissao'] === $trans ? 'selected' : ''; ?>>
                                <?php echo $trans; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="col-span-2 md:col-span-6 flex flex-col justify-center px-1 lg:col-span-2">
                        <label for="preco_max_mobile" class="range-label mb-1">
                            Preço Máximo: <span id="maxPriceValueDisplayMobile"><?php echo format_price_filter($slider_max_price); ?></span>
                        </label>
                        <input type="range" id="preco_max_mobile" name="preco_max" min="<?php echo $min_price_val; ?>" max="<?php echo $max_price_val; ?>" step="1000" value="<?php echo $slider_max_price; ?>" oninput="document.getElementById('maxPriceValueDisplayMobile').textContent = '€ ' + this.value.toLocaleString('pt-PT', { minimumFractionDigits: 0, maximumFractionDigits: 0 });">
                        <span class="range-label mt-0.5"><span class="font-bold text-highlight"><?php echo format_price_filter($min_price_val); ?></span><span class="font-bold text-highlight"><?php echo format_price_filter($max_price_val); ?></span></span>
                    </div>
                    
                    <div class="col-span-2 md:col-span-6 flex flex-col justify-center px-1 lg:col-span-2">
                        <label for="km_max_mobile" class="range-label mb-1">
                            KM Máximo: <span id="maxKmValueDisplayMobile"><?php echo format_km_filter($slider_max_km); ?></span>
                        </label>
                        <input type="range" id="km_max_mobile" name="km_max" min="<?php echo $min_km_val; ?>" max="<?php echo $max_km_val; ?>" step="10000" value="<?php echo $slider_max_km; ?>" oninput="document.getElementById('maxKmValueDisplayMobile').textContent = this.value.toLocaleString('pt-PT') + ' km';">
                        <span class="range-label mt-0.5"><span class="font-bold text-highlight"><?php echo format_km_filter($min_km_val); ?></span><span class="font-bold text-highlight"><?php echo format_km_filter($max_km_val); ?></span></span>
                    </div>

                    <button type="submit" class="col-span-2 md:col-span-6 btn-silver flex items-center justify-center gap-2 lg:col-span-2">
                        <i class="fa fa-search"></i>
                        <span class="inline">PESQUISAR</span>
                    </button>
                </form>
            </div>
        </section>

        <section id="search-bar-desktop" class="px-6 lg:px-12 -mt-12 relative z-30 hidden lg:block">
            <div class="max-w-6xl mx-auto bg-dark-card/90 border border-highlight/20 rounded-xl p-4 shadow-xl">
                <form method="GET" action="#inventory" class="grid grid-cols-2 md:grid-cols-6 lg:grid-cols-12 gap-3">
                    <input type="hidden" name="filter" value="1">
                    
                    <select name="marca" class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white col-span-2 md:col-span-3 lg:col-span-2">
                        <option value="all" <?php echo empty($current_filters['marca']) || $current_filters['marca'] === 'all' ? 'selected' : ''; ?>>MARCA</option>
                        <?php foreach ($brands as $brand): ?>
                            <?php $brand_name = htmlspecialchars($brand['marca']); ?>
                            <option value="<?php echo $brand_name; ?>" <?php echo isset($current_filters['marca']) && $current_filters['marca'] === $brand_name ? 'selected' : ''; ?>>
                                <?php echo strtoupper($brand_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="combustivel" class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white col-span-1 md:col-span-1 lg:col-span-2">
                        <option value="all" <?php echo empty($current_filters['combustivel']) || $current_filters['combustivel'] === 'all' ? 'selected' : ''; ?>>COMB.</option>
                        <?php foreach ($fuel_types as $fuel): ?>
                            <option value="<?php echo $fuel; ?>" <?php echo isset($current_filters['combustivel']) && $current_filters['combustivel'] === $fuel ? 'selected' : ''; ?>>
                                <?php echo $fuel; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="transmissao" class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white col-span-1 md:col-span-2 lg:col-span-2">
                        <option value="all" <?php echo empty($current_filters['transmissao']) || $current_filters['transmissao'] === 'all' ? 'selected' : ''; ?>>TRANS.</option>
                        <?php foreach ($transmissions as $trans): ?>
                            <option value="<?php echo $trans; ?>" <?php echo isset($current_filters['transmissao']) && $current_filters['transmissao'] === $trans ? 'selected' : ''; ?>>
                                <?php echo $trans; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="col-span-2 md:col-span-6 flex flex-col justify-center px-1 lg:col-span-2">
                        <label for="preco_max_desktop" class="range-label mb-1">
                            Preço Máximo: <span id="maxPriceValueDisplayDesktop"><?php echo format_price_filter($slider_max_price); ?></span>
                        </label>
                        <input type="range" id="preco_max_desktop" name="preco_max" min="<?php echo $min_price_val; ?>" max="<?php echo $max_price_val; ?>" step="1000" value="<?php echo $slider_max_price; ?>" oninput="document.getElementById('maxPriceValueDisplayDesktop').textContent = '€ ' + this.value.toLocaleString('pt-PT', { minimumFractionDigits: 0, maximumFractionDigits: 0 });">
                        <span class="range-label mt-0.5"><span class="font-bold text-highlight"><?php echo format_price_filter($min_price_val); ?></span><span class="font-bold text-highlight"><?php echo format_price_filter($max_price_val); ?></span></span>
                    </div>
                    
                    <div class="col-span-2 md:col-span-6 flex flex-col justify-center px-1 lg:col-span-2">
                        <label for="km_max_desktop" class="range-label mb-1">
                            KM Máximo: <span id="maxKmValueDisplayDesktop"><?php echo format_km_filter($slider_max_km); ?></span>
                        </label>
                        <input type="range" id="km_max_desktop" name="km_max" min="<?php echo $min_km_val; ?>" max="<?php echo $max_km_val; ?>" step="10000" value="<?php echo $slider_max_km; ?>" oninput="document.getElementById('maxKmValueDisplayDesktop').textContent = this.value.toLocaleString('pt-PT') + ' km';">
                        <span class="range-label mt-0.5"><span class="font-bold text-highlight"><?php echo format_km_filter($min_km_val); ?></span><span class="font-bold text-highlight"><?php echo format_km_filter($max_km_val); ?></span></span>
                    </div>

                    <button type="submit" class="col-span-2 md:col-span-6 btn-silver flex items-center justify-center gap-2 lg:col-span-2">
                        <i class="fa fa-search"></i>
                        <span class="inline">PESQUISAR</span>
                    </button>
                </form>
            </div>
        </section>

        
        <section id="inventory" class="py-12 lg:py-24 px-6 lg:px-12 bg-dark-primary">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8">
                    <div>
                        <h2 class="text-3xl lg:text-4xl font-extrabold">Coleção <span class="chrome-text">Exclusiva</span></h2>
                        <p class="text-subtle">Viaturas que são investimentos e declarações de intenção.</p>
                    </div>
                    <div class="flex gap-4 mt-4 sm:mt-0 w-full sm:w-auto"> 
                        <a href="index.php#inventory" id="reset-filters-link" class="text-subtle hover:text-highlight text-sm whitespace-nowrap flex items-center gap-1 btn-silver px-4 py-2 rounded-md font-bold shadow-xl">
                            Reiniciar Filtros <i class="fa fa-redo text-xs"></i>
                        </a>
                        <a href="inventory.php" class="btn-silver px-4 py-2 rounded-md text-sm font-bold shadow-xl hover:bg-white transition duration-300 flex items-center justify-center">
                            VER TUDO <i class="fa fa-arrow-right ml-2 text-xs"></i>
                        </a>
                    </div>
                </div>
                
                <?php if ($fallback_message): ?>
                    <div class="text-center bg-dark-card/80 p-6 rounded-xl my-4 border border-highlight/30">
                            <i class="fas fa-exclamation-triangle text-highlight text-2xl mb-2"></i>
                            <h3 class="text-lg font-semibold text-highlight mb-2">Não encontramos nenhum veículo que corresponda aos seus filtros.</h3>
                            <p class="text-subtle text-sm max-w-xl mx-auto">Mas não desista! Veja a nossa **seleção exclusiva de carros em Destaque** abaixo.</p>
                    </div>
                <?php endif; ?>

                <?php if (empty($display_listings)): ?>
                    <p class="text-subtle p-8 w-full text-center">Nenhum veículo ativo encontrado no catálogo.</p>
                <?php else: ?>
                    <div class="swiper mySwiper">
                        <div class="swiper-wrapper">
                        
                        <?php foreach ($display_listings as $car): 
                            $image_path = !empty($car['foto_principal']) ? '../' . htmlspecialchars($car['foto_principal']) : 'heroimage.jpeg';
                            $price_formatted = format_price_card($car['preco']);
                            
                            // Linhas de detalhes (Corrigido erro PHP)
                            $details_line_top = $car['modelo_ano'] . ' | ' . htmlspecialchars($car['transmissao']) . ' | ' . $car['potencia_hp'] . ' cv';
                            $details_line_bottom = '<i class="fa fa-tachometer-alt text-subtle/80"></i> ' . format_km_card($car['quilometragem']) . ' | <i class="fa fa-gas-pump text-subtle/80"></i> ' . htmlspecialchars($car['tipo_combustivel']);

                            $is_limited = $car['destaque'] == 1; 
                        ?>
                        <div class="swiper-slide p-3">
                            <div class="relative chrome-frame overflow-hidden rounded-2xl">
                                <div class="card-inner">
                                    <a href="car-details.php?id=<?php echo $car['id']; ?>">
                                        <div class="relative overflow-hidden rounded-xl">
                                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($car['titulo']); ?>" class="w-full h-56 object-cover group-hover:scale-105 transition duration-700 img-cover" />
                                            
                                            <span class="price-tag-highlight-button">
                                                <?php echo $price_formatted; ?>
                                            </span>

                                            <?php if ($is_limited): ?>
                                                <span class="featured-tag-mobile absolute top-4 left-4 px-2 py-0.5 rounded text-xs uppercase font-bold">DESTAQUE</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>

                                    <div class="p-4"> <h3 class="mt-2 text-2xl font-black chrome-text mb-1"><?php echo htmlspecialchars($car['marca']); ?></h3>
                                        <h3 class="text-xl font-bold text-gray-200 mb-2 leading-tight"><?php echo htmlspecialchars($car['titulo']); ?></h3>

                                        <div class="text-subtle text-sm mt-1">
                                            <span class="block"><?php echo $details_line_top; ?></span>
                                            <span class="block mt-1"><?php echo $details_line_bottom; ?></span>
                                        </div>

                                        <div class="card-footer-pc">
                                            <span class="text-xl font-semibold year-highlight-desktop">
                                                <?php echo $car['modelo_ano']; ?>
                                            </span>
                                            <span class="year-highlight-mobile text-gray-400">
                                                <?php echo $car['modelo_ano']; ?>
                                            </span>
                                            
                                            <a href="car-details.php?id=<?php echo $car['id']; ?>" class="premium-details-link text-sm btn-silver details-button px-4 py-2 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all">
                                                DETALHES <i class="fa fa-arrow-right text-sm ml-1"></i>
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                    
                <?php endif; ?>
                </div>
        </section>
        <section id="testimonials" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
            <div class="max-w-7xl mx-auto text-center">
                <h2 class="text-3xl lg:text-4xl font-extrabold mb-4">O Que <span class="chrome-text">Dizem os Nossos Clientes</span></h2>
                <p class="text-subtle mb-10">A excelência no serviço é refletida nas experiências partilhadas.</p>

                <div class="swiper mySwiperTestimonials pb-8">
                    <div class="swiper-wrapper">

                        <?php foreach ($testimonials as $t): ?>
                        <div class="swiper-slide px-3">
                            <div class="testimonial-card relative min-h-[220px]">
                                <span class="quote-icon">“</span>
                                <div class="relative z-10">
                                    <div class="flex gap-1 mb-3 text-yellow-400 justify-center">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                    <p class="text-gray-200 italic"><?php echo htmlspecialchars($t['text']); ?></p>
                                </div>

                                <footer class="mt-6">
                                    <div class="h-0.5 w-20 bg-gradient-to-r from-gray-500 via-white to-gray-500 opacity-60 mb-3 mx-auto"></div>
                                    <p class="text-highlight font-semibold"><?php echo htmlspecialchars($t['name']); ?></p>
                                    <p class="text-subtle text-sm">Crítica de Google · <?php echo htmlspecialchars($t['time']); ?></p>
                                </footer>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                    <div class="swiper-pagination mt-8"></div>
                </div>
            </div>
        </section>
        <section id="features" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl lg:text-4xl font-extrabold mb-6">Quem <span class="chrome-text">Somos</span></h2>
                <p class="text-subtle mb-8 max-w-3xl"></p>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    
                    <div class="lg:col-span-2 bg-dark-card p-8 rounded-xl border-t-4 border-highlight shadow-xl">
                        
                        <p class="mt-4 text-subtle leading-relaxed">A WFcars é <span class="font-bold text-white">especialista na importação</span> e comércio de veículos selecionados, dedicada à excelência no setor automóvel. O nosso compromisso é com a transparência e a confiança, oferecendo um atendimento personalizado para encontrar a solução perfeita para cada cliente.

Trabalhamos para que a sua experiência seja simples, segura e totalmente acompanhada — desde a escolha até à aquisição final. Colocamos o cliente no centro de tudo, garantindo um processo eficiente e sem complicações na compra, venda ou troca do seu veículo.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 lg:gap-8 lg:col-span-2"> 
                        
                        <div class="p-4 sm:p-6 bg-dark-card rounded-xl border border-highlight/10">
                            <p class="text-highlight text-2xl sm:text-3xl font-black mb-1 sm:mb-3">01</p>
                            <h4 class="text-sm sm:text-xl font-semibold">Curadoria de Alto Padrão</h4>
                            <p class="text-subtle mt-1 sm:mt-2 text-xs sm:text-base">Seleção e certificação rigorosa de veículos premium. A qualidade e proveniência são a nossa garantia de investimento.</p>
                        </div>

                        <div class="p-4 sm:p-6 bg-dark-card rounded-xl border border-highlight/10">
                            <p class="text-highlight text-2xl sm:text-3xl font-black mb-1 sm:mb-3">02</p>
                            <h4 class="text-sm sm:text-xl font-semibold">Excelência no Pós-Venda</h4>
                            <p class="text-subtle mt-1 sm:mt-2 text-xs sm:text-base">Garantia total (18 meses) e manutenção exclusiva, maximizando o desempenho e valor a longo prazo.</p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </section>

        <section id="about-faq" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-card">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl lg:text-5xl font-extrabold mb-6">As Suas <span class="chrome-text">Questões Frequentes</span></h2>

                    <div id="faq-accordion" class="space-y-4 mt-6">
                        <div class="faq-item border-b border-highlight/10 pb-4">
                            <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> 
                                <span class="font-semibold">Todos os carros têm garantias?</span> 
                                <span class="faq-icon text-highlight">+</span> 
                            </button>
                            <div class="faq-content mt-3 text-subtle hidden">Sim, todos os carros têm <span class="font-bold text-white">garantia de 18 meses, sem limite de km</span>.</div>
                        </div>

                        <div class="faq-item border-b border-highlight/10 pb-4">
                            <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> 
                                <span class="font-semibold">Fazem vendas 100% online?</span> 
                                <span class="faq-icon text-highlight">+</span> 
                            </button>
                            <div class="faq-content mt-3 text-subtle hidden">Sim, por várias vezes já fizemos vendas 100% online, entregando em casa do cliente sem custos adicionais.</div>
                        </div>

                        <div class="faq-item border-b border-highlight/10 pb-4">
                            <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> 
                                <span class="font-semibold">Qual a origem dos automóveis?</span> 
                                <span class="faq-icon text-highlight">+</span> 
                            </button>
                            <div class="faq-content mt-3 text-subtle hidden">Temos fornecedores espalhados por alguns países na europa, todas as viaturas são testadas e passam por testes de verificação de qualidade antes de serem compradas.</div>
                        </div>
                        
                        <div class="faq-item border-b border-highlight/10 pb-4">
                            <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> 
                                <span class="font-semibold">Fazem financiamento?</span> 
                                <span class="faq-icon text-highlight">+</span> 
                            </button>
                            <div class="faq-content mt-3 text-subtle hidden">Sim, damos um parecer (positivo ou negativo) em apenas 2 horas.</div>
                        </div>
                        
                        <div class="faq-item border-b border-highlight/10 pb-4">
                            <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> 
                                <span class="font-semibold">Vendem carros por encomenda?</span> 
                                <span class="faq-icon text-highlight">+</span> 
                            </button>
                            <div class="faq-content mt-3 text-subtle hidden">Sim, tratamos de todo o processo até ter a chave na mão, entre em contacto conosco pelo formulário ou pelo wpp.</div>
                        </div>
                        
                        <div class="faq-item border-b border-highlight/10 pb-4">
                            <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> 
                                <span class="font-semibold">Quantos dias demora o processo de compra?</span> 
                                <span class="faq-icon text-highlight">+</span> 
                            </button>
                            <div class="faq-content mt-3 text-subtle hidden">Se for pago a pronto, entregamos no próprio dia, a financiamento 2/3 dias e por encomenda em 7 dias.</div>
                        </div>
                    </div>
                </div>

                <div class="relative mt-8 lg:mt-0 flex justify-center lg:justify-center">
                    <div class="relative h-[350px] lg:h-[500px] w-fit rounded-xl overflow-hidden shadow-2xl">
                        <img src="keys.jpeg" alt="Chave de carro" class="h-full w-auto object-cover" />
                        
                        <div class="absolute bottom-4 right-4 bg-white p-4 rounded-xl border-none text-center shadow-lg">
                            <p class="text-2xl font-black text-dark-primary">100+</p>
                            <p class="text-dark-primary text-xs mt-0.5 font-bold">Clientes Satisfeitos</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="services" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
            <div class="max-w-7xl mx-auto text-center">
                <h2 class="text-3xl lg:text-4xl font-extrabold mb-4">Os Nossos <span class="chrome-text">Pilares</span></h2>
                <p class="text-subtle mb-8 max-w-3xl mx-auto">O seu padrão de excelência em cada serviço.</p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="relative rounded-xl overflow-hidden">
                        <img src="wfcars_service.png" alt="Concierge" class="w-full h-96 object-cover img-cover rounded-xl" />
                        <div class="absolute inset-0 bg-black/50 p-8 flex flex-col justify-end rounded-xl">
                            <h3 class="text-4xl font-black text-highlight">Concierge 360º</h3>
                            <p class="text-white mt-2 text-sm">O seu gestor pessoal, assegurando a excelência desde a consultoria à entrega discreta.</p>
                        </div>
                    </div>

                    <div class="grid grid-rows-3 gap-6">
                        <div class="p-6 bg-dark-card rounded-xl border border-highlight/10 flex items-center gap-4">
                            <div class="text-highlight text-3xl"><i class="fa fa-handshake"></i></div>
                            <div>
                                <h4 class="text-xl font-black">Financiamento Privado e Rápido</h4>
                                <p class="text-subtle text-sm">Financiamento confidencial ou leasing. Parecer rápido (2h), respeitando a sua privacidade.</p>
                            </div>
                        </div>

                        <div class="p-6 bg-dark-card rounded-xl border border-highlight/10 flex items-center gap-4">
                            <div class="text-highlight text-3xl"><i class="fa fa-globe"></i></div>
                            <div>
                                <h4 class="text-xl font-black">Curadoria de Alto Padrão</h4>
                                <p class="text-subtle text-sm">Seleção minuciosa de veículos *premium* europeus. A qualidade e proveniência são a nossa garantia de investimento.</p>
                            </div>
                        </div>

                        <div class="p-6 bg-dark-card rounded-xl border border-highlight/10 flex items-center gap-4">
                            <div class="text-highlight text-3xl"><i class="fa fa-tools"></i></div>
                            <div>
                                <h4 class="text-xl font-black">Garantia & Desempenho Máximo</h4>
                                <p class="text-subtle text-sm"><span class="font-bold text-white">Garantia de 18 meses (s/ limite km)</span> e manutenção certificada, protegendo o seu investimento a longo prazo.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
            <div class="max-w-4xl mx-auto contact-form-container bg-dark-card/70 border border-highlight/10 rounded-xl p-8 shadow-2xl">
                <h2 class="text-3xl lg:text-4xl font-extrabold mb-4">Comece a Sua <span class="chrome-text">Jornada</span></h2>
                <p class="text-subtle mb-4">Confidencialidade e exclusividade no seu contacto.</p>

                <form id="contact-form" class="space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="text-xs sm:text-sm text-highlight">Nome Completo</label>
                            <input name="nome" class="w-full bg-transparent border border-highlight/10 rounded px-3 py-2 sm:px-4 sm:py-2" placeholder="O seu nome" required />
                        </div>
                        <div>
                            <label class="text-xs sm:text-sm text-highlight">Email</label>
                            <input name="email" type="email" class="w-full bg-transparent border border-highlight/10 rounded px-3 py-2 sm:px-4 sm:py-2" placeholder="O seu melhor email" required />
                        </div>
                    </div>

                    <div class="mt-3 sm:mt-4">
                        <label class="text-xs sm:text-sm text-highlight">Telefone</label>
                        <input name="telefone" class="w-full bg-transparent border border-highlight/10 rounded px-3 py-2 sm:px-4 sm:py-2" placeholder="O seu número de contacto (opcional)" />
                    </div>
                    
                    <div class="mt-3 sm:mt-4">
                        <label class="text-xs sm:text-sm text-highlight">Detalhes do Pedido</label>
                        <textarea name="detalhes" class="w-full bg-transparent border border-highlight/10 rounded px-3 py-2 sm:px-4 sm:py-3" rows="3" placeholder="Descreva o veículo ou o serviço que procura em detalhe." required></textarea>
                    </div>

                    <div class="flex items-center gap-2 mb-4 mt-4 bg-black/30 p-3 rounded border border-highlight/20">
                        <input type="checkbox" id="human_check" name="human_verification" value="1" required class="custom-checkbox-input" />
                        <label for="human_check" class="text-sm text-white cursor-pointer">Confirmo que sou humano e pretendo ser contactado.</label>
                    </div>
                    
                    <p id="form-status" class="text-xs text-subtle italic mb-2"></p>

                    <button type="submit" class="btn-silver w-full py-3 rounded font-extrabold hover:opacity-90 transition">SOLICITAR CONTACTO</button>
                </form>
            </div>
        </section>

    </main>

    <footer class="py-16 bg-dark-card border-t border-highlight/10 mt-20">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 px-6 lg:px-12">
            <div>
                <h4 class="text-sm text-highlight uppercase font-bold mb-3">Localização Principal</h4>
                <address class="not-italic text-subtle text-sm">
                    EN1, Av São Salvador de Grijó 35<br/>
                    Vila Nova de Gaia, Portugal 4415-557
                </address>
            </div>

            <div>
                <h4 class="text-sm text-highlight uppercase font-bold mb-3">Fale Connosco</h4>
                <a href="mailto:geral@wfcars.pt" class="block text-white mb-1">geral@wfcars.pt</a>
                <a href="tel:+351910291038" class="block text-white">+351 910 291 038 (Chamada)</a>
            </div>

            <div>
                <h4 class="text-sm text-highlight uppercase font-bold mb-3">Redes Sociais</h4>
                <div class="flex gap-4 text-2xl text-subtle items-center">
                    <a href="https://www.instagram.com/wfcars.pt/" target="_blank" title="Instagram" class="hover:text-white transition"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/351910291038" target="_blank" title="WhatsApp" class="hover:text-white transition"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.facebook.com/people/WFcars/61551061824401/?ref=_xav_ig_profile_page_web" target="_blank" title="Facebook" class="hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
            
        </div>

       <div class="max-w-7xl mx-auto text-center mt-12 text-subtle text-sm px-6 lg:px-12">
            <a href="https://www.livroreclamacoes.pt/" target="_blank" class="inline-block text-white/80 hover:text-highlight transition text-xs border-b border-subtle/50 mb-3">
                Livro de Reclamações Eletrónico
            </a>
            <p class="text-subtle text-sm">&copy; 2025 WF CARS. Todos os direitos reservados.</p>
        </div>
    </footer>
    
    <a href="https://wa.me/351910291038" id="whatsapp-float-btn" target="_blank" title="Fale Connosco pelo WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
    
    <script>
        // --- PRELOADER LOGIC ---
        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            
            setTimeout(() => {
                preloader.style.opacity = '0';
                preloader.style.visibility = 'hidden';
                setTimeout(() => { preloader.remove(); }, 1000);
            }, 1800); 
        });

        // Função para fechar o menu mobile
        function closeMobileMenu() {
            document.getElementById('mobile-menu-overlay').classList.remove('active');
            setTimeout(() => {
                document.getElementById('mobile-menu-overlay').classList.add('hidden');
            }, 500);
        }

        // Lógica do Menu Hamburger (Slide-in)
        document.getElementById('open-menu').addEventListener('click', () => { 
            const menu = document.getElementById('mobile-menu-overlay');
            menu.classList.remove('hidden'); 
            void menu.offsetWidth;
            menu.classList.add('active');
        });
        
        document.getElementById('close-menu').addEventListener('click', closeMobileMenu);

        // --- AJAX FORM SUBMISSION (MANUAL CHECK) ---
        const contactForm = document.getElementById('contact-form');
        if(contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const statusMessage = document.getElementById('form-status');
                const submitBtn = contactForm.querySelector('button[type="submit"]');
                const humanCheck = document.getElementById('human_check');

                // Validação Manual no Frontend (redundante mas bom UX)
                if(!humanCheck.checked) {
                    statusMessage.textContent = "Por favor, marque a caixa de verificação.";
                    statusMessage.className = "text-xs text-red-400 italic mb-2";
                    return;
                }

                statusMessage.textContent = "A enviar...";
                statusMessage.className = "text-xs text-subtle italic mb-2";
                submitBtn.disabled = true;
                submitBtn.textContent = "A enviar...";

                const formData = new FormData(contactForm);
                formData.append('action', 'send_contact');

                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        statusMessage.textContent = data.message;
                        statusMessage.className = "text-xs text-green-400 italic mb-2";
                        contactForm.reset();
                    } else {
                        statusMessage.textContent = "Erro: " + data.message;
                        statusMessage.className = "text-xs text-red-400 italic mb-2";
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    statusMessage.textContent = "Ocorreu um erro de conexão. Tente novamente.";
                    statusMessage.className = "text-xs text-red-400 italic mb-2";
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "SOLICITAR CONTACTO";
                });
            });
        }

        // Lógica do Acordeão (FAQ)
        function toggleFaq(btn){ 
            const item = btn.closest('.faq-item');
            const content = item.querySelector('.faq-content'); 
            const icon = item.querySelector('.faq-icon');
            const isCurrentlyOpen = !content.classList.contains('hidden');

            document.querySelectorAll('.faq-item').forEach(n => {
                const otherContent = n.querySelector('.faq-content');
                const otherIcon = n.querySelector('.faq-icon');
                
                otherContent.classList.add('hidden');
                otherIcon.textContent = '+';
                otherIcon.style.transform = 'rotate(0deg)';
            });
            
            if (!isCurrentlyOpen){ 
                content.classList.remove('hidden'); 
                icon.textContent = '–';
                icon.style.transform = 'rotate(90deg)';
            } 
        }

        // Swiper inits
        document.addEventListener('DOMContentLoaded', ()=>{
            new Swiper('.mySwiper', { 
                slidesPerView: 'auto', 
                centeredSlides: true, 
                spaceBetween:10, 
                loop:false, 
                navigation:{nextEl:'.swiper-button-next',prevEl:'.swiper-button-prev'}, 
                breakpoints:{
                    768:{slidesPerView:2, centeredSlides: false},
                    1024:{slidesPerView:3, centeredSlides: false}
                } 
            });

            new Swiper('.mySwiperTestimonials', { 
                slidesPerView:1, 
                spaceBetween:20, 
                loop:true, 
                pagination:{el:'.swiper-pagination',clickable:true}, 
                autoplay:{ delay: 4500, disableOnInteraction: false },
                breakpoints:{
                    768:{slidesPerView:2, spaceBetween: 30},
                    1024:{slidesPerView:3, spaceBetween: 40}
                } 
            });
            
            // Filtros (Sliders UI Update)
            function formatPriceForDisplay(value) {
                return '€ ' + parseInt(value).toLocaleString('pt-PT', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            }
            function formatKmForDisplay(value) {
                return parseInt(value).toLocaleString('pt-PT') + ' km';
            }
            
            // Desktop & Mobile Logic
            const inputs = [
                {in: 'preco_max_mobile', out: 'maxPriceValueDisplayMobile', format: formatPriceForDisplay},
                {in: 'km_max_mobile', out: 'maxKmValueDisplayMobile', format: formatKmForDisplay},
                {in: 'preco_max_desktop', out: 'maxPriceValueDisplayDesktop', format: formatPriceForDisplay},
                {in: 'km_max_desktop', out: 'maxKmValueDisplayDesktop', format: formatKmForDisplay}
            ];

            inputs.forEach(item => {
                const elIn = document.getElementById(item.in);
                const elOut = document.getElementById(item.out);
                if(elIn && elOut) {
                    elOut.textContent = item.format(elIn.value);
                    elIn.addEventListener('input', (e) => {
                        elOut.textContent = item.format(e.target.value);
                    });
                }
            });
        });
    </script>
</body>
</html>