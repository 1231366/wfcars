<?php
/**
 * PÁGINA DE DETALHES DO CARRO - CARREGA DADOS DINAMICAMENTE
 * NOTA: Corrigido o problema de inicialização do Swiper e simplificado o bloco de Extras.
 */
include 'db_connect.php'; 

// 1. Obter o ID do carro da URL
$car_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$car_id) {
    header("Location: index.php");
    exit();
}

// 2. Buscar dados do carro (usando prepared statements para segurança)
$stmt = $conn->prepare("SELECT 
    id, titulo, descricao, modelo_ano, potencia_hp, quilometragem, transmissao,
    cilindrada_cc, tipo_combustivel, raw_extras 
    FROM anuncios WHERE id = ? AND status = 'Ativo'");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();
$stmt->close();

if (!$car) {
    header("Location: index.php");
    exit();
}

// 3. Buscar todas as fotos associadas
$photos_stmt = $conn->prepare("SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = ? ORDER BY is_principal DESC, id ASC");
$photos_stmt->bind_param("i", $car_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = $photos_result->fetch_all(MYSQLI_ASSOC);
$photos_stmt->close();


// Variáveis dinâmicas para a página
$title = htmlspecialchars($car['titulo']);
$year = $car['modelo_ano'];
$hp = $car['potencia_hp'];
$km = number_format($car['quilometragem'], 0, ',', '.') . ' km';
$transmissao = htmlspecialchars($car['transmissao']);
$descricao_simples = htmlspecialchars($car['descricao']); // Descrição completa
$cilindrada_cc = $car['cilindrada_cc'] . ' cc';
$combustivel = htmlspecialchars($car['tipo_combustivel']);
$raw_extras_list = explode("\n", $car['raw_extras'] ?? ''); // Divide a lista de extras em linhas

// Variáveis simuladas/mantidas para a estrutura (0-100 removido)
$aceleracao = "-"; 
$tracao = "Traseira"; 
?>
<!DOCTYPE html>
<html lang="pt-PT" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>WFCARS | <?php echo $title; ?> - Detalhes</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* VARIÁVEIS DE COR DA HOMEPAGE */
        :root{
            --color-dark-primary: #070708;
            --color-dark-card: #0f1114;
            --color-highlight: #C8C8CA; /* prata brilhante */
            --color-subtle: #9aa0a6;
            --color-light-accent: #141618;
        }

        /* ESTILOS DE TEMA */
        html,body{height:100%;}
        body{font-family:'Poppins',sans-serif;background:var(--color-dark-primary);color:#EFEFEF;-webkit-font-smoothing:antialiased;}

        /* UTILITY: Efeito Chrome Text da Homepage */
        .chrome-text{background:linear-gradient(90deg,#fff,#c8c8c8,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 0 8px rgba(200,200,200,0.12)}
        
        /* UTILITY: Botão Silver da Homepage */
        .btn-silver{background:linear-gradient(180deg,var(--color-highlight),#f5f5f5);color:#0b0b0b;font-weight:800}

        /* ESTILOS DE HEADER ESPECÍFICOS */
        header { background-color: var(--color-dark-primary)/80 !important; }
        .header-logo-img { height: 56px; } /* h-14 */
        
        /* ESTILOS DE CONTEÚDO DA PÁGINA (Mantidos) */
        .silver-text { background: linear-gradient(135deg,#d0d0d0,#ffffff,#bfbfbf); -webkit-background-clip: text; color: transparent; }
        .card { background:#111; border:1px solid #ffffff22; box-shadow:0 0 25px #ffffff10; border-radius:1rem; }
        
        /* Swiper Navigation (Inventário e Galeria) */
        .gallerySlider .swiper-button-next,
        .gallerySlider .swiper-button-prev {
            color: var(--color-highlight) !important;
            background-color: var(--color-dark-card) !important;
            width: 48px; 
            height: 48px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s;
            opacity: 0.8;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.8);
            position: absolute !important;
            top: 50% !important;
        }

        /* Thumbnails Styling */
        .galleryThumbs .swiper-slide {
            transition: all 0.3s;
            opacity: 0.5;
            border-radius: 8px;
            border: 2px solid transparent;
            overflow: hidden;
            height: 90px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            cursor: pointer;
        }
        .galleryThumbs .swiper-slide-thumb-active {
            opacity: 1;
            border-color: #C8C8CA;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="antialiased bg-dark-primary text-white">

<header class="fixed w-full z-40 top-0 left-0 bg-dark-primary/80 backdrop-blur-md border-b border-highlight/20">
    <nav class="max-w-7xl mx-auto flex items-center justify-between px-6 lg:px-12 py-4">
      <a href="index.php" class="flex items-center gap-4">
        <img src="logo.png" alt="WF Cars" class="h-14 header-logo-img" />
        <span class="hidden lg:inline-block chrome-text font-extrabold tracking-wide">WF CARS</span>
      </a>

      <div class="hidden lg:flex items-center gap-8">
        <a href="index.php#about-faq" class="uppercase text-sm tracking-wide text-subtle hover:text-highlight transition">SOBRE NÓS</a>
        <a href="index.php#inventory" class="uppercase text-sm tracking-wide text-subtle hover:text-highlight transition">CATÁLOGO</a>
        <a href="index.php#services" class="uppercase text-sm tracking-wide text-subtle hover:text-highlight transition">SERVIÇOS</a>
        <a href="index.php#contact" class="ml-4 btn-silver px-5 py-2 rounded-md shadow">FALE CONNOSCO</a>
      </div>

      <button id="open-menu" class="lg:hidden text-2xl text-subtle"><i class="fa fa-bars"></i></button>
    </nav>
</header>
<main class="pt-20">

<section id="galeria" class="py-12 px-10 bg-dark-primary max-w-7xl mx-auto">

    <div class="text-center mb-10 mt-6">
        <h1 class="text-5xl font-extrabold tracking-tight silver-text"><?php echo $title; ?></h1>
        <p class="text-gray-400 text-lg mt-2 max-w-xl mx-auto whitespace-pre-line"><?php echo nl2br($descricao_simples); ?></p>
    </div>

    <div class="swiper gallerySlider mb-4 relative rounded-xl overflow-hidden shadow-[0_0_45px_#ffffff30] border border-[#ffffff22]">
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
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $photo): 
                    $image_path = '../' . htmlspecialchars($photo['caminho_foto']);
                ?>
                <div class="swiper-slide"><img src="<?php echo $image_path; ?>" class="w-full h-full object-cover" alt="Thumbnail" /></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="swiper-slide"><img src="heroimage.jpeg" class="w-full h-full object-cover" alt="Thumbnail Default" /></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<section id="detalhes" class="py-24 px-10 max-w-7xl mx-auto">
    <h2 class="text-3xl font-bold mb-12 silver-text">Ficha Técnica Detalhada</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
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
        <?php 
        $cleaned_extras = array_map('trim', explode("\n", $car['raw_extras'] ?? ''));
        $cleaned_extras = array_filter($cleaned_extras); 
        ?>
        
        <?php if (!empty($cleaned_extras)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-2 gap-x-6 text-gray-300 text-sm">
                <?php foreach ($cleaned_extras as $extra): ?>
                    <?php $display_extra = trim(str_replace(['▪️', '•', '💰', '€', '📱'], '', $extra)); ?>
                    <p class="flex items-start">
                        <i class="fas fa-check-circle text-gray-500 text-xs mt-1 me-2 flex-shrink-0"></i>
                        <span><?php echo $display_extra; ?></span>
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

<footer class="py-10 text-center border-t border-white/10 mt-10">
    <p class="text-gray-500 text-sm">© 2025 WFCars — Todos os direitos reservados.</p>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Inicializa o Swiper de Thumbnails primeiro
        var galleryThumbs = new Swiper(".galleryThumbs", {
            spaceBetween: 10,
            slidesPerView: 4,
            freeMode: true,
            watchSlidesProgress: true,
            breakpoints: {
                1024: {
                    slidesPerView: 6,
                }
            },
            navigation: {
                nextEl: null,
                prevEl: null,
            }
        });

        // 2. Inicializa o Swiper principal, linkando aos Thumbs
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
    });
</script>

</body>
</html>