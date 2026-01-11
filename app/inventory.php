<?php
/**
 * PÁGINA DE INVENTÁRIO COMPLETO - INVENTORY.PHP
 * Carrega dinamicamente todos os anúncios ativos e arquivados (vendidos) da base de dados.
 */
include 'db_connect.php'; 

// --- 1. Aggregation for Filters (Mantido) ---

// a. Get unique Brands
$sql_brands = "SELECT DISTINCT marca FROM anuncios WHERE LOWER(status) = 'ativo' ORDER BY marca ASC";
$result_brands = $conn->query($sql_brands);
$brands = $result_brands ? $result_brands->fetch_all(MYSQLI_ASSOC) : [];

// b. Get Min/Max Price, Min/Max KM, Min/Max Year
$sql_stats = "SELECT 
    MIN(preco) as min_price, MAX(preco) as max_price,
    MIN(quilometragem) as min_km, MAX(quilometragem) as max_km,
    MIN(modelo_ano) as min_year, MAX(modelo_ano) as max_year
FROM anuncios WHERE LOWER(status) = 'ativo'";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// c. Get fixed options (Fuel and Transmission)
$fuel_types = ['Diesel', 'Gasolina', 'Híbrido Diesel', 'Híbrido Gasolina', 'Elétrico'];
$transmissions = ['Automática', 'Manual'];


// --- 2. Query para obter TODOS os anúncios ATIVOS, RESERVADOS e BREVEMENTE ---
$sql_active = "
    SELECT 
        a.id, a.titulo, a.marca, a.modelo_ano, a.quilometragem, a.preco, a.potencia_hp, a.transmissao, a.destaque, a.tipo_combustivel, a.status,
        (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
    FROM 
        anuncios a
    WHERE 
        a.status IN ('Ativo', 'Reservado', 'Brevemente')
    ORDER BY 
        FIELD(a.status, 'Ativo', 'Reservado', 'Brevemente'),
        a.destaque DESC, 
        a.data_criacao DESC
";
$result_active = $conn->query($sql_active);
$active_listings = $result_active ? $result_active->fetch_all(MYSQLI_ASSOC) : [];

// 3. Query para obter anúncios VENDIDOS (Arquivos)
$sql_sold = "
    SELECT 
        a.id, a.titulo, a.marca, a.modelo_ano, a.quilometragem, a.preco, a.potencia_hp, a.transmissao, a.destaque, a.tipo_combustivel,
        (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
    FROM 
        anuncios a
    WHERE 
        LOWER(a.status) = 'vendido' 
    ORDER BY 
        a.data_criacao DESC
";
$result_sold = $conn->query($sql_sold);
$sold_listings = $result_sold ? $result_sold->fetch_all(MYSQLI_ASSOC) : [];

// Variáveis para a header e funções
$logo_path = 'logo.png'; 

function format_km($value) {
    return number_format($value, 0, ',', '.') . ' km';
}
function format_price($value) {
    return '€ ' . number_format($value, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-PT" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>WFCARS | Inventário Completo</title>
    
    <script src="https://www.google.com/recaptcha/api.js?render=YOUR_RECAPTCHA_SITE_KEY_HERE"></script>

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
        /* Variáveis e Estilos de Base do Projeto (Cores) */
        :root{
            --color-dark-primary: #070708;
            --color-dark-card: #0f1114;
            --color-highlight: #C8C8CA; /* prata brilhante */
            --color-subtle: #9aa0a6;
            --color-light-accent: #141618;
        }
        body {
            font-family: "Poppins", sans-serif;
            background: #0a0a0a;
            color: white;
            -webkit-font-smoothing: antialiased;
        }
        
        /* --- ESTILOS GERAIS --- */
        .silver-text {
            background: linear-gradient(135deg, #d0d0d0, #ffffff, #bfbfbf);
            -webkit-background-clip: text;
            color: transparent;
        }
        .silver-line {
            height: 1px;
            background: linear-gradient(90deg, #7d7d7d, #ffffff, #7d7d7d);
            opacity: .35;
        }
        .car-card {
            background: var(--color-dark-card);
            border: 1px solid #ffffff22;
            box-shadow: 0 0 25px #ffffff10;
            border-radius: 1rem;
            transition: 0.35s;
        }
        .car-card:hover {
            box-shadow: 0 0 45px #ffffff25;
            transform: translateY(-4px);
        }
        .chrome-text{background:linear-gradient(90deg,#fff,#c8c8c8,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 0 8px rgba(200,200,200,0.12)}
        
        .btn-silver{background:linear-gradient(180deg,var(--color-highlight),#f5f5f5);color:#0b0b0b;font-weight:800}

        /* --- HEADER DESKTOP (Sincronizado com Index - ORBITRON) --- */
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
        
        /* Estado Ativo para Inventory */
        a[href="inventory.php"].header-nav-link {
            color: var(--color-highlight) !important;
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

        /* --- MENU MOBILE ESTILO ROLLS ROYCE (Sincronizado com Index) --- */
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

        /* --- ESTILOS ESPECÍFICOS DO INVENTÁRIO --- */
        
        /* Sold Cars */
        .sold-card .car-image {
            filter: grayscale(100%) brightness(50%);
            opacity: 0.7;
        }
        .sold-card .sold-tag-container {
             position: absolute;
             inset: 0;
             display: flex;
             align-items: center;
             justify-content: center;
             pointer-events: none;
             z-index: 10;
        }
        .sold-card .sold-tag {
             background-color: #dc2626; 
             padding: 0.75rem 1.5rem;
             color: white;
             font-weight: 800;
             font-size: 1.5rem;
             text-transform: uppercase;
             border-radius: 0.5rem;
             transform: rotate(-10deg);
             box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
        }
        
        /* Price Tag Button */
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
        
        /* Card Footer PC */
        .card-footer-pc {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem; 
            border-top: 1px solid var(--color-highlight)/10;
            padding-top: 0.75rem;
        }

        .year-highlight-desktop {
            font-size: 1.25rem; /* text-xl */
            font-weight: 600; 
            color: var(--color-highlight);
        }
        .year-highlight-mobile {
            display: none; 
        }
        
        /* Destaques e Status */
        .featured-tag-mobile { 
            position: absolute; 
            top: 12px; 
            left: 12px; 
            padding: 0.3rem 0.6rem; 
            font-size: 0.7rem; 
            z-index: 10; 
            font-weight: 700; 
            border-radius: 4px; 
            text-transform: uppercase; 
        }

        /* Audio Animations */
        @keyframes wave { 0%, 100% { height: 8px; opacity: 0.5; } 50% { height: 20px; opacity: 1; } }
        .animate-wave { animation: wave 1.2s ease-in-out infinite; }
        .animation-delay-200 { animation-delay: 0.2s; }
        .animation-delay-400 { animation-delay: 0.4s; }
        
        /* --- OTIMIZAÇÃO MÓVEL --- */
        @media(max-width: 639px) {
            .grid { gap: 1rem !important; }
            .py-20 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
            .h-16 { height: 3.5rem !important; }
            .header-logo-img { height: 3.5rem !important; }
            
            /* Card Adjustments */
            .car-card .p-7 { padding: 0.75rem !important; } 
            .car-card .h-64 { 
                height: 180px !important; 
                object-fit: cover;
                object-position: center 50%;
                background-color: var(--color-dark-primary); 
            } 
            
            .car-card .text-2xl { font-size: 0.9rem !important; font-weight: 800 !important; margin-top: 0.5rem !important; line-height: 1.2; } 
            .car-card .text-xl { font-size: 0.8rem !important; }
            .car-card .text-sm { font-size: 0.75rem !important; margin-top: 0.2rem !important;}
            
            .price-tag-highlight-button { top: 8px; right: 8px; font-size: 0.7rem; padding: 0.3rem 0.6rem; }
            .car-card .featured-tag-mobile { position: absolute; top: 8px; left: 8px; padding: 0.3rem 0.6rem; font-size: 0.7rem; z-index: 10; }

            /* Footer Mobile */
            .car-card .card-footer-pc {
                margin-top: 0.75rem !important;
                padding-top: 0.5rem;
            }
            
            .car-card .details-button { padding: 0.5rem 0.8rem !important; font-size: 0.7rem !important; }

            .year-highlight-desktop { display: none !important; }
            .year-highlight-mobile { display: block !important; font-size: 0.9rem !important; font-weight: 700; color: var(--color-highlight); }
        }
        
    </style>
</head>

<body class="antialiased bg-dark-primary text-white">

    <header class="fixed w-full z-40 top-0 left-0 bg-dark-primary/80 backdrop-blur-md border-b border-highlight/20">
        <nav class="max-w-7xl mx-auto flex items-center justify-between px-6 lg:px-12 py-4">
            <a href="index.php" class="flex items-center gap-4">
                <img src="logo.png" alt="WF Cars" class="h-16 header-logo-img" />
                </a>

            <div class="hidden lg:flex items-center gap-8">
                <a href="index.php#about-faq" class="header-nav-link text-subtle hover:text-highlight transition">SOBRE NÓS</a>
                <a href="inventory.php" class="header-nav-link text-highlight transition">VIATURAS EM STOCK</a> 
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
        </nav>
        
        <div class="absolute bottom-10 left-10 text-subtle text-xs tracking-widest font-sans opacity-50">WF CARS © 2025</div>
    </div>

    <section class="px-6 lg:px-12 text-center pt-24 lg:pt-40 pb-20">
        <h2 class="text-4xl lg:text-5xl font-extrabold silver-text tracking-tight">Inventário Completo</h2>
        <p class="text-gray-400 mt-4 max-w-2xl mx-auto text-lg">
            A nossa seleção premium de veículos cuidadosamente escolhidos.
        </p>
        <div class="silver-line w-40 mx-auto mt-6"></div>
    </section>

    <section id="active-inventory" class="px-6 lg:px-12 pb-12">
        <h3 class="text-2xl font-bold mb-8 text-highlight">Veículos Ativos</h3>
        <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-10">
            
            <?php if (!empty($active_listings)): ?>
                <?php foreach ($active_listings as $car): 
                    $details_line_top = $car['modelo_ano'] . ' | ' . htmlspecialchars($car['transmissao']) . ' | ' . $car['potencia_hp'] . ' cv';
                    $details_line_bottom = '<i class="fa fa-tachometer-alt text-subtle/80"></i> ' . format_km($car['quilometragem']) . ' | <i class="fa fa-gas-pump text-subtle/80"></i> ' . htmlspecialchars($car['tipo_combustivel']);
                    
                    $image_path = !empty($car['foto_principal']) ? '../' . htmlspecialchars($car['foto_principal']) : 'heroimage.jpeg';
                    $price_formatted = format_price($car['preco']);

                    // Badges de Status
                    $status_badge = '';
                    if ($car['status'] === 'Reservado') {
                        $status_badge = '<span class="featured-tag-mobile absolute top-4 left-4 px-3 py-1 text-xs font-bold rounded-full bg-orange-500 text-white shadow-lg z-20">RESERVADO</span>';
                    } elseif ($car['status'] === 'Brevemente') {
                        $status_badge = '<span class="featured-tag-mobile absolute top-4 left-4 px-3 py-1 text-xs font-bold rounded-full bg-blue-500 text-white shadow-lg z-20">BREVEMENTE</span>';
                    } elseif ($car['destaque'] == 1) {
                        $status_badge = '<span class="featured-tag-mobile absolute top-4 left-4 px-3 py-1 text-xs font-bold rounded-full bg-yellow-400 text-black shadow-lg z-20">DESTAQUE</span>';
                    }
                ?>
                <div class="car-card overflow-hidden group relative">
                    <a href="car-details.php?id=<?php echo $car['id']; ?>">
                        <div class="relative">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($car['titulo']); ?>" class="w-full h-64 object-cover car-image group-hover:scale-105 duration-500 opacity-90">
                            
                            <span class="price-tag-highlight-button">
                                <?php echo $price_formatted; ?>
                            </span>
                            
                            <?php echo $status_badge; ?>
                        </div>
                    </a>

                    <div class="p-7">
                        <h3 class="text-2xl font-bold chrome-text mb-1"><?php echo htmlspecialchars($car['marca']); ?></h3>
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
                            <a href="car-details.php?id=<?php echo $car['id']; ?>"
                            class="details-button px-5 py-2 rounded-lg btn-silver text-sm font-semibold shadow-lg hover:shadow-xl transition-all">
                                Detalhes
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-10">
                    <p class="text-gray-400 text-xl">Nenhum veículo ativo encontrado no catálogo.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="sold-inventory" class="px-6 lg:px-12 pt-12 pb-24">
        <h3 class="text-2xl font-bold mb-8 border-t border-white/10 pt-10 text-highlight">Arquivos (Vendidos)</h3>
        <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-10">
            
            <?php if (!empty($sold_listings)): ?>
                <?php foreach ($sold_listings as $car): 
                    $details_line_top = $car['modelo_ano'] . ' | ' . htmlspecialchars($car['transmissao']) . ' | . . ' . $car['potencia_hp'] . ' cv';
                    $details_line_bottom = '<i class="fa fa-tachometer-alt text-subtle/80"></i> ' . format_km($car['quilometragem']) . ' | <i class="fa fa-gas-pump text-subtle/80"></i> ' . htmlspecialchars($car['tipo_combustivel']);

                    $image_path = !empty($car['foto_principal']) ? '../' . htmlspecialchars($car['foto_principal']) : 'heroimage.jpeg';
                ?>
                <div class="car-card overflow-hidden group sold-card relative">
                    
                    <div class="relative">
                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($car['titulo']); ?>" class="w-full h-64 object-cover car-image duration-500">
                        <span class="price-tag-highlight-button bg-gray-600/50 text-white">
                            <?php echo format_price($car['preco']); ?>
                        </span>
                    </div>

                    <div class="p-7">
                        <h3 class="text-2xl font-bold silver-text mb-1"><?php echo htmlspecialchars($car['marca']); ?></h3>
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
                            <span class="text-sm px-5 py-2 text-gray-400 border border-gray-600 rounded-lg">
                                ARQUIVADO
                            </span>
                        </div>
                    </div>
                    <div class="sold-tag-container">
                        <span class="sold-tag">
                            VENDIDO
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-10">
                    <p class="text-gray-400 text-xl">Nenhum veículo vendido encontrado no arquivo.</p>
                </div>
            <?php endif; ?>

        </div>
    </section>

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

    <script>
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
            // Forçar reflow para ativar a transição
            void menu.offsetWidth;
            menu.classList.add('active');
        });
        
        document.getElementById('close-menu').addEventListener('click', closeMobileMenu);

        // --- PERSISTENT AUDIO LOGIC ---
        document.addEventListener('DOMContentLoaded', () => {
            const audio = document.getElementById('bg-music');
            const btn = document.getElementById('music-toggle-btn');
            const icon = document.getElementById('music-icon');
            const waves = document.getElementById('music-waves');
            const container = document.getElementById('audio-control-container');

            // Ler estado do LocalStorage
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

            // Auto-Resume
            if (isMusicActive) {
                audio.currentTime = storedTime;
                isPlaying = true;
                updateUI(true);
                audio.play().then(() => { fadeAudio(0.4, 1000); }).catch(e => {
                    console.log("Autoplay blocked:", e);
                    isPlaying = false; updateUI(false);
                    localStorage.setItem('wfcars_music_active', 'false');
                });
            }

            // Guardar estado
            window.addEventListener('beforeunload', () => {
                if(isPlaying) {
                    localStorage.setItem('wfcars_music_active', 'true');
                    localStorage.setItem('wfcars_music_time', audio.currentTime);
                } else {
                    localStorage.setItem('wfcars_music_active', 'false');
                }
            });
            
            // Backup periódico
            setInterval(() => { if(isPlaying) localStorage.setItem('wfcars_music_time', audio.currentTime); }, 3000);

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