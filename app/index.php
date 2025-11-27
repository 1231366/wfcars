<?php
/**
 * HOMEPAGE - INDEX.PHP
 * Carrega dinamicamente a lista de anúncios ativos e destacados da base de dados.
 */
include 'db_connect.php'; 

// Query para obter anúncios ativos (ordenados por Destaque e data)
$sql_inventory = "
    SELECT 
        a.id, 
        a.titulo, 
        a.modelo_ano, 
        a.quilometragem,
        a.preco, 
        a.potencia_hp,
        a.transmissao,
        a.destaque,
        (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
    FROM 
        anuncios a
    WHERE 
        a.status = 'Ativo'
    ORDER BY 
        a.destaque DESC, a.data_criacao DESC
";

$result_inventory = $conn->query($sql_inventory);

// Inicialização segura para a listagem
$listings = $result_inventory ? $result_inventory->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-PT" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>WFCARS</title>

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
          keyframes: {
            'shine': {
              '0%': { "transform": 'translateX(-100%)' },
              '100%': { "transform": 'translateX(100%)' }
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

    /* Common premium utility classes */
    .chrome-text{background:linear-gradient(90deg,#fff,#c8c8c8,#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 0 8px rgba(200,200,200,0.12)}

    /* Subtle chrome sweep */
    .shine-sweep{position:relative;overflow:hidden}
    .shine-sweep::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.12),transparent);transform:translateX(-120%);animation:1.6s linear 0s infinite;animation-name:shine}

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
    
    /* MODIFICADO: Estilos para as Setas do Swiper (Prata Vibrante) */
    .mySwiper .swiper-button-next,
    .mySwiper .swiper-button-prev {
        color: var(--color-highlight) !important;
        background-color: var(--color-dark-card) !important;
        width: 48px; /* Aumentado ligeiramente para um look mais premium */
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
        /* Efeito de brilho prateado no hover */
        background: linear-gradient(180deg, var(--color-highlight), #fff);
        color: var(--color-dark-primary) !important;
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.4);
    }
    
    /* Testemunhos: Swiper custom bullets - silver */
    .mySwiperTestimonials .swiper-pagination-bullet{background:linear-gradient(145deg,#9b9b9b,#e8e8e8);opacity:0.6;width:10px;height:10px}
    .mySwiperTestimonials .swiper-pagination-bullet-active{background:linear-gradient(145deg,#fff,#d1d1d1);width:26px;border-radius:10px;box-shadow:0 0 12px rgba(255,255,255,0.14)}

    /* Testimonial card (dark + silver) */
    .testimonial-card{background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01));border:1px solid rgba(200,200,200,0.06);backdrop-filter:blur(6px);border-radius:14px;padding:2.25rem;box-shadow:0 12px 30px rgba(2,6,23,0.6);}
    .testimonial-card .quote-icon{font-size:6rem;color:rgba(200,200,200,0.08);position:absolute;left:-10px;top:-20px}

    /* Inventory tiles */
    .card-inner{background:linear-gradient(180deg,rgba(255,255,255,0.02),transparent);border-radius:12px;padding:1rem}
    .price-tag{position:absolute;right:0;top:0;background:linear-gradient(90deg,#f8f8f8,#cfcfcf);color:#050505;font-weight:800;padding:.6rem 1rem;border-bottom-left-radius:10px}

    /* Mobile menu overlay tweaks */
    .mobile-menu-overlay{background:linear-gradient(180deg,rgba(10,10,10,0.96),rgba(0,0,0,0.95));}

    /* Slight floating effect for decorative elements */
    .float-slow{animation:floatY 6s ease-in-out infinite}

    /* Improve form inputs' placeholder visibility */
    input::placeholder, textarea::placeholder{color:var(--color-subtle);opacity:0.9}

    /* Small accessibility focus outlines */
    a:focus, button:focus, input:focus, select:focus, textarea:focus{outline:2px solid rgba(200,200,200,0.12);outline-offset:2px}

    /* Reduce image transform jank */
    .img-cover{will-change:transform}
    
    /* NOVO: Estilo para o link de Detalhes Premium */
    .premium-details-link {
        color: var(--color-subtle);
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        text-shadow: 0 0 5px rgba(0,0,0,0.5);
    }
    .premium-details-link:hover {
        color: var(--color-highlight);
        transform: translateX(3px);
    }
  </style>
</head>
<body class="antialiased bg-dark-primary text-white">

  <div id="preloader" class="fixed inset-0 z-50 flex items-center justify-center bg-dark-primary">
    <img src="logo.png" alt="WF Cars" class="w-44 filter drop-shadow-lg" />
  </div>

  <header class="fixed w-full z-40 top-0 left-0 bg-dark-primary/80 backdrop-blur-md border-b border-highlight/20">
    <nav class="max-w-7xl mx-auto flex items-center justify-between px-6 lg:px-12 py-4">
      <a href="#" class="flex items-center gap-4">
        <img src="logo.png" alt="WF Cars" class="h-14 header-logo-img" />
        <span class="hidden lg:inline-block chrome-text font-extrabold tracking-wide">WF CARS</span>
      </a>

      <div class="hidden lg:flex items-center gap-8">
        <a href="#about-faq" class="uppercase text-sm tracking-wide text-subtle hover:text-highlight transition">SOBRE NÓS</a>
        <a href="#inventory" class="uppercase text-sm tracking-wide text-subtle hover:text-highlight transition">CATÁLOGO</a>
        <a href="#services" class="uppercase text-sm tracking-wide text-subtle hover:text-highlight transition">SERVIÇOS</a>
        <a href="#contact" class="ml-4 btn-silver px-5 py-2 rounded-md shadow">FALE CONNOSCO</a>
      </div>

      <button id="open-menu" class="lg:hidden text-2xl text-subtle"><i class="fa fa-bars"></i></button>
    </nav>
  </header>

  <div id="mobile-menu-overlay" class="mobile-menu-overlay fixed inset-0 z-50 hidden items-center justify-center">
    <button id="close-menu" class="absolute top-6 right-6 text-highlight text-3xl">&times;</button>
    <nav class="flex flex-col items-center gap-6 text-center">
      <a href="#about-faq" class="text-3xl font-bold text-white">SOBRE NÓS</a>
      <a href="#inventory" class="text-3xl font-bold text-white">CATÁLOGO</a>
      <a href="#contact" class="text-3xl font-bold text-white">CONTACTO</a>
    </nav>
  </div>

  <main class="pt-20">

    <section id="hero" class="relative hero-background" style="background-image:url('heroimage2.jpeg')">
      <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-transparent"></div>

      <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-20 flex flex-col lg:flex-row items-center lg:items-end justify-between py-20">
        <div class="lg:w-1/2 text-center lg:text-left">
          <p class="uppercase text-subtle tracking-widest mb-4">A Sua Próxima História.</p>
          <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight max-w-2xl">
            <span class="block chrome-text">Onde o Seu Sonho <br/> Encontra a Estrada</span>
          </h1>

          <div class="mt-8 flex flex-col sm:flex-row gap-4 items-center justify-center lg:justify-start">
            <a href="#inventory" class="btn-silver px-6 py-3 rounded-md text-base shadow-lg">VER COLEÇÃO</a>
            <a href="#contact" class="border border-highlight px-6 py-3 rounded-md text-sm tracking-wide hover:bg-white/5 transition">SOLICITAR CONSULTA</a>
          </div>
        </div>

        </div>
    </section>

    <section id="search-bar" class="px-6 lg:px-12 -mt-12 relative z-30">
      <div class="max-w-6xl mx-auto bg-dark-card/90 border border-highlight/20 rounded-xl p-4 shadow-xl">
        <form class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <select class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white">
            <option disabled selected>MARCA</option>
            <option>Mercedes-Benz</option>
            <option>Ferrari</option>
            <option>Rolls-Royce</option>
            <option>Porsche</option>
          </select>

          <select class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white">
            <option disabled selected>ANO MÍN.</option>
            <option>2024</option>
            <option>2023</option>
            <option>2020</option>
            <option>2015+</option>
          </select>

          <select class="search-input p-3 bg-transparent border border-highlight/20 rounded text-sm text-white">
            <option>&euro;100.000</option>
            <option>&euro;250.000</option>
            <option>&euro;500.000</option>
          </select>

          <select class="hidden md:block p-3 bg-transparent border border-highlight/20 rounded text-sm text-white">
            <option>&euro;250.000</option>
            <option>&euro;500.000</option>
            <option>&euro;1.000.000+</option>
          </select>

          <button class="col-span-2 md:col-span-1 btn-silver flex items-center justify-center gap-2">
            <i class="fa fa-search"></i>
            <span class="hidden lg:inline">PESQUISAR</span>
          </button>
        </form>
      </div>
    </section>

    <section id="testimonials" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
      <div class="max-w-7xl mx-auto text-center">
        <h2 class="text-3xl lg:text-4xl font-extrabold mb-4">O Que <span class="chrome-text">Dizem os Nossos Clientes</span></h2>
        <p class="text-subtle mb-10">A excelência no serviço é refletida nas experiências partilhadas.</p>

        <div class="swiper mySwiperTestimonials pb-8">
          <div class="swiper-wrapper">

            <div class="swiper-slide px-3">
              <div class="testimonial-card relative min-h-[220px]">
                <span class="quote-icon">“</span>
                <div class="relative z-10">
                  <div class="flex gap-1 mb-3 text-yellow-400">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  </div>
                  <p class="text-gray-200 italic">Experiência muito boa. Carro de excelente procedência, ótimo estado, atendimento top e seguro feito na hora. Recomendo!</p>
                </div>

                <footer class="mt-6">
                  <div class="h-0.5 w-20 bg-gradient-to-r from-gray-500 via-white to-gray-500 opacity-60 mb-3"></div>
                  <p class="text-highlight font-semibold">Marcos Antônio de Souza Monteiro</p>
                  <p class="text-subtle text-sm">Crítica de Google · há um ano</p>
                </footer>
              </div>
            </div>

            <div class="swiper-slide px-3">
              <div class="testimonial-card relative min-h-[220px]">
                <span class="quote-icon">“</span>
                <div class="relative z-10">
                  <div class="flex gap-1 mb-3 text-yellow-400">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  </div>
                  <p class="text-gray-200 italic">Experiência premium! Conforto, segurança e pontualidade ao mais alto nível.</p>
                </div>

                <footer class="mt-6">
                  <div class="h-0.5 w-20 bg-gradient-to-r from-gray-500 via-white to-gray-500 opacity-60 mb-3"></div>
                  <p class="text-highlight font-semibold">Renato Ribeiro</p>
                  <p class="text-subtle text-sm">Crítica de Google · há 2 anos</p>
                </footer>
              </div>
            </div>

            <div class="swiper-slide px-3">
              <div class="testimonial-card relative min-h-[220px]">
                <span class="quote-icon">“</span>
                <div class="relative z-10">
                  <div class="flex gap-1 mb-3 text-yellow-400">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  </div>
                  <p class="text-gray-200 italic">Profissionalismo total e excelência no atendimento. Recomendo 100%.</p>
                </div>

                <footer class="mt-6">
                  <div class="h-0.5 w-20 bg-gradient-to-r from-gray-500 via-white to-gray-500 opacity-60 mb-3"></div>
                  <p class="text-highlight font-semibold">Silva</p>
                  <p class="text-subtle text-sm">Crítica de Google · há um ano</p>
                </footer>
              </div>
            </div>

          </div>

          <div class="swiper-pagination mt-8"></div>
        </div>
      </div>
    </section>

    <section id="inventory" class="py-12 lg:py-24 px-6 lg:px-12 bg-dark-primary">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-3xl lg:text-4xl font-extrabold">Coleção <span class="chrome-text">Exclusiva</span></h2>
            <p class="text-subtle">Viaturas que são investimentos e declarações de intenção.</p>
          </div>
          <a href="#inventory" class="text-subtle hover:text-highlight">Ver tudo →</a>
        </div>

        <div class="swiper mySwiper">
          <div class="swiper-wrapper">
            
            <?php if (!empty($listings)): ?>
                <?php foreach ($listings as $car): 
                    $image_path = !empty($car['foto_principal']) ? '../' . htmlspecialchars($car['foto_principal']) : 'heroimage.jpeg';
                    $price_formatted = '€ ' . number_format($car['preco'], 0, ',', '.');
                    $km_formatted = number_format($car['quilometragem'], 0, ',', '.') . ' km';
                    $is_limited = $car['destaque'] == 1; 
                ?>
                <div class="swiper-slide p-3">
                    <div class="relative chrome-frame overflow-hidden rounded-2xl">
                        <div class="card-inner">
                            <a href="car-details.php?id=<?php echo $car['id']; ?>">
                                <div class="relative overflow-hidden rounded-xl">
                                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($car['titulo']); ?>" class="w-full h-56 object-cover group-hover:scale-105 transition duration-700 img-cover" />
                                    <div class="price-tag"><?php echo $price_formatted; ?></div>
                                    <?php if ($is_limited): ?>
                                        <span class="absolute top-3 left-3 bg-yellow-400/95 text-black px-3 py-1 rounded text-xs uppercase font-bold">DESTAQUE</span>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <h3 class="mt-4 text-xl font-black"><?php echo htmlspecialchars($car['titulo']); ?></h3>
                            <p class="text-subtle text-sm"><?php echo $car['modelo_ano'] . ' | ' . $km_formatted; ?> | <?php echo htmlspecialchars($car['transmissao']); ?></p>

                            <div class="mt-4 flex items-center justify-between border-t border-highlight/10 pt-3">
                                <span class="text-highlight text-sm font-medium"><?php echo $car['potencia_hp']; ?> cv</span>
                                
                                <a href="car-details.php?id=<?php echo $car['id']; ?>" class="premium-details-link">
                                    VER DETALHES <i class="fa fa-arrow-right text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="swiper-slide p-3">
                    <p class="text-subtle p-8 w-full text-center">Nenhum veículo ativo encontrado no catálogo.</p>
                 </div>
            <?php endif; ?>
            
          </div>

          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
        </div>
      </div>
    </section>

    <section id="features" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
      <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl lg:text-4xl font-extrabold mb-6">A Nossa <span class="chrome-text">Filosofia</span></h2>
        <p class="text-subtle mb-12 max-w-3xl">Mais do que transações, cultivamos relações baseadas na confiança e excelência.</p>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <div class="lg:col-span-2 bg-dark-card p-8 rounded-xl border-t-4 border-highlight shadow-xl">
            <h3 class="text-4xl font-black text-highlight">10 Anos de Legado.</h3>
            <p class="mt-4 text-subtle leading-relaxed">Na WF CARS, a excelência é a nossa prioridade. Dedicamo-nos à meticulosa seleção e importação de veículos premium de alto desempenho, focando em proveniência, testes rigorosos de qualidade e discrição total em cada processo.</p>
          </div>

          <div class="p-6 bg-dark-card rounded-xl border border-highlight/10">
            <p class="text-highlight text-3xl font-black mb-3">01</p>
            <h4 class="text-xl font-semibold">Curadoria de Alto Padrão</h4>
            <p class="text-subtle mt-2">Seleção rigorosa e certificação de origem dos melhores veículos europeus, garantindo a qualidade antes de qualquer aquisição.</p>
          </div>

          <div class="p-6 bg-dark-card rounded-xl border border-highlight/10">
            <p class="text-highlight text-3xl font-black mb-3">02</p>
            <h4 class="text-xl font-semibold">Excelência no Pós-Venda</h4>
            <p class="text-subtle mt-2">Garantia total de 18 meses e manutenção certificada, assegurando a preservação do valor e o desempenho máximo do seu investimento a longo prazo.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="about-faq" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-card">
      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div>
          <h2 class="text-4xl lg:text-5xl font-extrabold mb-6">O Seu Próximo <span class="chrome-text">Investimento</span></h2>

          <div id="faq-accordion" class="space-y-4 mt-6">
            <div class="faq-item border-b border-highlight/10 pb-4">
              <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> <span class="font-semibold">Todos os carros têm garantias?</span> <span class="faq-icon text-highlight">+</span> </button>
              <div class="faq-content mt-3 text-subtle hidden">Sim, todos os carros têm garantia de 18 meses, sem limite de kms.</div>
            </div>

            <div class="faq-item border-b border-highlight/10 pb-4">
              <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> <span class="font-semibold">Fazem vendas 100% online?</span> <span class="faq-icon text-highlight">+</span> </button>
              <div class="faq-content mt-3 text-subtle hidden">Sim, por várias vezes já fizemos vendas 100% online, entregando em casa do cliente sem custos adicionais.</div>
            </div>

            <div class="faq-item border-b border-highlight/10 pb-4">
              <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> <span class="font-semibold">Qual a origem dos automóveis?</span> <span class="faq-icon text-highlight">+</span> </button>
              <div class="faq-content mt-3 text-subtle hidden">Temos fornecedores espalhados por alguns países na europa, todas as viaturas são testadas e passam por testes de verificação de qualidade antes de serem compradas.</div>
            </div>
            
            <div class="faq-item border-b border-highlight/10 pb-4">
              <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> <span class="font-semibold">Fazem financiamento?</span> <span class="faq-icon text-highlight">+</span> </button>
              <div class="faq-content mt-3 text-subtle hidden">Sim, damos um parecer (positivo ou negativo) em apenas 2 horas.</div>
            </div>
            
            <div class="faq-item border-b border-highlight/10 pb-4">
              <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> <span class="font-semibold">Vendem carros por encomenda?</span> <span class="faq-icon text-highlight">+</span> </button>
              <div class="faq-content mt-3 text-subtle hidden">Sim, tratamos de todo o processo até ter a chave na mão, entre em contacto conosco pelo formulário ou pelo wpp.</div>
            </div>
            
            <div class="faq-item border-b border-highlight/10 pb-4">
              <button class="w-full flex justify-between items-center text-left" onclick="toggleFaq(this)"> <span class="font-semibold">Quantos dias demora o processo de compra?</span> <span class="faq-icon text-highlight">+</span> </button>
              <div class="faq-content mt-3 text-subtle hidden">Se for pago a pronto, entregamos no próprio dia, a financiamento 2/3 dias e por encomenda em 7 dias.</div>
            </div>
          </div>
        </div>

        <div class="relative">
          <div class="rounded-xl overflow-hidden chrome-frame shadow-2xl">
            <img src="keys.jpg" alt="Chave de carro" class="w-full h-[420px] object-cover img-cover" />
          </div>

          <div class="absolute -bottom-10 right-1/2 lg:right-0 transform translate-x-1/2 lg:translate-x-0 flex gap-4">
            <div class="bg-dark-primary p-5 rounded-xl chrome-frame text-center shadow-lg">
              <p class="text-3xl font-black text-highlight">100+</p>
              <p class="text-subtle text-sm mt-1">Clientes Satisfeitos</p>
            </div>
            <div class="bg-dark-primary p-5 rounded-xl chrome-frame text-center shadow-lg">
              <p class="text-3xl font-black text-highlight">16</p>
              <p class="text-subtle text-sm mt-1">Países Servidos</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="services" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
      <div class="max-w-7xl mx-auto text-center">
        <h2 class="text-3xl lg:text-4xl font-extrabold mb-4">Os Nossos <span class="chrome-text">Pilares</span></h2>
        <p class="text-subtle mb-12 max-w-3xl mx-auto">Cada serviço é um compromisso com o seu padrão de excelência.</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="relative rounded-xl overflow-hidden">
            <img src="wfcars_service.png" alt="Concierge" class="w-full h-96 object-cover img-cover rounded-xl" />
            <div class="absolute inset-0 bg-black/50 p-8 flex flex-col justify-end rounded-xl">
              <h3 class="text-4xl font-black text-highlight">Concierge 360º</h3>
              <p class="text-white mt-2">O seu gestor dedicado que assegura a excelência em cada etapa, desde a consultoria inicial à entrega final e discreta, focando na sua total satisfação.</p>
            </div>
          </div>

          <div class="grid grid-rows-3 gap-6">
            <div class="p-6 bg-dark-card rounded-xl border border-highlight/10 flex items-center gap-4">
              <div class="text-highlight text-3xl"><i class="fa fa-handshake"></i></div>
              <div>
                <h4 class="text-xl font-black">Financiamento Privado e Rápido</h4>
                <p class="text-subtle">Estruturação confidencial e rápida de *leasing* ou crédito, com parecer em apenas 2 horas, respeitando a sua ambição e privacidade.</p>
              </div>
            </div>

            <div class="p-6 bg-dark-card rounded-xl border border-highlight/10 flex items-center gap-4">
              <div class="text-highlight text-3xl"><i class="fa fa-globe"></i></div>
              <div>
                <h4 class="text-xl font-black">Curadoria de Alto Padrão</h4>
                <p class="text-subtle">Seleção e certificação minuciosa da proveniência dos melhores veículos *premium* europeus. A qualidade é verificada antes de ser adquirida, garantindo o seu melhor investimento.</p>
              </div>
            </div>

            <div class="p-6 bg-dark-card rounded-xl border border-highlight/10 flex items-center gap-4">
              <div class="text-highlight text-3xl"><i class="fa fa-tools"></i></div>
              <div>
                <h4 class="text-xl font-black">Garantia & Desempenho Máximo</h4>
                <p class="text-subtle">Proteção do seu investimento com garantia de 18 meses (sem limite de km) e manutenção certificada, assegurando o valor e o desempenho máximo a longo prazo.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="contact" class="py-20 lg:py-28 px-6 lg:px-12 bg-dark-primary">
      <div class="max-w-4xl mx-auto contact-form-container bg-dark-card/70 border border-highlight/10 rounded-xl p-8 shadow-2xl">
        <h2 class="text-3xl lg:text-4xl font-extrabold mb-4">Comece a Sua <span class="chrome-text">Jornada</span></h2>
        <p class="text-subtle mb-6">O primeiro passo para o seu próximo investimento. A confidencialidade é a nossa prioridade.</p>

        <form onsubmit="event.preventDefault(); alert('Mensagem enviada. Obrigado!');" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-highlight">Nome Completo</label>
              <input class="w-full bg-transparent border border-highlight/10 rounded px-4 py-2" placeholder="O seu nome" required />
            </div>
            <div>
              <label class="text-sm text-highlight">Email</label>
              <input type="email" class="w-full bg-transparent border border-highlight/10 rounded px-4 py-2" placeholder="O seu melhor email" required />
            </div>
          </div>

          <div>
            <label class="text-sm text-highlight">Telefone</label>
            <input class="w-full bg-transparent border border-highlight/10 rounded px-4 py-2" placeholder="O seu número de contacto (opcional)" />
          </div>

          <div>
            <label class="text-sm text-highlight">Assunto de Interesse</label>
            <select class="w-full bg-transparent border border-highlight/10 rounded px-4 py-2" required>
              <option disabled selected>Selecione uma opção</option>
              <option>Aquisição</option>
              <option>Sourcing</option>
              <option>Financiamento</option>
              <option>Manutenção</option>
            </select>
          </div>

          <div>
            <label class="text-sm text-highlight">Detalhes do Pedido</label>
            <textarea class="w-full bg-transparent border border-highlight/10 rounded px-4 py-3" rows="5" placeholder="Descreva o veículo ou o serviço que procura em detalhe." required></textarea>
          </div>

          <button type="submit" class="btn-silver w-full py-3 rounded font-extrabold">SOLICITAR CONTACTO</button>
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
        <a href="mailto:warriorfcar@gmail.com" class="block text-white mb-1">warriorfcar@gmail.com</a>
        <a href="tel:+351910291038" class="block text-white">+351 910 291 038 (Chamada)</a>
      </div>

      <div>
        <h4 class="text-sm text-highlight uppercase font-bold mb-3">Redes Sociais</h4>
        <div class="flex gap-4 text-2xl text-subtle">
            <a href="https://www.instagram.com/wfcars.pt/" target="_blank" title="Instagram" class="hover:text-white transition"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/351910291038" target="_blank" title="WhatsApp" class="hover:text-white transition"><i class="fab fa-whatsapp"></i></a>
            <a href="https://www.facebook.com/people/WFcars/61551061824401/?ref=_xav_ig_profile_page_web" target="_blank" title="Facebook" class="hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
        </div>
      </div>
      
    </div>

    <div class="max-w-7xl mx-auto text-center mt-12 text-subtle text-sm">&copy; 2025 WF CARS. Todos os direitos reservados.</div>
  </footer>

  <script>
    // Preloader
    window.addEventListener('load', () => { const p = document.getElementById('preloader'); if(p) setTimeout(()=>p.remove(),600); });

    // Mobile menu
    document.getElementById('open-menu').addEventListener('click', () => { document.getElementById('mobile-menu-overlay').classList.remove('hidden'); });
    document.getElementById('close-menu').addEventListener('click', () => { document.getElementById('mobile-menu-overlay').classList.add('hidden'); });

    // FAQ toggle
    function toggleFaq(btn){ 
        const content = btn.nextElementSibling; 
        const icon = btn.querySelector('.faq-icon');
        
        // Esconde todos os outros e remove o ícone de rotação
        document.querySelectorAll('.faq-item .faq-content').forEach(n => n.classList.add('hidden'));
        document.querySelectorAll('.faq-item .faq-icon').forEach(n => n.textContent = '+');
        
        if (content.classList.contains('hidden')){ 
            content.classList.remove('hidden'); 
            icon.textContent = '–';
        } else { 
            content.classList.add('hidden'); 
            icon.textContent = '+';
        } 
    }

    // Swiper inits
    document.addEventListener('DOMContentLoaded', ()=>{
      new Swiper('.mySwiper', { slidesPerView:1, spaceBetween:20, loop:true, navigation:{nextEl:'.swiper-button-next',prevEl:'.swiper-button-prev'}, breakpoints:{768:{slidesPerView:2},1024:{slidesPerView:3}} });

      // MODIFICADO: Adicionado Autoplay ao Swiper de Testemunhos
      new Swiper('.mySwiperTestimonials', { 
          slidesPerView:1, 
          spaceBetween:20, 
          loop:true, 
          pagination:{el:'.swiper-pagination',clickable:true}, 
          // NOVO: Parâmetros de Autoplay
          autoplay:{
              delay: 4500, // 4.5 segundos entre slides
              disableOnInteraction: false, // Continua a reprodução mesmo após o usuário interagir
          },
          breakpoints:{768:{slidesPerView:2},1024:{slidesPerView:3}} 
      });
    });
  </script>
</body>
</html>