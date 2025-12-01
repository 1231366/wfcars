<?php
/**
 * PÁGINA DE DETALHES DO CARRO - CARREGA DADOS DINAMICAMENTE
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
    FROM anuncios WHERE id = ?"); // Removido "AND status='Ativo'" para permitir ver vendidos se tiver o link
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

$logo_path = 'logo.png'; 
?>
<!DOCTYPE html>
<html lang="pt-PT" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>WFCARS | <?php echo $title; ?></title>

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
        
        /* ============================================== */
        /* ESTILOS UNIFICADOS (HEADER & MENU) */
        /* ============================================== */

        /* HEADER PC */
        .h-16 { height: 4rem !important; } 
        .header-logo-img { height: 4rem !important; }
        
        .header-nav-link {
            font-weight: 300; 
            letter-spacing: 0.05em; 
            text-shadow: 0 0 10px rgba(200, 200, 200, 0.1);
            transition: color 0.3s;
            font-size: 1.1rem; 
            font-family: 'Playfair Display', serif; 
            color: var(--color-subtle) !important;
        }
        .header-nav-link:hover {
            color: var(--color-highlight) !important;
        }

        /* MENU MOBILE ESTILO ROLLS ROYCE */
        .mobile-menu-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: #000000; /* Fundo Preto Puro */
            display: flex; 
            flex-direction: column;
            justify-content: center;
            align-items: flex-start; /* Alinhado à esquerda */
            padding-left: 2.5rem; 
            
            /* Transição */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu-overlay nav {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .mobile-menu-overlay nav a {
            font-family: 'Manrope', sans-serif; 
            font-size: 1.2rem;
            font-weight: 400; 
            letter-spacing: 0.2em; 
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6); 
            transition: all 0.4s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .mobile-menu-overlay.active nav a {
            opacity: 1;
            transform: translateY(0);
        }

        /* Delays para efeito cascata */
        .mobile-menu-overlay.active nav a:nth-child(1) { transition-delay: 0.1s; }
        .mobile-menu-overlay.active nav a:nth-child(2) { transition-delay: 0.15s; }
        .mobile-menu-overlay.active nav a:nth-child(3) { transition-delay: 0.2s; }
        .mobile-menu-overlay.active nav a:nth-child(4) { transition-delay: 0.25s; }

        .mobile-menu-overlay nav a:hover {
            color: #fff;
            padding-left: 10px; 
            text-shadow: 0 0 15px rgba(255,255,255,0.4);
        }

        /* Botão Fechar (Topo Direito) */
        .rr-close-btn {
            position: absolute;
            top: 25px;
            right: 25px; 
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
            flex-direction: row-reverse;
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
        
        /* ============================================== */
        /* ESTILOS DE DETALHES DO CARRO */
        /* ============================================== */

        .card { background:#111; border:1px solid #ffffff22; box-shadow:0 0 25px #ffffff10; border-radius:1rem; }
        
        /* Swiper Navigation */
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

        /* Thumbnails */
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
        
        /* FOOTER subtle */
        footer a{color:var(--color-subtle)}
        footer a:hover{color:var(--color-highlight)}

        /* ================================================= */
        /* == MOBILE OPTIMIZATION (Max 767px) == */
        /* ================================================= */
        @media(max-width: 767px) {
            .py-12 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
            .py-24 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
            .px-10 { padding-left: 1rem !important; padding-right: 1rem !important; }
            .text-5xl { font-size: 2.25rem !important; }
            .text-3xl { font-size: 1.5rem !important; }
            .text-lg { font-size: 0.9rem !important; }

            /* Header Mobile */
            .h-16 { height: 3.5rem !important; }
            .header-logo-img { height: 3.5rem !important; }
            
            /* Galeria */
            #galeria .text-center { margin-bottom: 1rem; margin-top: 0; }
            #galeria .swiper { margin-bottom: 0.5rem; }
            .gallerySlider .swiper-slide img { min-height: 300px !important; }
            
            .gallerySlider .swiper-button-next,
            .gallerySlider .swiper-button-prev {
                width: 36px;
                height: 36px;
                font-size: 0.8rem;
                border: 1px solid rgba(255, 255, 255, 0.4);
            }

            .galleryThumbs .swiper-slide {
                height: 70px;
            }
            
            /* Ficha Técnica */
            #detalhes .grid-cols-1 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.5rem;
            }
            #detalhes .mb-12 { margin-bottom: 1rem; }
            
            .card.p-8 {
                padding: 0.75rem !important;
                border-radius: 0.5rem;
            }
            .card p.text-sm {
                font-size: 0.65rem;
            }
            .card h3.text-2xl {
                font-size: 1rem;
                margin-top: 0.25rem;
            }
            
            /* Extras */
            #extras .mb-12 { margin-bottom: 1rem; }
            #extras .card h3.text-xl {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }
            #extras .grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                font-size: 0.75rem;
                gap-y: 0.5rem;
            }
            .fa-check-circle { 
                 font-size: 0.6rem !important;
            }

            /* Contacto */
            #contacto .mb-10 { margin-bottom: 1rem; }
            #contacto a.px-10 {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem;
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
        <a href="index.php#contact" class="ml-4 btn-silver px-5 py-2 rounded-md shadow">FALE CONNOSCO</a>
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
            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-y-2 gap-x-6 text-gray-300 text-sm">
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
    });
</script>

</body>
</html>