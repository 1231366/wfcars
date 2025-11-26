/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        // Paleta Luxury Dark
        'dark-primary': '#0A0A0A', // Fundo principal: Preto Profundo
        'dark-card': '#1C1C1C',    // Fundo dos cartões: Cinza muito escuro
        'highlight': '#C8A253',    // Destaque: Ouro Escovado
        'subtle': '#737373',       // Texto/bordas secundárias: Cinza Sutil
      },
      fontFamily: {
        // Usamos Poppins para um visual clean e moderno
        sans: ['Poppins', 'sans-serif'],
      },
      // Adiciona uma animação de "bounce" para a seta do HERO
      keyframes: {
        bounce: {
          '0%, 100%': { transform: 'translateY(-25%)', animationTimingFunction: 'cubic-bezier(0.8, 0, 1, 1)' },
          '50%': { transform: 'none', animationTimingFunction: 'cubic-bezier(0, 0, 0.2, 1)' },
        }
      },
      animation: {
        'bounce': 'bounce 1s infinite',
      },
    },
  },
  plugins: [],
}