-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 27-Nov-2025 às 01:37
-- Versão do servidor: 10.4.28-MariaDB
-- versão do PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `wfcars_db`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `anuncios`
--

CREATE TABLE `anuncios` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo_ano` int(4) NOT NULL,
  `cilindrada_cc` int(11) DEFAULT NULL,
  `tipo_combustivel` enum('Gasolina','Diesel','Híbrido','Elétrico') NOT NULL DEFAULT 'Diesel',
  `descricao` text NOT NULL,
  `raw_extras` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `quilometragem` int(11) NOT NULL,
  `potencia_hp` int(4) NOT NULL,
  `transmissao` enum('Automática','Manual') NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Ativo','Vendido') NOT NULL DEFAULT 'Ativo',
  `destaque` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `anuncios`
--

INSERT INTO `anuncios` (`id`, `titulo`, `marca`, `modelo_ano`, `cilindrada_cc`, `tipo_combustivel`, `descricao`, `raw_extras`, `preco`, `quilometragem`, `potencia_hp`, `transmissao`, `data_criacao`, `status`, `destaque`) VALUES
(6, '𝗕𝗠𝗪 𝟱𝟮𝟬𝗗 𝗣𝗔𝗖𝗞 𝗠 𝗧𝗢𝗨𝗥𝗜𝗡𝗚', 'bmw', 2017, NULL, 'Diesel', '▪️ᴀᴜᴛᴏᴍᴀᴛɪᴄᴀ\r\n▪️180 ᴍɪʟ ᴋᴍ\r\n▪️2017\r\n▪️2.0 ᴄᴄ\r\n▪️190 ᴄᴠ\r\n▪️ᴅɪᴇsᴇʟ\r\n▪️ʟᴜᴢᴇs ʟᴇᴅ ᴅɪᴜʀɴᴀs\r\n▪️ʙᴀɴᴄᴏs ᴀǫᴜᴇᴄɪᴅᴏs\r\n▪️ᴠᴏʟᴀɴᴛᴇ ᴀǫᴜᴇᴄɪᴅᴏ\r\n▪️ᴀɪʀᴅʀᴏᴘ ᴅɪsᴘʟᴀʏ\r\n▪️ᴛᴇᴛᴏ ᴘᴀɴᴏʀᴀᴍɪᴄᴏ\r\n▪️ᴄᴏᴄᴋᴘɪᴛ ᴅɪɢɪᴛᴀʟ\r\n▪️ʟᴜᴢ ᴀᴍʙɪᴇɴᴛᴇ\r\n▪️sᴇɴsᴏʀᴇs ᴇsᴛᴀᴄɪᴏɴᴀᴍᴇɴᴛᴏ\r\n▪️ᴠᴏʟᴀɴᴛᴇ ᴅᴇsᴘᴏʀᴛɪᴠᴏ ᴍᴜʟᴛɪғᴜɴᴄᴏᴇs\r\n▪️ᴠᴏʟᴀɴᴛᴇ ᴄᴏᴍ ᴘᴀᴛɪʟʜᴀs\r\n▪️ᴍᴀʟᴀ ᴇʟᴇᴛʀɪᴄᴀ\r\n▪️sɪsᴛᴇᴍᴀ ᴀᴄᴛɪᴠᴏ ᴅᴇ ᴛʀᴀᴠᴀɢᴇᴍ\r\n▪️ᴀᴠɪsᴏ ᴅᴇ ғᴀɪxᴀ ᴅᴇ ʀᴏᴅᴀɢᴇᴍ\r\n▪️ᴀᴠɪsᴏ ᴀɴɢᴜʟᴏ ᴍᴏʀᴛᴏ\r\n▪️ᴄᴀʀʀᴇɢᴀᴅᴏʀ sᴍᴀʀᴛᴘʜᴏɴᴇ ᴡɪʀᴇʟᴇss\r\n▪️ʙʟᴜᴇᴛʜᴏᴏᴛʜ\r\n▪️ɢᴘs\r\n▪️ᴍᴏᴅᴏs ᴅᴇ ᴄᴏɴᴅᴜᴄᴀᴏ\r\n▪️ɢᴀʀᴀɴᴛɪᴀ\r\n💰22990€\r\n📱ᴡʜᴀᴛsᴀᴘᴘ +351910291038', NULL, 29990.00, 180000, 179, 'Automática', '2025-11-26 20:18:57', 'Ativo', 1),
(7, 'BMW', '1', 1, NULL, 'Diesel', '1', NULL, 1.00, 1, 1, 'Automática', '2025-11-26 20:21:59', 'Ativo', 0),
(8, '𝗠𝗜𝗡𝗜 𝗖𝗢𝗢𝗣𝗘𝗥 𝗢𝗡𝗘 𝗗', 'Mini', 158000, NULL, 'Diesel', '▪️2015\\\\r\\\\n▪️ᴅɪᴇsᴇʟ\\\\r\\\\n▪️ᴍᴀɴᴜᴀʟ\\\\r\\\\n▪️158 ᴍɪʟ ᴋᴍ\\\\r\\\\n▪️1.5 ᴄᴄ\\\\r\\\\n▪️95 ᴄᴠ\\\\r\\\\n▪️ᴀʀ ᴄᴏɴᴅɪᴄɪᴏɴᴀᴅᴏ\\\\r\\\\n▪️ʙʟᴜᴇᴛᴏᴏᴛʜ\\\\r\\\\n▪️ɢᴘs\\\\r\\\\n▪️ᴜsʙ\\\\r\\\\n▪️ᴄʜᴀᴠᴇ ɪɴᴛᴇʟɪɢᴇɴᴛᴇ\\\\r\\\\n▪️ʟᴜᴢᴇs ᴀᴜᴛᴏᴍᴀᴛɪᴄᴀs\\\\r\\\\n▪️ᴀʀʀᴀɴǫᴜᴇ ᴍᴏᴛᴏʀ sᴇᴍ ᴄʜᴀᴠᴇ\\\\r\\\\n▪️ᴠɪᴅʀᴏs ᴇʟᴇᴄᴛʀɪᴄᴏs\\\\r\\\\n▪️ᴊᴀɴᴛᴇs ᴇsᴘᴇᴄɪᴀɪs\\\\r\\\\n▪️ʀᴇɢᴜʟᴀᴄᴀᴏ ʀᴇᴛʀᴏᴠɪsᴏʀᴇs ᴇʟᴇᴄᴛʀɪᴄᴏ\\\\r\\\\n▪️ғᴇᴄʜᴏ ᴇ ᴀʙᴇʀᴛᴜʀᴀ ᴅᴇ ᴘᴏʀᴛᴀs ᴀᴜᴛᴏᴍᴀᴛɪᴄᴀs\\\\r\\\\n▪️sᴛᴀʀ / sᴛᴏᴘ\\\\r\\\\n▪️ɢᴀʀᴀɴᴛɪᴀ\\\\r\\\\n💰12499€\\\\r\\\\n📱ᴡʜᴀᴛsᴀᴘᴘ +351910291038', NULL, 12499.00, 158000, 95, 'Manual', '2025-11-26 20:28:03', 'Ativo', 1),
(9, '𝗠𝗘𝗥𝗖𝗘𝗗𝗘𝗦 𝗕𝗘𝗡𝗭 𝗖𝗟𝗔 𝟮𝟬𝟬𝗱', 'Mercedes', 2016, NULL, 'Diesel', 'ʜɪsᴛᴏʀɪᴄᴏ ᴅᴇ ʀᴇᴠɪsᴏᴇs ᴄᴏᴍᴘʟᴇᴛᴏ ᴍᴇʀᴄᴇᴅᴇs\\r\\n▪️2143cc\\r\\n▪️197 ᴍɪʟ ᴋᴍ\\r\\n▪️2016\\r\\n▪️136 cv\\r\\n▪️ᴀᴜᴛᴏᴍᴀᴛɪᴄᴏ\\r\\n▪️ᴅɪᴇsᴇʟ\\r\\n▪️ʟᴜᴢᴇs ʟᴇᴅ ᴅɪᴜʀɴᴀs\\r\\n▪️ʙɪ xᴇɴᴏɴ\\r\\n▪️ᴄʀᴜɪsᴇ ᴄᴏɴᴛʀᴏʟ\\r\\n▪️ᴠᴏʟᴀɴᴛᴇ ᴀᴍɢ ᴄᴏᴍ ᴘᴀᴛɪʟʜᴀs\\r\\n▪️ᴜsʙ\\r\\n▪️ʙʟᴜᴇᴛᴏᴏᴛʜ\\r\\n▪️ɢᴘs\\r\\n▪️ᴄᴅ\\r\\n▪️ᴀᴄ ᴀᴜᴛᴏᴍᴀᴛɪᴄᴏ\\r\\n▪️ғᴇᴄʜᴏ ᴘᴏʀᴛᴀs ᴇᴍ ᴀɴᴅᴀᴍᴇɴᴛᴏ\\r\\n▪️ᴀᴠɪsᴏ ᴀɴᴛɪ ᴄᴏʟɪsᴀᴏ\\r\\n▪️ʙᴀǫᴜᴇᴛs\\r\\n▪️ᴍᴏᴅᴏ ᴇᴄᴏ\\r\\n▪️ɢᴀʀᴀɴᴛɪᴀ\\r\\n💰18999€\\r\\n📱ᴡʜᴀᴛsᴀᴘᴘ +351910291038', NULL, 18999.00, 197000, 136, 'Automática', '2025-11-26 22:03:51', 'Ativo', 1),
(11, '𝗠𝗘𝗥𝗖𝗘𝗗𝗘𝗦 𝗕𝗘𝗡𝗭 𝗖𝗟𝗔 𝟮𝟬𝟬𝗱', 'Mercedes', 2000, 1990, 'Diesel', 'top cheio de extras', 'Volante aquecido\r\nmeow meow pici pici', 20000.00, 18000, 180, 'Automática', '2025-11-26 23:39:29', 'Ativo', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `fotos_anuncio`
--

CREATE TABLE `fotos_anuncio` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `caminho_foto` varchar(255) NOT NULL,
  `is_principal` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `fotos_anuncio`
--

INSERT INTO `fotos_anuncio` (`id`, `anuncio_id`, `caminho_foto`, `is_principal`) VALUES
(10, 6, 'uploads/car_photos/car_692760b1cebc42.28312292.jpg', 1),
(11, 6, 'uploads/car_photos/car_692760b1ceee67.08017534.jpg', 0),
(12, 6, 'uploads/car_photos/car_692760b1cef947.11349863.jpg', 0),
(13, 6, 'uploads/car_photos/car_692760b1cf0383.51827472.jpg', 0),
(14, 6, 'uploads/car_photos/car_692760b1cf0cb0.33790605.jpg', 0),
(15, 6, 'uploads/car_photos/car_692760b1cf1537.77582173.jpg', 0),
(16, 6, 'uploads/car_photos/car_692760b1cf1d54.31936074.jpg', 0),
(17, 6, 'uploads/car_photos/car_692760b1cf2578.41703380.jpg', 0),
(42, 8, 'uploads/car_photos/car_692762d31eb685.27162568.jpg', 1),
(43, 8, 'uploads/car_photos/car_692762d31ed451.98047627.jpg', 0),
(44, 8, 'uploads/car_photos/car_692762d31ec8f9.56576305.jpg', 0),
(45, 8, 'uploads/car_photos/car_692762d31edee9.44830139.jpg', 0),
(46, 8, 'uploads/car_photos/car_692762d31ee910.10752424.jpg', 0),
(47, 8, 'uploads/car_photos/car_692762d31ef2b9.48627992.jpg', 0),
(48, 8, 'uploads/car_photos/car_692762d31efe20.83646016.jpg', 0),
(49, 8, 'uploads/car_photos/car_692762d31f07d2.69939928.jpg', 0),
(64, 7, 'uploads/car_photos/car_692761676914e4.02211315.jpg', 1),
(65, 7, 'uploads/car_photos/car_69276167692cb6.80433402.jpg', 0),
(66, 7, 'uploads/car_photos/car_69276167693b17.79798641.jpg', 0),
(67, 7, 'uploads/car_photos/car_692761676948d2.74375226.jpg', 0),
(68, 7, 'uploads/car_photos/car_692761676956d5.10767267.jpg', 0),
(69, 7, 'uploads/car_photos/car_692761676963a1.76667671.jpg', 0),
(70, 7, 'uploads/car_photos/car_69276167697042.97909523.jpg', 0),
(71, 7, 'uploads/car_photos/car_692761676979d7.38445557.jpg', 0),
(72, 9, 'uploads/car_photos/car_69277947af9010.11868465.jpg', 1),
(73, 9, 'uploads/car_photos/car_69277947afc754.06883942.jpg', 0),
(74, 9, 'uploads/car_photos/car_69277947afd546.93641903.jpg', 0),
(75, 9, 'uploads/car_photos/car_69277947afdf46.09628583.jpg', 0),
(76, 9, 'uploads/car_photos/car_69277947afe903.15098832.jpg', 0),
(77, 9, 'uploads/car_photos/car_69277947aff2f2.69767753.jpg', 0),
(78, 9, 'uploads/car_photos/car_69277947affd15.51942265.jpg', 0),
(79, 9, 'uploads/car_photos/car_69277947b00511.42600506.jpg', 0),
(80, 11, 'uploads/car_photos/car_69278fb1c7e358.17774843.png', 0),
(81, 11, 'uploads/car_photos/car_69278fb1c82714.97281337.png', 0),
(82, 11, 'uploads/car_photos/car_69278fb1c82fe2.52237921.png', 0),
(83, 11, 'uploads/car_photos/car_69278fb1c83774.87412167.png', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','editor','viewer') NOT NULL DEFAULT 'editor',
  `data_registo` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `data_registo`) VALUES
(1, 'admin', 'admin@wfcars.pt', '$2y$10$wN1H9g.1B6wXhE2dG8b.nO4E1Zl6Cq7sFpL5vX2J0Yw0', 'admin', '2025-11-26 01:35:58'),
(2, 'tiago', 'tiagofsilva04@gmail.com', '$2y$10$V3ZXSZ3TMEWchJeowXKcSezduUOwuxm43aGW6TOniy5nYhzG0tieq', 'admin', '2025-11-27 00:09:24');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `anuncios`
--
ALTER TABLE `anuncios`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `fotos_anuncio`
--
ALTER TABLE `fotos_anuncio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anuncios`
--
ALTER TABLE `anuncios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `fotos_anuncio`
--
ALTER TABLE `fotos_anuncio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `fotos_anuncio`
--
ALTER TABLE `fotos_anuncio`
  ADD CONSTRAINT `fotos_anuncio_ibfk_1` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
