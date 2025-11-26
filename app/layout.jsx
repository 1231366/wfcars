// app/layout.jsx

import '../globals.css'; // CORREÇÃO: Importa os estilos globais (incluindo Tailwind), ajustando o caminho para a compilação.

export default function RootLayout({ children }) {
  return (
    <html lang="pt-BR">
      <head>
        {/* Adiciona o viewport para garantir o design responsivo */}
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>WFCARS | Coleção de Luxo</title>
      </head>
      <body>
        {/* O 'children' é a nossa página principal (page.jsx) */}
        {children}
      </body>
    </html>
  );
}