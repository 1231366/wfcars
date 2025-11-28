<?php
/**
 * PÁGINA DE INVENTÁRIO COMPLETO - INVENTORY.PHP
 * Carrega dinamicamente todos os anúncios ativos e arquivados (vendidos) da base de dados.
 */
include 'db_connect.php'; 

// --- 1. Aggregation for Filters (Mantido) ---

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

// c. Get fixed options (Fuel and Transmission)
$fuel_types = ['Diesel', 'Gasolina', 'Híbrido', 'Elétrico'];
$transmissions = ['Automática', 'Manual'];


// --- 2. Query para obter TODOS os anúncios ATIVOS (Mantido) ---
$sql_active = "
    SELECT 
        a.id, a.titulo, a.marca, a.modelo_ano, a.quilometragem, a.preco, a.potencia_hp, a.transmissao, a.destaque, a.tipo_combustivel,
        (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
    FROM 
        anuncios a
    WHERE 
        a.status = 'Ativo'
    ORDER BY 
        a.data_criacao DESC
";
$result_active = $conn->query($sql_active);
$active_listings = $result_active ? $result_active->fetch_all(MYSQLI_ASSOC) : [];

// 3. Query para obter anúncios VENDIDOS (Arquivos) (Mantido)
$sql_sold = "
    SELECT 
        a.id, a.titulo, a.marca, a.modelo_ano, a.quilometragem, a.preco, a.potencia_hp, a.transmissao, a.destaque, a.tipo_combustivel,
        (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
    FROM 
        anuncios a
    WHERE 
        a.status = 'Vendido'
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
    <title>WFCARS | Inventário Completo</title>
    
    <script src="https://www.google.com/recaptcha/api.js?render=YOUR_RECAPTCHA_SITE_KEY_HERE"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">
    
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

        /* ESTILOS DO HEADER (PC) - COPIADOS DA INDEX.PHP */
        .h-16 { height: 4rem !important; } 
        .header-logo-img { height: 4rem !important; }
        
        /* CORREÇÃO: Estilo do Menu PC igual ao Hamburger */
        .header-nav-link {
            font-weight: 800; /* Bolder */
            letter-spacing: 0.05em; /* Tracking wider */
            text-shadow: 0 0 10px rgba(200, 200, 200, 0.1);
            transition: color 0.3s;
            font-size: 1.2rem; /* Tamanho maior para destaque */
            color: var(--color-subtle) !important; 
        }
        /* No Inventory, o CATÁLOGO é o ativo */
        a[href="inventory.php"].header-nav-link {
            color: var(--color-highlight) !important;
        }
        .header-nav-link:hover {
            color: var(--color-highlight) !important;
        }

        /* CORREÇÃO 3: Estilo do Overlay do Menu Mobile - COPIADO DA INDEX.PHP */
        .mobile-menu-overlay {
            transition: transform 0.4s ease-in-out;
            transform: translateX(100%); /* Esconde por defeito */
            background: var(--color-dark-card); 
            border-left: 1px solid var(--color-highlight)/20;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            width: 100%;
            display: flex; /* Garante que está ativo no LG:hidden */
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-overlay.active {
            transform: translateX(0); /* Mostra o menu */
        }

        .mobile-menu-overlay nav a {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-shadow: 0 0 10px rgba(200, 200, 200, 0.1);
            transition: color 0.3s;
        }

        .mobile-menu-overlay nav a:hover {
            color: var(--color-highlight);
        }

        /* ESTILOS ESPECÍFICOS PARA CARROS VENDIDOS */
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
             background-color: #dc2626; /* Red 600 */
             padding: 0.75rem 1.5rem;
             color: white;
             font-weight: 800;
             font-size: 1.5rem;
             text-transform: uppercase;
             border-radius: 0.5rem;
             transform: rotate(-10deg);
             box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
        }
        
        /* NOVO ESTILO: Preço Prata Vibrante (Botão) */
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
        
        /* ESTILO PARA FOOTER DO CARD (PC) */
        .card-footer-pc {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem; /* mt-6 */
        }

        /* Testimonials Swiper Pagination/Arrows */
        .mySwiperTestimonials .swiper-pagination-bullet-active {
            background: var(--color-highlight);
        }
        
        /* OTIMIZAÇÃO MÓVEL INSANA (Max 639px) */
        @media(max-width: 639px) {
            /* == LAYOUT GERAL == */
            .grid {
                gap: 1rem !important; 
            }
            .py-20 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
            
            /* == HEADER == */
            .h-16 { height: 3.5rem !important; }
            .header-logo-img { height: 3.5rem !important; }
            
            /* == CARD (2 COLUNAS) == */
            .car-card .p-7 { 
                padding: 0.75rem !important; 
            } 
            /* CORREÇÃO: Altura da imagem ajustada para mobile (maior) */
            .car-card .h-64 { 
                height: 180px !important; 
                object-fit: cover;
                object-position: center 50%;
                background-color: var(--color-dark-primary); 
            } 
            
            /* == TIPOGRAFIA (REDUZIDA) == */
            .car-card .text-2xl { 
                font-size: 0.9rem !important; 
                font-weight: 800 !important;
                margin-top: 0.5rem !important;
                line-height: 1.2;
            } 
            .car-card .text-xl {
                font-size: 0.8rem !important; 
            }
            .car-card .text-sm { 
                font-size: 0.75rem !important; 
                margin-top: 0.2rem !important;
            }
            
            /* == POSICIONAMENTO SUPERIOR (Preço/Destaque) == */
            .price-tag-highlight-button {
                top: 8px;
                right: 8px;
                font-size: 0.7rem; 
                padding: 0.3rem 0.6rem;
            }

            /* NOVO: Ajuste do Destaque para não colidir com o preço (vai para a esquerda) */
            .car-card .featured-tag-mobile {
                position: absolute;
                top: 8px;
                left: 8px;
                padding: 0.3rem 0.6rem;
                font-size: 0.7rem;
                z-index: 10;
            }

            /* NOVO: Footer Mobile Otimizado (mobile-footer) */
            .car-card .card-footer-pc { /* No mobile, o footer PC usa o layout mobile-footer */
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                margin-top: 0.75rem !important;
                padding-top: 0.5rem;
                border-top: 1px solid #ffffff1a;
            }
            
            .car-card .details-button {
                padding: 0.5rem 0.8rem !important; 
                font-size: 0.7rem !important; 
            }

            /* AJUSTADO: Estilo para o ano em destaque no mobile */
            .year-highlight-desktop { /* Esconde no mobile */
                display: none;
            }
            .year-highlight-mobile { /* Mostra no mobile */
                display: block !important;
                font-size: 0.9rem !important; /* Reduzido para 0.9rem */
                font-weight: 700;
                color: var(--color-highlight);
            }
        }

        /* Desktop: Oculta o ano em destaque mobile e mostra o de desktop */
        @media (min-width: 640px) {
            .year-highlight-mobile {
                display: none !important;
            }
            .year-highlight-desktop {
                display: block !important;
                font-size: 1.25rem !important; /* text-xl */
                font-weight: 600 !important; /* font-semibold */
                color: var(--color-highlight);
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
        <a href="inventory.php" class="header-nav-link text-highlight transition">CATÁLOGO</a> 
        <a href="index.php#contact" class="ml-4 btn-silver px-5 py-2 rounded-md shadow">FALE CONNOSCO</a>
      </div>

      <button id="open-menu" class="lg:hidden text-2xl text-subtle"><i class="fa fa-bars"></i></button>
    </nav>
</header>

<div id="mobile-menu-overlay" class="mobile-menu-overlay fixed inset-0 z-50 hidden">
    <button id="close-menu" class="absolute top-6 right-6 text-highlight text-3xl z-20 hover:text-white transition">&times;</button>
    <nav class="flex flex-col items-center gap-10 text-center">
        <a href="index.php#about-faq" class="text-3xl font-bold text-white" onclick="closeMobileMenu();">SOBRE NÓS</a>
        <a href="inventory.php" class="text-3xl font-bold text-white" onclick="closeMobileMenu();">CATÁLOGO</a>
        <a href="index.php#contact" class="text-3xl font-bold text-white" onclick="closeMobileMenu();">CONTACTO</a>
    </nav>
    <div class="absolute bottom-10 text-subtle text-sm">WF CARS © 2025</div>
</div>

<section class="py-20 px-6 lg:px-12 text-center pt-32">
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
            ?>
            <div class="car-card overflow-hidden group relative">
                <a href="car-details.php?id=<?php echo $car['id']; ?>">
                    <div class="relative">
                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($car['titulo']); ?>" class="w-full h-64 object-cover car-image group-hover:scale-105 duration-500 opacity-90">
                        
                        <span class="price-tag-highlight-button">
                             <?php echo $price_formatted; ?>
                        </span>
                        
                        <?php if ($car['destaque'] == 1): ?>
                            <span class="featured-tag-mobile absolute top-4 left-4 px-3 py-1 text-xs font-bold rounded-full bg-yellow-400 text-black shadow-lg">
                                DESTAQUE
                            </span>
                        <?php endif; ?>
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
                $details_line_top = $car['modelo_ano'] . ' | ' . htmlspecialchars($car['transmissao']) . ' | ' . $car['potencia_hp'] . ' cv';
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
        <a href="mailto:warriorfcar@gmail.com" class="block text-white mb-1">warriorfcar@gmail.com</a>
        <a href="tel:+351910291038" class="block text-white">+351 910 291 038 (Chamada)</a>
      </div>

      <div>
        <h4 class="text-sm text-highlight uppercase font-bold mb-3">Redes Sociais</h4>
        <div class="flex gap-4 text-2xl text-subtle items-center">
            <a class="hover:text-white transition" href="https://www.instagram.com/wfcars.pt/" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
            <a class="hover:text-white transition" href="https://wa.me/351910291038" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <a class="hover:text-white transition" href="https://www.facebook.com/people/WFcars/61551061824401/?ref=_xav_ig_profile_page_web" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
        </div>
      </div>
      
    </div>

   <div class="max-w-7xl mx-auto text-center mt-12 text-subtle text-sm px-6 lg:px-12">
        <a class="inline-block text-white/80 hover:text-highlight transition text-xs border-b border-subtle/50 mb-3" href="https://www.livroreclamacoes.pt/" target="_blank">
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
    
    // FUNÇÃO SIMULADA: para lidar com a submissão do formulário (simulada)
    // NOTE: Esta função é redundante nesta página, mas mantida para consistência se fosse usada.
    function handleFormSubmission(event) {
        event.preventDefault();
        alert('Esta função simula a submissão. Para funcionar, precisa do formulário na página (index.php) e de uma chave reCAPTCHA válida.');
    }

    document.addEventListener('DOMContentLoaded', () => {

        // Inicialização do Swiper (Testemunhos)
        new Swiper('.mySwiperTestimonials', { 
            slidesPerView:1, 
            spaceBetween:20, 
            loop:true, 
            pagination:{el:'.swiper-pagination',clickable:true}, 
            autoplay:{
                delay: 4500, // Autoplay a cada 4.5 segundos
                disableOnInteraction: false, // Não para ao interagir
            },
            breakpoints:{
                768:{slidesPerView:2, spaceBetween: 30},
                1024:{slidesPerView:3, spaceBetween: 40}
            } 
        });

        // Lógica do Menu Hamburger
        const openMenu = document.getElementById('open-menu');
        const closeMenu = document.getElementById('close-menu');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');

        if (openMenu && mobileMenuOverlay) {
            openMenu.addEventListener('click', () => { 
                mobileMenuOverlay.classList.remove('hidden'); 
                setTimeout(() => mobileMenuOverlay.classList.add('active'), 10);
            });
        }
        
        if (closeMenu && mobileMenuOverlay) {
            closeMenu.addEventListener('click', closeMobileMenu);
        }

        // Lógica do Acordeão (FAQ) - Apenas para compatibilidade se o código for copiado
        window.toggleFaq = function(btn){ 
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
        };
    });
</script>

</body>
</html>