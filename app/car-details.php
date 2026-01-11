<?php
/**
 * PÁGINA DE DETALHES DO CARRO - VERSÃO FINAL (DESIGN ORIGINAL + LIMPEZA AVANÇADA + MUSIC PLAYER + PREÇO)
 */
include 'db_connect.php'; 

// 1. Obter o ID do carro da URL
$car_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$car_id) {
    header("Location: index.php");
    exit();
}

// 2. Buscar dados do carro (ADICIONADO: preco)
$stmt = $conn->prepare("SELECT 
    id, titulo, descricao, modelo_ano, potencia_hp, quilometragem, transmissao,
    cilindrada_cc, tipo_combustivel, raw_extras, preco 
    FROM anuncios WHERE id = ?"); 
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();
$stmt->close();

if (!$car) {
    header("Location: index.php");
    exit();
}

// 3. Buscar fotos
$photos_stmt = $conn->prepare("SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = ? ORDER BY is_principal DESC, id ASC");
$photos_stmt->bind_param("i", $car_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = $photos_result->fetch_all(MYSQLI_ASSOC);
$photos_stmt->close();

// Variáveis dinâmicas
$title = htmlspecialchars($car['titulo']);
$year = $car['modelo_ano'];
$hp = $car['potencia_hp'];
$km = number_format($car['quilometragem'], 0, ',', '.') . ' km';
$transmissao = htmlspecialchars($car['transmissao']);
$descricao_simples = htmlspecialchars($car['descricao']); 
$cilindrada_cc = $car['cilindrada_cc'] . ' cc';
$combustivel = htmlspecialchars($car['tipo_combustivel']);
$logo_path = 'logo.png'; 

// NOVO: Formatação do Preço
$price = $car['preco'];
$price_formatted = '€ ' . number_format($price, 0, ',', '.');


// =========================================================
// LÓGICA DE LIMPEZA DE EXTRAS (INTEGRADA)
// =========================================================
$raw_data = $car['raw_extras'] ?? '';

// Regex que apanha \r\n, \n, \\r\\n, \\\\r\\\\n (o lixo da base de dados)
$pattern = '/(\\\+r\\\+n|\\\+n|\\\+r|\r\n|\n|\r)/'; 
$cleaned_extras_raw = preg_split($pattern, $raw_data, -1, PREG_SPLIT_NO_EMPTY);

$final_extras_list = [];
if (is_array($cleaned_extras_raw)) {
    foreach ($cleaned_extras_raw as $item) {
        // Remove aspas, barras e espaços
        $clean_item = trim($item, " \t\n\r\0\x0B\"'\\");
        // Remove emojis e marcadores
        $clean_item = str_replace(['▪️', '•', '💰', '€', '📱', '- ', '_'], '', $clean_item);
        
        // Só adiciona se tiver texto real
        if (strlen($clean_item) > 1) {
            $final_extras_list[] = trim($clean_item);
        }
    }
}
// =========================================================
?>
<!DOCTYPE html>
<html lang="pt-PT" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>WFCARS | <?php echo $title; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:wght@200;300;400;600;700;800;900&family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">

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
            }
          }
        }
    </script>
    
    <style>
        /* VARIÁVEIS DE COR */
        :root{
            --color-dark-primary: #070708;
            --color-dark-card: #0f1114;
            --color-highlight: #C8C8CA; /* prata brilhante */
            --color-subtle: #9aa0a6;
            --color-light-accent: #141618;
        }

        html,body{height:100%;}
        body{font-family:'Poppins',sans-serif;background:var(--color-dark-primary);color:#EFEFEF;-webkit-font-smoothing:antialiased;}

        /* UTILITY */
        .chrome-text{background:linear-gradient(90deg,#fff,#c8c8c8,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 0 8px rgba(200,200,200,0.12)}
        .btn-silver{background:linear-gradient(180deg,var(--color-highlight),#f5f5f5);color:#0b0b0b;font-weight:800}
        .silver-text { background: linear-gradient(135deg,#d0d0d0,#ffffff,#bfbfbf); -webkit-background-clip: text; color: transparent; }
        
        /* NOVO: Estilo da Etiqueta de Preço Grande */
        .price-tag-highlight-large {
            position: absolute;
            top: 20px; /* Mais afastado do topo */
            right: 20px; /* Mais afastado da lateral */
            background: linear-gradient(180deg, var(--color-highlight), #f5f5f5);
            color: #0b0b0b; 
            font-weight: 900; /* Mais negrito */
            font-size: 1.8rem; /* Tornar maior */
            padding: 0.75rem 1.5rem; /* Mais enchimento */
            border-radius: 9999px; /* Pill shape */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
            z-index: 10; 
            text-shadow: none; 
            letter-spacing: 0.05em;
            transform: scale(1.05); /* Ligeiro destaque */
            transition: transform 0.3s;
        }
        .price-tag-highlight-large:hover {
             transform: scale(1.1);
        }


        /* HEADER PC (ESTILO ORBITRON - IGUAL AO INDEX) */
        .h-16 { height: 4rem !important; } 
        .header-logo-img { height: 4rem !important; }
        
        .header-nav-link {
            font-family: 'Orbitron', sans-serif;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-size: 0.9rem;
            text-shadow: 0 0 10px rgba(200,200,200,0.1);
            transition: color 0.3s, transform 0.3s;
            color: var(--color-subtle) !important;
        }
        
        .header-nav-link:hover {
            color: var(--color-highlight) !important;
            transform: translateY(-2px);
        }

        header .btn-silver {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        /* MENU MOBILE */
        .mobile-menu-overlay {
            position: fixed; inset: 0; z-index: 50; background: #000000;
            display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
            padding-left: 2.5rem; opacity: 0; visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .mobile-menu-overlay.active { opacity: 1; visibility: visible; }
        .mobile-menu-overlay nav { display: flex; flex-direction: column; gap: 1.5rem; width: 100%; }
        .mobile-menu-overlay nav a {
            font-family: 'Manrope', sans-serif; font-size: 1.2rem; font-weight: 400; 
            letter-spacing: 0.2em; text-transform: uppercase; color: rgba(255, 255, 255, 0.6); 
            transition: all 0.4s ease; opacity: 0; transform: translateY(20px);
        }
        .mobile-menu-overlay.active nav a { opacity: 1; transform: translateY(0); }
        .mobile-menu-overlay.active nav a:nth-child(1) { transition-delay: 0.1s; }
        .mobile-menu-overlay.active nav a:nth-child(2) { transition-delay: 0.15s; }
        .mobile-menu-overlay.active nav a:nth-child(3) { transition-delay: 0.2s; }
        .mobile-menu-overlay.active nav a:nth-child(4) { transition-delay: 0.25s; }
        .mobile-menu-overlay nav a:hover { color: #fff; padding-left: 10px; text-shadow: 0 0 15px rgba(255,255,255,0.4); }

        /* Botão Fechar */
        .rr-close-btn {
            position: absolute; top: 25px; right: 25px; left: auto; display: flex; align-items: center;
            gap: 10px; color: white; font-family: 'Manrope', sans-serif; font-size: 0.75rem;
            letter-spacing: 0.15em; text-transform: uppercase; background: none; border: none;
            cursor: pointer; z-index: 60; flex-direction: row-reverse;
        }
        .rr-close-icon {
            width: 32px; height: 32px; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; transition: border-color 0.3s;
        }
        .rr-close-btn:hover .rr-close-icon { border-color: white; }
        
        /* CARD & SWIPER */
        .card { background:#111; border:1px solid #ffffff22; box-shadow:0 0 25px #ffffff10; border-radius:1rem; }
        
        .gallerySlider .swiper-button-next,
        .gallerySlider .swiper-button-prev {
            color: var(--color-highlight) !important; background-color: var(--color-dark-card) !important;
            width: 48px; height: 48px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s; opacity: 0.8; box-shadow: 0 0 10px rgba(0, 0, 0, 0.8);
            position: absolute !important; top: 50% !important;
        }
        .galleryThumbs .swiper-slide {
            transition: all 0.3s; opacity: 0.5; border-radius: 8px; border: 2px solid transparent;
            overflow: hidden; height: 90px; box-shadow: 0 0 10px rgba(0,0,0,0.3); cursor: pointer;
        }
        .galleryThumbs .swiper-slide-thumb-active { opacity: 1; border-color: #C8C8CA; box-shadow: 0 0 15px rgba(255, 255, 255, 0.3); }
        
        footer a{color:var(--color-subtle)}
        footer a:hover{color:var(--color-highlight)}

        /* MOBILE */
        @media(max-width: 767px) {
            .py-12 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
            .py-24 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
            .px-10 { padding-left: 1rem !important; padding-right: 1rem !important; }
            .text-5xl { font-size: 2.25rem !important; }
            .text-3xl { font-size: 1.5rem !important; }
            .text-lg { font-size: 0.9rem !important; }
            .h-16 { height: 3.5rem !important; }
            .header-logo-img { height: 3.5rem !important; }
            #galeria .text-center { margin-bottom: 1rem; margin-top: 0; }
            #galeria .swiper { margin-bottom: 0.5rem; }
            .gallerySlider .swiper-slide img { min-height: 300px !important; }
            .gallerySlider .swiper-button-next, .gallerySlider .swiper-button-prev { width: 36px; height: 36px; font-size: 0.8rem; }
            .galleryThumbs .swiper-slide { height: 70px; }
            #detalhes .grid-cols-1 { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem; }
            #detalhes .mb-12 { margin-bottom: 1rem; }
            .card.p-8 { padding: 0.75rem !important; border-radius: 0.5rem; }
            .card p.text-sm { font-size: 0.65rem; }
            .card h3.text-2xl { font-size: 1rem; margin-top: 0.25rem; }
            #extras .mb-12 { margin-bottom: 1rem; }
            #extras .card h3.text-xl { font-size: 1rem; margin-bottom: 0.5rem; }
            #extras .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); font-size: 0.75rem; gap-y: 0.5rem; }
            .fa-check-circle { font-size: 0.6rem !important; }
            #contacto .mb-10 { margin-bottom: 1rem; }
            #contacto a.px-10 { padding: 0.75rem 1rem !important; font-size: 0.9rem; }

            /* Ajuste para o preço grande no móvel */
            .price-tag-highlight-large {
                top: 12px; 
                right: 12px; 
                font-size: 1.2rem; 
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body class="antialiased bg-dark-primary text-white">

<header class="fixed w-full z-40 top-0 left-0 bg-dark-primary/80 backdrop-blur-md border-b border-highlight/20">
    <nav class="max-w-7xl mx-auto flex items-center justify-between px-6 lg:px-12 py-4">
      <a href="index.php" class="flex items-center gap-4">
        <img src="<?php echo $logo_path; ?>" alt="WF Cars" class="h-16 header-logo-img" />
        </a>

      <div class="hidden lg:flex items-center gap-8">
        <a href="index.php#about-faq" class="header-nav-link text-subtle hover:text-highlight transition">SOBRE NÓS</a>
        <a href="inventory.php" class="header-nav-link text-subtle hover:text-highlight transition">CATÁLOGO</a> 
        <a href="index.php#contact" class="ml-4 btn-silver px-5 py-2 rounded-md shadow transition-transform hover:scale-105">FALE CONNOSCO</a>
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
        <a href="index.php#about-faq" onclick="closeMobileMenu()">SOBRE NÓS</a>
        <a href="inventory.php" onclick="closeMobileMenu()">VIATURAS EM STOCK</a>
        <a href="index.php#contact" onclick="closeMobileMenu()">CONTACTO</a>
        <a href="https://wa.me/351910291038" target="_blank" onclick="closeMobileMenu()">WHATSAPP</a>
    </nav>
    
    <div class="absolute bottom-10 left-10 text-subtle text-xs tracking-widest font-sans opacity-50">WF CARS © 2025</div>
</div>

<main class="pt-28 lg:pt-20">

<section id="galeria" class="px-10 bg-dark-primary max-w-7xl mx-auto">
    <div class="text-center mb-10 mt-6">
        <h1 class="text-5xl font-extrabold tracking-tight silver-text"><?php echo $title; ?></h1>
        <p class="text-gray-400 text-lg mt-2 max-w-xl mx-auto whitespace-pre-line"><?php echo nl2br($descricao_simples); ?></p>
    </div>

    <div class="swiper gallerySlider mb-4 relative rounded-xl overflow-hidden shadow-[0_0_45px_#ffffff30] border border-[#ffffff22]">
        
        <span class="price-tag-highlight-large">
            <?php echo $price_formatted; ?>
        </span>
        <div class="swiper-wrapper">
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $photo): 
                    $image_path = '../' . htmlspecialchars($photo['caminho_foto']);
                ?>
                <div class="swiper-slide"><img src="<?php echo $image_path; ?>" class="w-full h-full object-cover min-h-[450px]" alt="<?php echo $title; ?>" /></div>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="swiper-slide"><img src="heroimage.jpeg" class="w-full h-full object-cover min-h-[450px]" alt="Imagem não disponível" /></div>
            <?php endif; ?>
        </div>
        <div class="swiper-button-next swiper-button-white right-4"></div>
        <div class="swiper-button-prev swiper-button-white left-4"></div>
    </div>

    <div class="swiper galleryThumbs w-full h-24 mt-4">
        <div class="swiper-wrapper">
            <?php if (!empty($photos)): foreach ($photos as $photo): 
                    $image_path = '../' . htmlspecialchars($photo['caminho_foto']);
                ?>
                <div class="swiper-slide"><img src="<?php echo $image_path; ?>" class="w-full h-full object-cover" alt="Thumbnail" /></div>
                <?php endforeach; else: ?>
                <div class="swiper-slide"><img src="heroimage.jpeg" class="w-full h-full object-cover" alt="Thumbnail Default" /></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="detalhes" class="py-24 px-10 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-12 silver-text">Ficha Técnica Detalhada</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
        <div class="card p-8"><p class="text-sm text-gray-400">Preço (Referência)</p><h3 class="text-2xl font-semibold mt-2"><?php echo $price_formatted; ?></h3></div>
        <div class="card p-8"><p class="text-sm text-gray-400">Potência (CV)</p><h3 class="text-2xl font-semibold mt-2"><?php echo $hp; ?> cv</h3></div>
        <div class="card p-8"><p class="text-sm text-gray-400">Transmissão</p><h3 class="text-2xl font-semibold mt-2"><?php echo $transmissao; ?></h3></div>
        <div class="card p-8"><p class="text-sm text-gray-400">Combustível</p><h3 class="text-2xl font-semibold mt-2"><?php echo $combustivel; ?></h3></div>
        <div class="card p-8"><p class="text-sm text-gray-400">Quilometragem</p><h3 class="text-2xl font-semibold mt-2"><?php echo $km; ?></h3></div>
        <div class="card p-8"><p class="text-sm text-gray-400">Cilindrada</p><h3 class="text-2xl font-semibold mt-2"><?php echo $cilindrada_cc; ?></h3></div>
        <div class="card p-8"><p class="text-sm text-gray-400">Ano</p><h3 class="text-2xl font-semibold mt-2"><?php echo $year; ?></h3></div>
    </div>
</section>

<section id="extras" class="py-24 px-10 bg-[#0d0d0d] max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-12 silver-text">Extras & Equipamentos</h2>

    <div class="card p-8 mx-auto max-w-4xl">
        <h3 class="text-xl font-semibold mb-3 silver-text">Lista Completa de Extras</h3>
        
        <?php if (!empty($final_extras_list)): ?>
            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-y-2 gap-x-6 text-gray-300 text-sm">
                <?php foreach ($final_extras_list as $extra): ?>
                    <p class="flex items-start">
                        <i class="fas fa-check-circle text-gray-500 text-xs mt-1 me-2 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($extra); ?></span>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
             <p class="text-subtle text-sm">Não há extras listados para este veículo.</p>
        <?php endif; ?>
    </div>
</section>

<section id="contacto" class="py-24 px-10 bg-[#0d0d0d] text-center">
    <h2 class="text-3xl font-bold silver-text mb-6">Interessado neste veículo?</h2>
    <p class="text-gray-400 max-w-xl mx-auto mb-10">Entre em contacto connosco para agendar visita, test-drive ou solicitar mais informações.</p>

    <a href="https://wa.me/351910291038?text=Ol%C3%A1%2C%20estou%20interessado%20no%20an%C3%BAncio%20do%20<?php echo urlencode($title); ?>%20(ID%3A%20<?php echo $car_id; ?>)." 
       target="_blank"
       class="px-10 py-4 text-lg font-semibold rounded-xl bg-gradient-to-r from-[#1EBE4B] to-[#25D366] text-black shadow-[0_0_25px_#1EBE4B40] hover:shadow-[0_0_40px_#1EBE4B60] duration-300">
        <i class="fab fa-whatsapp me-2"></i> Contactar via WhatsApp
    </a>
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

<audio id="bg-music" loop>
    <source src="jazz_ambience.mp3" type="audio/mpeg">
    O seu navegador não suporta áudio.
</audio>

<div id="audio-control-container" class="fixed bottom-6 left-6 z-50 flex items-center gap-3 transition-all duration-1000 transform translate-y-20 opacity-0">
    
    <button id="music-toggle-btn" class="group relative w-[50px] h-[50px] rounded-full bg-dark-card/80 border border-highlight/30 text-highlight shadow-[0_0_20px_rgba(0,0,0,0.5)] backdrop-blur-md flex items-center justify-center hover:bg-highlight hover:text-dark-primary transition-all duration-500 overflow-hidden">
        
        <div id="music-waves" class="absolute inset-0 flex items-center justify-center gap-1 opacity-0 transition-opacity duration-300">
            <div class="w-1 h-3 bg-current rounded-full animate-wave"></div>
            <div class="w-1 h-5 bg-current rounded-full animate-wave animation-delay-200"></div>
            <div class="w-1 h-3 bg-current rounded-full animate-wave animation-delay-400"></div>
        </div>

        <i id="music-icon" class="fas fa-play ml-1 text-lg transition-transform duration-300 group-hover:scale-110"></i>
    </button>
</div>

<style>
    /* Audio Animations */
    @keyframes wave { 
        0%, 100% { height: 8px; opacity: 0.5; } 
        50% { height: 20px; opacity: 1; } 
    }
    .animate-wave { animation: wave 1.2s ease-in-out infinite; }
    .animation-delay-200 { animation-delay: 0.2s; }
    .animation-delay-400 { animation-delay: 0.4s; }

    /* MOBILE ADJUSTMENT FOR NEW PRICE SECTION */
    @media(max-width: 767px) {
        #galeria .text-center { margin-top: 0; }
    }
</style>

<script>
    // Função auxiliar para fechar o menu mobile
    function closeMobileMenu() {
        document.getElementById('mobile-menu-overlay').classList.remove('active');
        setTimeout(() => document.getElementById('mobile-menu-overlay').classList.add('hidden'), 400);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica do Menu Hamburger
        const openMenu = document.getElementById('open-menu');
        const closeMenu = document.getElementById('close-menu');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');

        if (openMenu && mobileMenuOverlay) {
            openMenu.addEventListener('click', () => { 
                mobileMenuOverlay.classList.remove('hidden'); 
                // Force reflow
                void mobileMenuOverlay.offsetWidth;
                mobileMenuOverlay.classList.add('active');
            });
        }
        
        if (closeMenu && mobileMenuOverlay) {
            closeMenu.addEventListener('click', closeMobileMenu);
        }
        
        // 1. Inicializa o Swiper de Thumbnails
        var galleryThumbs = new Swiper(".galleryThumbs", {
            spaceBetween: 10,
            slidesPerView: 5,
            freeMode: true,
            watchSlidesProgress: true,
            breakpoints: {
                768: { slidesPerView: 6 }
            },
            navigation: {
                nextEl: null,
                prevEl: null,
            }
        });

        // 2. Inicializa o Swiper principal
        var gallerySlider = new Swiper(".gallerySlider", {
            spaceBetween: 10,
            loop: true,
            navigation: {
                nextEl: ".gallerySlider .swiper-button-next",
                prevEl: ".gallerySlider .swiper-button-prev",
            },
            thumbs: {
                swiper: galleryThumbs,
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
        });

        // --- PERSISTENT AUDIO LOGIC (SHARED) ---
        const audio = document.getElementById('bg-music');
        const btn = document.getElementById('music-toggle-btn');
        const icon = document.getElementById('music-icon');
        const waves = document.getElementById('music-waves');
        const container = document.getElementById('audio-control-container');

        // 1. Ler o estado guardado no navegador
        const isMusicActive = localStorage.getItem('wfcars_music_active') === 'true';
        const storedTime = parseFloat(localStorage.getItem('wfcars_music_time')) || 0;

        audio.volume = 0; 
        let isPlaying = false;
        let fadeInterval;

        // Mostrar botão suavemente
        setTimeout(() => { if(container) container.classList.remove('translate-y-20', 'opacity-0'); }, 1000);

        // Função Fade
        function fadeAudio(targetVolume, duration) {
            const step = 0.05;
            if (fadeInterval) clearInterval(fadeInterval);
            fadeInterval = setInterval(() => {
                if (audio.volume < targetVolume && targetVolume > 0) {
                    if (audio.volume + step >= targetVolume) { audio.volume = targetVolume; clearInterval(fadeInterval); } 
                    else { audio.volume += step; }
                } else if (audio.volume > targetVolume) {
                    if (audio.volume - step <= targetVolume) { audio.volume = targetVolume; clearInterval(fadeInterval); if (targetVolume === 0) audio.pause(); } 
                    else { audio.volume -= step; }
                }
            }, 50);
        }

        // Atualizar UI
        function updateUI(playing) {
            if (playing) {
                icon.classList.add('hidden');
                waves.classList.remove('opacity-0');
                btn.classList.add('bg-highlight', 'text-dark-primary');
                btn.classList.remove('text-highlight');
            } else {
                icon.classList.remove('hidden');
                waves.classList.add('opacity-0');
                btn.classList.remove('bg-highlight', 'text-dark-primary');
                btn.classList.add('text-highlight');
            }
        }

        // 2. Se a música estava ativa, retoma de onde parou
        if (isMusicActive) {
            audio.currentTime = storedTime;
            isPlaying = true;
            updateUI(true);
            audio.play().then(() => { fadeAudio(0.4, 1000); }).catch(e => {
                console.log("Autoplay blocked (browser policy):", e);
                isPlaying = false; updateUI(false);
                localStorage.setItem('wfcars_music_active', 'false');
            });
        }

        // 3. Salvar estado antes de sair da página
        window.addEventListener('beforeunload', () => {
            if(isPlaying) {
                localStorage.setItem('wfcars_music_active', 'true');
                localStorage.setItem('wfcars_music_time', audio.currentTime);
            } else {
                localStorage.setItem('wfcars_music_active', 'false');
            }
        });

        // Salvar periodicamente (segurança extra)
        setInterval(() => { if(isPlaying) localStorage.setItem('wfcars_music_time', audio.currentTime); }, 3000);

        // 4. Click Handler
        if(btn) {
            btn.addEventListener('click', () => {
                if (isPlaying) {
                    isPlaying = false; updateUI(false); fadeAudio(0, 500);
                    localStorage.setItem('wfcars_music_active', 'false');
                } else {
                    isPlaying = true; updateUI(true); audio.play(); fadeAudio(0.4, 1000);
                    localStorage.setItem('wfcars_music_active', 'true');
                }
            });
        }
    });
</script>

</body>
</html>