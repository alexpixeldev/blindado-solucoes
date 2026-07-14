-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26-Fev-2026 às 08:01
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `alex8076_blindado`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `acoes_disciplinares`
--

CREATE TABLE `acoes_disciplinares` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `datas` text NOT NULL,
  `tipo` enum('advertencia','suspensao') NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `data_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `acoes_disciplinares`
--

INSERT INTO `acoes_disciplinares` (`id`, `usuario_id`, `datas`, `tipo`, `motivo`, `descricao`, `arquivo`, `data_registro`) VALUES
(3, 37, '2026-02-26', 'advertencia', 'foi paia de mais', 'uhum uhum', NULL, '2026-02-26 02:40:10'),
(4, 37, '2026-02-26', 'suspensao', 'teste suspensao', 'descrição da suspensão', NULL, '2026-02-26 02:49:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `administradoras`
--

CREATE TABLE `administradoras` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `administradoras`
--

INSERT INTO `administradoras` (`id`, `nome`, `telefone`, `email`, `created_at`) VALUES
(1, 'AADM ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(2, 'CECOM ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(3, 'CONSTRUTORA SANTANA', NULL, NULL, '2026-02-04 05:18:52'),
(4, 'CRJ ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(5, 'DELERMANDO', NULL, NULL, '2026-02-04 05:18:52'),
(6, 'DIMENSÃO', NULL, NULL, '2026-02-04 05:18:52'),
(7, 'FORTT ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(8, 'J RODRIGUES', NULL, NULL, '2026-02-04 05:18:52'),
(9, 'JR ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(10, 'LIDERANÇA', NULL, NULL, '2026-02-04 05:18:52'),
(11, 'LM ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(12, 'MONJARDIM', NULL, NULL, '2026-02-04 05:18:52'),
(13, 'PAULO CIRINO ADMNISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(14, 'PROSPECTA', NULL, NULL, '2026-02-04 05:18:52'),
(15, 'R LOCASSO', NULL, NULL, '2026-02-04 05:18:52'),
(16, 'RC ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(17, 'RIBEIRO', NULL, NULL, '2026-02-04 05:18:52'),
(18, 'ROCHA AVELAR', NULL, NULL, '2026-02-04 05:18:52'),
(19, 'RUBI ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52'),
(20, 'SUA CONSERVADORA', NULL, NULL, '2026-02-04 05:18:52'),
(21, 'SUPREMA', NULL, NULL, '2026-02-04 05:18:52'),
(22, 'VIEIRA E PADUA', NULL, NULL, '2026-02-04 05:18:52'),
(23, 'WM ADMINISTRADORA', NULL, NULL, '2026-02-04 05:18:52');

-- --------------------------------------------------------

--
-- Estrutura da tabela `bases`
--

CREATE TABLE `bases` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `telefone` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `bases`
--

INSERT INTO `bases` (`id`, `nome`, `telefone`) VALUES
(1, 'Nova Guaraparí', '5527998173386'),
(2, 'Praia do morro', '5527999626933'),
(3, 'Vitória', '5527981034823'),
(4, 'Vila Velha', '5527996930305');

-- --------------------------------------------------------

--
-- Estrutura da tabela `categorias_ramais`
--

CREATE TABLE `categorias_ramais` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `categorias_ramais`
--

INSERT INTO `categorias_ramais` (`id`, `nome`, `data_criacao`) VALUES
(1, '1ª Porta Social', '2026-01-25 07:49:31'),
(2, '2ª Porta Social', '2026-01-25 07:49:40'),
(3, 'Garagem Térreo', '2026-01-25 07:49:47'),
(4, 'Garagem Rampa', '2026-01-25 07:49:54'),
(5, 'Academia', '2026-01-25 07:50:20'),
(6, 'Área de Lazer', '2026-01-25 07:50:33'),
(7, 'Banhista', '2026-01-25 07:51:06'),
(8, 'Garagem Subsolo', '2026-02-06 08:48:59');

-- --------------------------------------------------------

--
-- Estrutura da tabela `chat_mensagens`
--

CREATE TABLE `chat_mensagens` (
  `id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `arquivo_caminho` varchar(255) DEFAULT NULL,
  `arquivo_tipo` varchar(50) DEFAULT NULL,
  `arquivo_nome_original` varchar(255) DEFAULT NULL,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `data_edicao` datetime DEFAULT NULL,
  `status` enum('enviada','editada','apagada') NOT NULL DEFAULT 'enviada',
  `lida` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `chat_mensagens`
--

INSERT INTO `chat_mensagens` (`id`, `remetente_id`, `destinatario_id`, `mensagem`, `arquivo_caminho`, `arquivo_tipo`, `arquivo_nome_original`, `data_envio`, `data_edicao`, `status`, `lida`) VALUES
(37, 1, 21, 'teste', '', '', '', '2026-01-29 02:08:36', NULL, 'enviada', 1),
(38, 22, 21, 'alo', '', '', '', '2026-01-30 18:41:59', NULL, 'enviada', 1),
(39, 1, 21, '', 'uploads/chat/697d262b997eb_RELA____O_DE_MORADORES_CALIFORNIA.pdf', 'documento', 'RELAÇÃO DE MORADORES CALIFORNIA.pdf', '2026-01-30 18:44:11', NULL, 'enviada', 1),
(40, 1, 21, 'Teste', '', '', '', '2026-02-14 08:47:56', NULL, 'enviada', 1),
(41, 21, 1, 'Teste', '', '', '', '2026-02-14 09:10:11', NULL, 'enviada', 1),
(42, 1, 21, 'Ok', NULL, NULL, NULL, '2026-02-14 10:19:52', NULL, 'enviada', 0),
(43, 1, 21, 'Teste', NULL, NULL, NULL, '2026-02-14 10:22:34', NULL, 'enviada', 0),
(44, 1, 6, 'Teste chat ana', NULL, NULL, NULL, '2026-02-14 10:25:27', NULL, 'enviada', 1),
(45, 6, 1, 'Teste ok. Validado', NULL, NULL, NULL, '2026-02-14 10:27:02', NULL, 'enviada', 1),
(46, 23, 1, 'alou', NULL, NULL, NULL, '2026-02-20 07:15:47', NULL, 'enviada', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `contracheques`
--

CREATE TABLE `contracheques` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `data_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_ata`
--

CREATE TABLE `controle_ata` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) NOT NULL,
  `itens_ata` longtext DEFAULT NULL,
  `status` enum('ativo','inativo','pendente') DEFAULT 'ativo',
  `observacao` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `controle_ata`
--

INSERT INTO `controle_ata` (`id`, `edificio_id`, `itens_ata`, `status`, `observacao`, `usuario_id`, `data_criacao`, `data_atualizacao`) VALUES
(1, 63, '[{\"marca_modelo\":\"ATA GT\",\"ip\":\"172.18.248.45\",\"descricao\":\"Ata 01\",\"usuario\":\"admin\",\"senha\":\"@Blindado5079\"},{\"marca_modelo\":\"Intelbras GKM\",\"ip\":\"192.168.1.5:9215\",\"descricao\":\"Ata 02\",\"usuario\":\"admin\",\"senha\":\"5079\"},{\"marca_modelo\":\"Intelbras 200\",\"ip\":\"192.168.10.179\",\"descricao\":\"Ata 03\",\"usuario\":\"admin\",\"senha\":\"5079\"}]', 'ativo', NULL, 1, '2026-01-25 07:30:46', '2026-01-25 07:30:46'),
(2, 55, '[{\"marca_modelo\":\"ATA GT\",\"ip\":\"172.18.248.42\",\"descricao\":\"Ata 01\",\"usuario\":\"admin\",\"senha\":\"5079\"}]', 'ativo', NULL, 1, '2026-01-27 00:38:21', '2026-01-27 00:38:21');

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_dvr`
--

CREATE TABLE `controle_dvr` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) DEFAULT NULL,
  `base_id` int(11) DEFAULT NULL,
  `ip_dominio` varchar(255) DEFAULT NULL,
  `cloud` varchar(255) DEFAULT NULL,
  `porta_tcp` varchar(50) DEFAULT NULL,
  `porta_http` varchar(50) DEFAULT NULL,
  `login` varchar(100) DEFAULT NULL,
  `senha` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `senha_mibo` varchar(100) DEFAULT NULL,
  `numero_dvr` varchar(50) DEFAULT NULL,
  `ip_dvr` varchar(15) DEFAULT NULL,
  `porta` int(11) DEFAULT NULL,
  `usuario_dvr` varchar(100) DEFAULT NULL,
  `data_atualizacao` date NOT NULL,
  `status` enum('ativo','inativo','pendente') DEFAULT 'ativo',
  `observacao` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `controle_dvr`
--

INSERT INTO `controle_dvr` (`id`, `edificio_id`, `base_id`, `ip_dominio`, `cloud`, `porta_tcp`, `porta_http`, `login`, `senha`, `modelo`, `senha_mibo`, `numero_dvr`, `ip_dvr`, `porta`, `usuario_dvr`, `data_atualizacao`, `status`, `observacao`, `usuario_id`, `data_criacao`) VALUES
(1, 51, NULL, '192.168.1.123', '071I0200136QM', '37775', '8185', 'admin', '@Blindado58cftv', 'MHDX 1116', '', NULL, NULL, NULL, NULL, '0000-00-00', 'ativo', NULL, 1, '2026-01-27 02:15:06');

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_faciais`
--

CREATE TABLE `controle_faciais` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) NOT NULL,
  `marca_equipamento` varchar(100) DEFAULT NULL,
  `acessos` longtext DEFAULT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('ativo','inativo','pendente') DEFAULT 'ativo',
  `observacao` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `controle_faciais`
--

INSERT INTO `controle_faciais` (`id`, `edificio_id`, `marca_equipamento`, `acessos`, `data_atualizacao`, `status`, `observacao`, `usuario_id`, `data_criacao`) VALUES
(1, 59, 'Control ID', '[{\"ip\":\"172.18.243.6\",\"obs\":\"1\\u00ba Acesso\"},{\"ip\":\"172.18.243.7\",\"obs\":\"2\\u00ba Acesso\"},{\"ip\":\"172.18.243.8\",\"obs\":\"3\\u00ba Acesso\"}]', '2026-01-25 06:38:48', 'ativo', NULL, 1, '2026-01-25 06:38:48'),
(2, 38, 'Control ID', '[{\"ip\":\"172.18.243.40\",\"obs\":\"1\\u00ba Acesso\"},{\"ip\":\"172.18.243.41\",\"obs\":\"2\\u00ba Acesso\"},{\"ip\":\"172.18.243.42\",\"obs\":\"3\\u00ba Acesso\"},{\"ip\":\"x\",\"obs\":\"4\\u00ba Acesso\"},{\"ip\":\"172.18.243.63\",\"obs\":\"5\\u00ba Acesso\"}]', '2026-01-27 00:33:23', 'ativo', NULL, 1, '2026-01-27 00:33:23'),
(3, 60, 'Control ID', '[{\"ip\":\"172.18.243.15\",\"obs\":\"1\\u00ba Acesso\"},{\"ip\":\"172.18.243.16\",\"obs\":\"2\\u00ba Acesso\"}]', '2026-01-27 00:34:26', 'ativo', NULL, 1, '2026-01-27 00:34:26'),
(4, 54, 'Control ID', '[{\"ip\":\"172.18.243.43\",\"obs\":\"1\\u00ba Acesso\"},{\"ip\":\"x\",\"obs\":\"2\\u00ba Acesso\"},{\"ip\":\"172.18.243.44\",\"obs\":\"3\\u00ba Acesso\"}]', '2026-01-27 00:35:56', 'ativo', NULL, 1, '2026-01-27 00:35:56'),
(5, 62, 'Control ID', '[{\"ip\":\"172.18.243.50\",\"obs\":\"1\\u00ba Acesso\"},{\"ip\":\"172.18.243.51\",\"obs\":\"2\\u00ba Acesso\"},{\"ip\":\"172.18.243.52\",\"obs\":\"3\\u00ba Acesso\"},{\"ip\":\"172.18.243.53\",\"obs\":\"4\\u00ba Acesso\"}]', '2026-02-24 05:14:22', 'ativo', '', 33, '2026-01-27 00:36:57');

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_ips`
--

CREATE TABLE `controle_ips` (
  `id` int(11) NOT NULL,
  `base_id` int(11) DEFAULT NULL,
  `edificio_id` int(11) DEFAULT NULL,
  `estacao` varchar(255) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `ramais` longtext DEFAULT NULL,
  `status` enum('ativo','inativo','pendente') DEFAULT 'ativo',
  `usuario_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `controle_ips`
--

INSERT INTO `controle_ips` (`id`, `base_id`, `edificio_id`, `estacao`, `ip`, `ramais`, `status`, `usuario_id`, `data_criacao`, `data_atualizacao`) VALUES
(2, 1, NULL, 'Servidor tela 4 linha 1', '192.168.10.189', '[]', 'ativo', 1, '2026-01-27 02:25:18', '2026-01-27 02:25:18'),
(3, 1, NULL, 'Tela 4 linha 2', '192.168.10.185', '[]', 'ativo', 1, '2026-01-27 02:25:43', '2026-01-27 02:25:43'),
(4, 1, NULL, 'Tela Corais', '192.168.10.188', '[]', 'ativo', 1, '2026-01-27 02:25:59', '2026-01-27 02:25:59'),
(5, 1, NULL, 'Moni Estação 1', '192.168.10.182', '[{\"numero\":\"2007\",\"senha\":\"rml2007blindado\"},{\"numero\":\"2015\",\"senha\":\"rml2015blindado\"}]', 'ativo', 1, '2026-01-27 02:27:50', '2026-01-27 02:27:50'),
(6, 1, NULL, 'Moni Estação 2', '192.168.10.201', '[{\"numero\":\"2008\",\"senha\":\"rml2008blindado\"},{\"numero\":\"2017\",\"senha\":\"rml2017blindado\"}]', 'ativo', 1, '2026-01-27 02:28:50', '2026-01-27 02:28:50'),
(7, 1, NULL, 'Moni estação 3', '192.168.10.225', '[{\"numero\":\"2006\",\"senha\":\"rml2006blindado\"},{\"numero\":\"2016\",\"senha\":\"rml2016blindado\"}]', 'ativo', 1, '2026-01-27 02:29:42', '2026-01-27 02:29:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_pop_dependentes`
--

CREATE TABLE `controle_pop_dependentes` (
  `id` int(11) NOT NULL,
  `pop_id` int(11) NOT NULL,
  `edificio_id` int(11) DEFAULT NULL,
  `nome_personalizado` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `controle_pop_dependentes`
--

INSERT INTO `controle_pop_dependentes` (`id`, `pop_id`, `edificio_id`, `nome_personalizado`) VALUES
(62, 1, 2, NULL),
(63, 1, 29, NULL),
(64, 1, 43, NULL),
(65, 1, 55, NULL),
(66, 1, 24, NULL),
(67, 1, 62, NULL),
(68, 1, 61, NULL),
(69, 1, 16, NULL),
(70, 1, 30, NULL),
(71, 1, 33, NULL),
(72, 1, 1, NULL),
(73, 1, 64, NULL),
(74, 1, 21, NULL),
(75, 1, 50, NULL),
(76, 1, NULL, 'Rádio Maestro'),
(77, 2, 47, NULL),
(78, 2, 38, NULL),
(79, 2, 57, NULL),
(80, 2, 32, NULL),
(81, 2, 46, NULL),
(82, 2, 54, NULL),
(83, 2, 40, NULL),
(84, 2, 42, NULL),
(85, 2, 52, NULL),
(86, 2, 37, NULL),
(87, 2, 51, NULL),
(88, 2, NULL, 'Rádio Corais'),
(89, 4, 9, NULL),
(90, 4, 53, NULL),
(91, 4, 10, NULL),
(92, 4, 31, NULL),
(93, 4, 35, NULL),
(94, 4, 56, NULL),
(95, 4, 15, NULL),
(96, 4, 12, NULL),
(97, 4, 27, NULL),
(98, 4, 13, NULL),
(99, 4, 26, NULL),
(100, 5, 14, NULL),
(101, 5, 17, NULL),
(102, 5, 11, NULL),
(103, 5, 63, NULL),
(104, 5, 18, NULL),
(105, 5, 58, NULL),
(106, 5, 20, NULL),
(107, 5, 25, NULL),
(108, 6, 41, NULL),
(109, 6, 39, NULL),
(110, 6, 59, NULL),
(111, 6, 6, NULL),
(112, 6, 45, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_radio_fibra`
--

CREATE TABLE `controle_radio_fibra` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) DEFAULT NULL,
  `base_id` int(11) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `local_detalhe` varchar(255) DEFAULT NULL,
  `modo` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `login` varchar(100) DEFAULT NULL,
  `senha` varchar(100) DEFAULT NULL,
  `is_pop` tinyint(1) DEFAULT 0,
  `pop_responsavel_id` int(11) DEFAULT NULL,
  `status` enum('ativo','inativo','pendente') DEFAULT 'ativo',
  `observacao` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `controle_radio_fibra`
--

INSERT INTO `controle_radio_fibra` (`id`, `edificio_id`, `base_id`, `ip`, `local_detalhe`, `modo`, `marca`, `modelo`, `login`, `senha`, `is_pop`, `pop_responsavel_id`, `status`, `observacao`, `usuario_id`, `data_criacao`, `data_atualizacao`) VALUES
(1, NULL, 2, '192.168.1.60', 'Serve Mar de veneza', 'AP', 'Intelbras', 'POWERBEAM_5AC', 'admin', '50795079', 1, NULL, 'ativo', NULL, 1, '2026-01-27 00:44:15', '2026-01-27 05:10:04'),
(2, NULL, 1, '172.18.252.7', 'Recebe do Maestro', 'Cliente', 'Ubiquit', 'POWERBEAM_5AC', 'Blindado', '@Blindado5079', 1, NULL, 'ativo', NULL, 1, '2026-01-27 00:55:12', '2026-01-27 06:01:51'),
(4, 19, NULL, '192.168.10.63', 'Recebe do Mar de Veneza', '', '', '', '', '', 1, NULL, 'ativo', NULL, 1, '2026-01-27 06:08:11', '2026-01-27 06:08:11'),
(5, 28, NULL, 'http://192.168.10.81', 'Recebe do Maestro', 'Cliente', 'Ubiquit', 'POWERBEAM_5AC', 'blindado', '@Blindado5079', 1, NULL, 'ativo', NULL, 1, '2026-01-27 06:10:35', '2026-01-27 06:10:35'),
(6, 8, NULL, '', '', '', '', '', '', '', 1, NULL, 'ativo', NULL, 1, '2026-01-27 06:12:49', '2026-01-27 06:12:49');

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_ramais`
--

CREATE TABLE `controle_ramais` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) DEFAULT NULL,
  `base_id` int(11) DEFAULT NULL,
  `numero_ramal` varchar(50) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `comando_acesso` text DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `controle_ramais`
--

INSERT INTO `controle_ramais` (`id`, `edificio_id`, `base_id`, `numero_ramal`, `categoria_id`, `comando_acesso`, `status`, `usuario_id`, `created_at`) VALUES
(23, 32, NULL, '9096', 7, NULL, 'ativo', 33, '2026-02-20 01:06:03'),
(24, 32, NULL, '9096', 1, NULL, 'ativo', 33, '2026-02-20 01:06:03');

-- --------------------------------------------------------

--
-- Estrutura da tabela `edificios`
--

CREATE TABLE `edificios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `base_id` int(11) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `sindico_nome` varchar(255) DEFAULT NULL,
  `sindico_contato` varchar(255) DEFAULT NULL,
  `administradora_id` int(11) DEFAULT NULL,
  `observacao_ficha_locacao` text DEFAULT NULL,
  `sindico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `edificios`
--

INSERT INTO `edificios` (`id`, `nome`, `base_id`, `endereco`, `sindico_nome`, `sindico_contato`, `administradora_id`, `observacao_ficha_locacao`, `sindico_id`) VALUES
(1, 'Prediletto', 1, NULL, 'BRUNO HENRIQUE', NULL, 7, NULL, 23),
(2, 'Refinatto', 2, NULL, 'CARLOS HENRIQUE', NULL, 18, NULL, 49),
(6, 'Alessandro', 2, NULL, 'RAFAEL', NULL, 1, NULL, 32),
(7, 'Angélica Paganini', 2, NULL, 'EVA', NULL, 16, NULL, 33),
(8, 'Atrium Verona', 2, NULL, 'MARCONI', NULL, 10, NULL, 35),
(9, 'Antônio Di Pietro', 2, NULL, 'LUCIO', NULL, 8, NULL, 34),
(10, 'Bolonha', 2, NULL, 'ULISSES', NULL, 22, NULL, 36),
(11, 'Casa Blanca', 2, NULL, 'RENATO CONDE', NULL, 14, NULL, 27),
(12, 'Cordialle', 2, NULL, 'ASSEF', NULL, 23, NULL, 37),
(13, 'Del Pietro', 2, NULL, 'ANA PAULA', NULL, 21, NULL, 38),
(14, 'Dom Henrique', 2, NULL, 'DELERMANDO', NULL, 5, NULL, 9),
(15, 'Gabriel', 2, NULL, 'PEDRO HENRIQUE', NULL, 14, NULL, 39),
(16, 'Graal', 2, NULL, 'CARLOS JOSÉ', NULL, 23, NULL, 40),
(17, 'Ilha das garças', 2, NULL, 'RENATO CONDE', NULL, 14, NULL, 27),
(18, 'Juiz Pedro Guimarães', 2, '', 'MANOEL', NULL, 6, NULL, 41),
(19, 'Lavínia', 2, NULL, 'GUILHERME', NULL, 13, NULL, 42),
(20, 'Mar e Sol', 2, NULL, 'ROBSON', NULL, 23, NULL, 43),
(21, 'Maria Bomfim', 2, '', 'ALEX ADM', NULL, 8, NULL, 26),
(22, 'Mirador', 2, NULL, 'NEMER', NULL, 22, NULL, 44),
(23, 'Moorea Beach', 2, '', 'ANTONIO CARLOS', '', 2, NULL, 45),
(24, 'Ninive Almeida', 2, NULL, 'ARLINDO', NULL, 20, NULL, 46),
(25, 'Praia das Virtudes', 2, NULL, 'RENATO CONDE', NULL, 14, NULL, 27),
(26, 'Praia Bella', 2, NULL, 'RONALDO', NULL, 1, NULL, 47),
(27, 'Rubem Braga', 2, NULL, 'THIAGO JESUS', NULL, 7, NULL, 15),
(28, 'Summer Hill', 2, NULL, 'SANDRA', NULL, 14, NULL, 50),
(29, 'Tifany', 2, NULL, 'RUBENS', NULL, 15, NULL, 51),
(30, 'Varandas do mar', 2, NULL, 'BRUNO VICENTE', NULL, 12, NULL, 52),
(31, 'Viena', 2, NULL, 'SACRAMENTO', NULL, 8, NULL, 53),
(32, 'Atobá', 1, '', 'ADAHY', '', 9, NULL, 1),
(33, 'Bellagio', 1, '', 'FLAVIO', '', 8, NULL, 2),
(34, 'Caiado Rodrigues', 1, NULL, 'NUBIA', NULL, 19, NULL, 3),
(35, 'Califórnia', 1, NULL, 'PATRICIA', NULL, 14, NULL, 4),
(36, 'Carolina', 1, NULL, 'GERLAINE', NULL, 3, NULL, 5),
(37, 'Corais da enseada', 1, NULL, 'PEDRO', NULL, 1, NULL, 6),
(38, 'Enseada vip', 1, '', 'FABIANO RAMOS', NULL, 14, NULL, 7),
(39, 'Granito', 1, NULL, 'RENAN CONDE', NULL, 22, NULL, 8),
(40, 'Ilha de comandatuba', 1, NULL, 'DELERMANDO', NULL, 5, NULL, 9),
(41, 'Jean Marcel', 1, NULL, 'DELERMANDO', NULL, 5, NULL, 9),
(42, 'Long Summer', 1, NULL, 'FRANCISCO', NULL, 14, NULL, 10),
(43, 'Maestro', 1, NULL, 'MARIO', NULL, 22, NULL, 11),
(44, 'Mais JK', 1, NULL, 'TIAGO DE OLIVEIRA', NULL, 5, NULL, 12),
(45, 'Malibu', 1, NULL, 'ZELIA', NULL, 6, NULL, 13),
(46, 'Margarida Motta', 1, NULL, 'MARCOS', NULL, 14, NULL, 16),
(47, 'Marek', 1, '', 'THIAGO JESUS', NULL, 10, NULL, 15),
(48, 'Mar de Veneza', 1, NULL, 'VIVALDO', NULL, 23, NULL, 14),
(49, 'Monte Blu', 1, NULL, 'JORGE', NULL, 4, NULL, 17),
(50, 'Murano', 1, NULL, 'CESAR', NULL, 15, NULL, 18),
(51, 'Panoramic', 1, NULL, 'NILTON', NULL, 18, NULL, 19),
(52, 'Peracanga', 1, NULL, 'CINELI', NULL, 17, NULL, 20),
(53, 'Pontal D\'areia', 1, NULL, 'SHIRLEY', NULL, 16, NULL, 21),
(54, 'Praia de peracanga', 1, NULL, 'AUGUSTO', NULL, 18, NULL, 22),
(55, 'Recanto da praia', 1, NULL, 'SIDNEY', NULL, 11, NULL, 24),
(56, 'Santorini', 1, NULL, 'CLAUDIO', NULL, 22, NULL, 25),
(57, 'Seven', 1, NULL, 'RENATO CONDE', NULL, 14, NULL, 27),
(58, 'Serenata', 1, NULL, 'ALEX ADM', NULL, 8, NULL, 26),
(59, 'Solar Mariz', 1, '', 'JOSÉ FABIANO', NULL, 1, NULL, 28),
(60, 'Sollarium', 1, NULL, 'DELERMANDO', NULL, 5, NULL, 9),
(61, 'Splendia', 1, NULL, 'MARIA', NULL, 7, NULL, 29),
(62, 'Veneto', 1, NULL, 'ANDERSON', NULL, 18, NULL, 30),
(63, 'Verdes mares', 1, NULL, 'MAURO', NULL, 6, NULL, 31),
(64, 'Prime', 2, NULL, 'SUA CONSERV.', NULL, 20, NULL, 48),
(70, 'Alex Pixel 2', 1, 'Avenida Meaípe, 138, Nova Guaraparí, Guaraparí - ES', 'Alex Pixel', '27996930305', 22, 'Não é permitida a ocupação do apartamento sem a apresentação desta ficha cadastral\r\ntotalmente preenchida. Assim, como o empréstimo do cartão de acesso ao prédio. A\r\nficha deverá ser entregue com 24 horas de antecedência à ocupação.\r\n\r\nART. 9º da convenção de condomínio. Sei e concordo que sou responsável perante o\r\ncondomínio, civil ou criminalmente, por todos os atos ou infrações que venham a ser\r\npraticados por meus empregados, inquilinos ou ocupantes da minha unidade.', NULL);

--
-- Acionadores `edificios`
--
DELIMITER $$
CREATE TRIGGER `after_delete_edificio` AFTER DELETE ON `edificios` FOR EACH ROW BEGIN
    UPDATE sindicos SET edificio_id = NULL 
    WHERE id = OLD.sindico_id 
    AND OLD.sindico_id IS NOT NULL
    AND (SELECT COUNT(*) FROM edificios WHERE sindico_id = OLD.sindico_id) = 0;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `entregas`
--

CREATE TABLE `entregas` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) NOT NULL,
  `numero_apartamento` varchar(10) NOT NULL,
  `data_entrega` date DEFAULT NULL,
  `hora_entrega` time NOT NULL,
  `situacao_recebimento` enum('zelador','sindico','porteiro','morador','recepcao','retornou') NOT NULL,
  `transportadora` enum('Transportadora','Shein','Shopee','Amazon','Aliexpress','Temu','Correios','Mercado Livre','Internet','Compra Online','Outros') NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `atualizado_por` int(11) DEFAULT NULL,
  `data_atualizacao` datetime DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `entregas`
--

INSERT INTO `entregas` (`id`, `edificio_id`, `numero_apartamento`, `data_entrega`, `hora_entrega`, `situacao_recebimento`, `transportadora`, `usuario_id`, `atualizado_por`, `data_atualizacao`, `observacao`, `data_criacao`) VALUES
(1, 32, '101', '2026-01-24', '23:55:00', 'zelador', 'Internet', 1, 1, '2026-02-24 02:30:29', 'teste de observação', '2026-01-25 02:42:05'),
(4, 9, '123', '2026-02-23', '16:09:00', 'morador', 'Amazon', 33, 33, '2026-02-24 02:30:29', 'fsf', '2026-02-23 19:09:54'),
(5, 33, '102', '2026-02-24', '04:05:00', '', 'Mercado Livre', 33, 3, '2026-02-24 02:35:53', '', '2026-02-24 04:58:55');

-- --------------------------------------------------------

--
-- Estrutura da tabela `extras`
--

CREATE TABLE `extras` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `atualizado_por` int(11) DEFAULT NULL,
  `data_atualizacao` datetime DEFAULT NULL,
  `data_extra` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `local` varchar(255) NOT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `extras`
--

INSERT INTO `extras` (`id`, `usuario_id`, `atualizado_por`, `data_atualizacao`, `data_extra`, `hora_inicio`, `hora_fim`, `local`, `arquivo`, `data_registro`, `registrado_por`) VALUES
(6, 37, NULL, NULL, '2026-02-26', '07:00:00', '17:00:00', 'Matriz NG', NULL, '2026-02-26 02:21:31', 33),
(7, 37, NULL, NULL, '2026-01-09', '07:00:00', '17:00:00', 'Matriz NG', NULL, '2026-02-26 02:21:53', 33);

-- --------------------------------------------------------

--
-- Estrutura da tabela `faciais`
--

CREATE TABLE `faciais` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `documento` varchar(100) NOT NULL,
  `edificio` varchar(100) NOT NULL,
  `apartamento` varchar(50) NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `integrado_controlid` tinyint(1) DEFAULT 0,
  `controlid_usuario_id` int(11) DEFAULT NULL,
  `metodo_integracao` enum('local','api','webscraping') DEFAULT 'local',
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `faciais`
--

INSERT INTO `faciais` (`id`, `nome`, `documento`, `edificio`, `apartamento`, `foto_path`, `integrado_controlid`, `controlid_usuario_id`, `metodo_integracao`, `data_cadastro`) VALUES
(1, 'Alex teste', '23324234', 'Edificio A', '23', '1769989786_WhatsApp Image 2026-01-30 at 08.59.49.jpeg', 0, NULL, 'local', '2026-02-01 20:49:46');

-- --------------------------------------------------------

--
-- Estrutura da tabela `faltas`
--

CREATE TABLE `faltas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `datas` text NOT NULL,
  `tipo` enum('justificada','injustificada') NOT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `motivo` varchar(255) NOT NULL DEFAULT '',
  `descricao` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `faltas`
--

INSERT INTO `faltas` (`id`, `usuario_id`, `datas`, `tipo`, `arquivo`, `data_registro`, `motivo`, `descricao`) VALUES
(3, 37, '2026-02-01', 'injustificada', NULL, '2026-02-26 03:06:00', 'falta motivo', 'desc falta');

-- --------------------------------------------------------

--
-- Estrutura da tabela `ferias`
--

CREATE TABLE `ferias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `data_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `funcoes`
--

CREATE TABLE `funcoes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `funcoes`
--

INSERT INTO `funcoes` (`id`, `nome`, `created_at`, `updated_at`) VALUES
(1, 'Portaria', '2026-02-24 05:45:56', '2026-02-24 05:45:56'),
(2, 'Limpeza', '2026-02-24 05:46:09', '2026-02-24 05:46:09'),
(3, 'Operador', '2026-02-24 05:46:14', '2026-02-24 05:46:14');

-- --------------------------------------------------------

--
-- Estrutura da tabela `locacoes`
--

CREATE TABLE `locacoes` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) NOT NULL,
  `tipo_usuario` varchar(20) DEFAULT NULL,
  `numero_apartamento` varchar(10) DEFAULT NULL,
  `nome_morador` varchar(255) DEFAULT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `data_locacao` date DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `locador_nome` varchar(255) DEFAULT NULL,
  `locador_telefone` varchar(50) DEFAULT NULL,
  `user_whatsapp` varchar(20) DEFAULT NULL,
  `data_entrada` date DEFAULT NULL,
  `data_saida` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `locacoes`
--

INSERT INTO `locacoes` (`id`, `edificio_id`, `tipo_usuario`, `numero_apartamento`, `nome_morador`, `cpf_cnpj`, `telefone`, `email`, `data_locacao`, `data_criacao`, `locador_nome`, `locador_telefone`, `user_whatsapp`, `data_entrada`, `data_saida`, `observacoes`, `data_registro`) VALUES
(21, 10, 'locador', '12', NULL, NULL, NULL, NULL, '2026-02-23', '2026-02-23 19:16:42', '', '', NULL, '2026-02-24', '2026-02-26', '', '2026-02-23 19:16:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `locacoes_inquilinos`
--

CREATE TABLE `locacoes_inquilinos` (
  `id` int(11) NOT NULL,
  `locacao_id` int(11) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `locacoes_inquilinos`
--

INSERT INTO `locacoes_inquilinos` (`id`, `locacao_id`, `nome`, `documento`, `telefone`) VALUES
(35, 21, 'ffw', 'wewe', '(27) 99999-9999');

-- --------------------------------------------------------

--
-- Estrutura da tabela `locacoes_veiculos`
--

CREATE TABLE `locacoes_veiculos` (
  `id` int(11) NOT NULL,
  `locacao_id` int(11) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `cor` varchar(50) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `acesso_garagem` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `ocorrencias`
--

CREATE TABLE `ocorrencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `atualizado_por` int(11) DEFAULT NULL,
  `data_atualizacao` datetime DEFAULT NULL,
  `supervisor_nome` varchar(255) NOT NULL,
  `operadores_nomes` text NOT NULL,
  `edificio_id` int(11) DEFAULT NULL,
  `base_id` int(11) DEFAULT NULL,
  `descricao` text NOT NULL,
  `periodo_dia` enum('dia','noite') DEFAULT NULL,
  `data_ocorrencia` date NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `ocorrencias`
--

INSERT INTO `ocorrencias` (`id`, `usuario_id`, `atualizado_por`, `data_atualizacao`, `supervisor_nome`, `operadores_nomes`, `edificio_id`, `base_id`, `descricao`, `periodo_dia`, `data_ocorrencia`, `data_criacao`) VALUES
(16, 33, 33, '2026-02-24 02:30:29', 'vinicius', 'alex', NULL, 1, '<p>teste</p>', 'dia', '2026-02-24', '2026-02-24 04:57:46');

-- --------------------------------------------------------

--
-- Estrutura da tabela `ocorrencias_midia`
--

CREATE TABLE `ocorrencias_midia` (
  `id` int(11) NOT NULL,
  `ocorrencia_id` int(11) NOT NULL,
  `tipo_midia` enum('imagem','video','audio') NOT NULL,
  `caminho_arquivo` varchar(500) NOT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `prestadores_servico`
--

CREATE TABLE `prestadores_servico` (
  `id` int(11) NOT NULL,
  `edificio_id` int(11) NOT NULL,
  `numero_apartamento` varchar(10) NOT NULL,
  `data_servico` date DEFAULT NULL,
  `hora_servico` time NOT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `nome_funcionario` varchar(255) NOT NULL,
  `numero_matricula` varchar(50) DEFAULT NULL,
  `tipo_servico` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `atualizado_por` int(11) DEFAULT NULL,
  `data_atualizacao` datetime DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `prestadores_servico`
--

INSERT INTO `prestadores_servico` (`id`, `edificio_id`, `numero_apartamento`, `data_servico`, `hora_servico`, `nome_empresa`, `nome_funcionario`, `numero_matricula`, `tipo_servico`, `usuario_id`, `atualizado_por`, `data_atualizacao`, `observacao`, `data_criacao`) VALUES
(4, 32, 'COND', '2026-02-24', '06:04:00', 'ELEVATEL', 'João', '1234546', 'Chamado', 33, 3, '2026-02-24 02:36:20', '', '2026-02-24 05:05:15');

-- --------------------------------------------------------

--
-- Estrutura da tabela `sindicos`
--

CREATE TABLE `sindicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `edificio_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `sindicos`
--

INSERT INTO `sindicos` (`id`, `nome`, `telefone`, `email`, `edificio_id`, `created_at`) VALUES
(1, 'ADAHY', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(2, 'FLAVIO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(3, 'NUBIA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(4, 'PATRICIA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(5, 'GERLAINE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(6, 'PEDRO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(7, 'FABIANO RAMOS', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(8, 'RENAN CONDE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(9, 'DELERMANDO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(10, 'FRANCISCO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(11, 'MARIO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(12, 'TIAGO DE OLIVEIRA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(13, 'ZELIA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(14, 'VIVALDO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(15, 'THIAGO JESUS', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(16, 'MARCOS', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(17, 'JORGE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(18, 'CESAR', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(19, 'NILTON', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(20, 'CINELI', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(21, 'SHIRLEY', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(22, 'AUGUSTO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(23, 'BRUNO HENRIQUE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(24, 'SIDNEY', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(25, 'CLAUDIO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(26, 'ALEX ADM', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(27, 'RENATO CONDE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(28, 'JOSÉ FABIANO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(29, 'MARIA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(30, 'ANDERSON', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(31, 'MAURO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(32, 'RAFAEL', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(33, 'EVA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(34, 'LUCIO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(35, 'MARCONI', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(36, 'ULISSES', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(37, 'ASSEF', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(38, 'ANA PAULA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(39, 'PEDRO HENRIQUE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(40, 'CARLOS JOSÉ', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(41, 'MANOEL', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(42, 'GUILHERME', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(43, 'ROBSON', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(44, 'NEMER', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(45, 'ANTONIO CARLOS', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(46, 'ARLINDO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(47, 'RONALDO', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(48, 'SUA CONSERV.', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(49, 'CARLOS HENRIQUE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(50, 'SANDRA', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(51, 'RUBENS', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(52, 'BRUNO VICENTE', NULL, NULL, NULL, '2026-02-04 08:12:07'),
(53, 'SACRAMENTO', NULL, NULL, NULL, '2026-02-04 08:12:07');

-- --------------------------------------------------------

--
-- Estrutura da tabela `situacoes_entrega`
--

CREATE TABLE `situacoes_entrega` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `situacoes_entrega`
--

INSERT INTO `situacoes_entrega` (`id`, `nome`) VALUES
(5, 'Deixado na Recepção'),
(4, 'Morador'),
(3, 'Porteiro'),
(6, 'Retornou'),
(2, 'Síndico'),
(1, 'Zelador');

-- --------------------------------------------------------

--
-- Estrutura da tabela `telas_layout`
--

CREATE TABLE `telas_layout` (
  `id` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `linha` int(11) NOT NULL,
  `tela` int(11) NOT NULL,
  `edificio1_id` int(11) DEFAULT NULL,
  `edificio2_id` int(11) DEFAULT NULL,
  `edificio3_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `telas_layout`
--

INSERT INTO `telas_layout` (`id`, `base_id`, `linha`, `tela`, `edificio1_id`, `edificio2_id`, `edificio3_id`) VALUES
(13, 1, 0, 0, 45, 55, 36),
(14, 1, 0, 1, NULL, NULL, NULL),
(15, 1, 0, 2, NULL, NULL, NULL),
(16, 1, 0, 3, NULL, NULL, NULL),
(17, 1, 0, 4, NULL, NULL, NULL),
(18, 1, 1, 0, NULL, NULL, NULL),
(19, 1, 1, 1, NULL, NULL, NULL),
(20, 1, 1, 2, NULL, NULL, NULL),
(21, 1, 1, 3, NULL, NULL, NULL),
(22, 1, 1, 4, NULL, NULL, NULL),
(23, 1, 2, 0, NULL, NULL, NULL),
(24, 1, 2, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `transportadoras`
--

CREATE TABLE `transportadoras` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `transportadoras`
--

INSERT INTO `transportadoras` (`id`, `nome`) VALUES
(5, 'Aliexpress'),
(4, 'Amazon'),
(10, 'Compra Online'),
(7, 'Correios'),
(9, 'Internet'),
(8, 'Mercado Livre'),
(11, 'Outros'),
(2, 'Shein'),
(3, 'Shopee'),
(6, 'Temu'),
(1, 'Transportadora Própria');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `nome_real` varchar(255) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `numero_cartao` varchar(20) DEFAULT NULL,
  `foto_colaborador` varchar(255) DEFAULT NULL,
  `arquivo_cpf` varchar(255) DEFAULT NULL,
  `arquivo_rg` varchar(255) DEFAULT NULL,
  `categoria` enum('gerente','supervisor','administrativo','operador','colaborador','diretor','tecnico') NOT NULL,
  `funcao_id` int(11) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `status_chat` varchar(20) DEFAULT 'offline',
  `ultimo_acesso` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `nome_real`, `rg`, `cpf`, `data_admissao`, `numero_cartao`, `foto_colaborador`, `arquivo_cpf`, `arquivo_rg`, `categoria`, `funcao_id`, `senha`, `status_chat`, `ultimo_acesso`) VALUES
(1, 'alex', 'Alex Silva', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'operador', NULL, '$2y$10$ktkWp99QRZCY0u/3yvONE.u2Yh3d99/F0LGQ49GQEMnlmwOIHu3BG', 'online', '2026-02-20 00:38:53'),
(3, 'wesley', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'gerente', NULL, '$2y$10$KEMKt58zIylk1r/swZhMe.vMurYROcArQD8ouzjMVpV9QhxXjOWhG', 'online', '2026-02-24 02:36:45'),
(6, 'ana', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'administrativo', NULL, '$2y$10$6HCprYlVSpom6ASscaoxUuaemeQsAQqePvJFuBzdr8.pLM3D8R/xu', 'online', '2026-02-23 15:51:32'),
(23, 'ludiane', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'supervisor', NULL, '$2y$10$uZGcB/rpa9VMG3/pzkOGE..mLz.5oM82W2g6seX55CwZS33gbO3km', 'online', '2026-02-20 07:16:21'),
(24, 'vinicius', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'supervisor', NULL, '$2y$10$dYIKY3GLpLB3amjjMHjYduXtOidAvuUmACxtNAzvG94Ldcmhon3fG', 'offline', '2026-02-14 10:28:07'),
(25, 'richard', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'operador', NULL, '$2y$10$l7dacWbLRBZv/fywHHCBq.7Qfv5tVaNhoYJDuFg7b0eP/BdylhIsy', 'offline', '2026-02-14 10:28:28'),
(27, 'raphael', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'gerente', NULL, '$2y$10$cd5ZVJ8jDwFzcrYh7d46feGogdutiVLtYK74emIHhabSC2EMhpbpe', 'offline', '2026-02-14 10:30:18'),
(28, 'davi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'operador', NULL, '$2y$10$gjavWFfsqNsFHXVHIWVcjux5diZPe9rps1ZYqR0Dt7q2WUgvvSexC', 'offline', '2026-02-14 10:31:19'),
(29, 'wesleycosta', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'operador', NULL, '$2y$10$VbQ3ITCUivxtNNNUyvZ4perglI.tvP2ZvT3eZFu2DEmOpDN8VwVia', 'offline', '2026-02-14 10:31:38'),
(30, 'larissa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'operador', NULL, '$2y$10$56iC50Go1CwWBF/9i44CZOpjgkdcwrloR8yogPeb0dPTuuaLgK3aq', 'offline', '2026-02-14 10:31:53'),
(31, 'gabriel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'operador', NULL, '$2y$10$1t/DaZS1VYFT78xPTe2hReOIUMzOL7Q3unEPKI8OeH47jLt1sB2kC', 'offline', '2026-02-14 10:32:46'),
(32, 'leonardo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'operador', NULL, '$2y$10$a5IIYXn0q/xzOC1LtArWj..E/zYayrviYX00/uMR7j2fvPkoIEFQm', 'online', '2026-02-15 07:48:55'),
(33, 'admin', 'Desenvolvedor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'gerente', NULL, '$2y$10$D4DK/ktZO5p68/EShEfceuW474QNqlfdzhcgkMvJAqRrnd4.dBjui', 'online', '2026-02-26 04:01:34'),
(34, 'joao', 'João Vitor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'diretor', NULL, '$2y$10$V4au..7z3vfXwngXw3Ou5.MUFVTOszBVA6xFZkBphFnCums2pSEcC', 'offline', '2026-02-14 10:35:02'),
(35, 'gabrielroratto', 'Gabriel Roratto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'supervisor', NULL, '$2y$10$HJ.ImX/B9aDoPb8aO4Key./3bAaNswyK0aUstqNRO0pnGUz8FCWJu', 'online', '2026-02-19 11:52:13'),
(37, 'colaborador', 'Blindado da Silva', 'ES-789456', '12345678912', '2025-02-26', '111 222 333 444', NULL, NULL, NULL, 'colaborador', 3, '$2y$10$m800pFBuBylLa72Vl3TNROA3H.ZiWdi3M7x4WJEwFcKYMZgk.JG4O', 'offline', '2026-02-26 02:20:34');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `acoes_disciplinares`
--
ALTER TABLE `acoes_disciplinares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `administradoras`
--
ALTER TABLE `administradoras`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `bases`
--
ALTER TABLE `bases`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `categorias_ramais`
--
ALTER TABLE `categorias_ramais`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_remetente` (`remetente_id`),
  ADD KEY `idx_destinatario` (`destinatario_id`);

--
-- Índices para tabela `contracheques`
--
ALTER TABLE `contracheques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `controle_ata`
--
ALTER TABLE `controle_ata`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`);

--
-- Índices para tabela `controle_dvr`
--
ALTER TABLE `controle_dvr`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `controle_faciais`
--
ALTER TABLE `controle_faciais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `controle_ips`
--
ALTER TABLE `controle_ips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `base_id` (`base_id`);

--
-- Índices para tabela `controle_pop_dependentes`
--
ALTER TABLE `controle_pop_dependentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pop_id` (`pop_id`),
  ADD KEY `edificio_id` (`edificio_id`);

--
-- Índices para tabela `controle_radio_fibra`
--
ALTER TABLE `controle_radio_fibra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`),
  ADD KEY `fk_crf_base` (`base_id`),
  ADD KEY `fk_pop_responsavel` (`pop_responsavel_id`);

--
-- Índices para tabela `controle_ramais`
--
ALTER TABLE `controle_ramais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`),
  ADD KEY `base_id` (`base_id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `edificios`
--
ALTER TABLE `edificios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `base_id` (`base_id`),
  ADD KEY `sindico_id` (`sindico_id`);

--
-- Índices para tabela `entregas`
--
ALTER TABLE `entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_entregas_atualizado_por` (`atualizado_por`);

--
-- Índices para tabela `extras`
--
ALTER TABLE `extras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_extras_atualizado_por` (`atualizado_por`);

--
-- Índices para tabela `faciais`
--
ALTER TABLE `faciais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_documento` (`documento`),
  ADD KEY `idx_edificio` (`edificio`);

--
-- Índices para tabela `faltas`
--
ALTER TABLE `faltas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `ferias`
--
ALTER TABLE `ferias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `funcoes`
--
ALTER TABLE `funcoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices para tabela `locacoes`
--
ALTER TABLE `locacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`);

--
-- Índices para tabela `locacoes_inquilinos`
--
ALTER TABLE `locacoes_inquilinos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `locacao_id` (`locacao_id`);

--
-- Índices para tabela `locacoes_veiculos`
--
ALTER TABLE `locacoes_veiculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `locacao_id` (`locacao_id`);

--
-- Índices para tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `edificio_id` (`edificio_id`),
  ADD KEY `fk_ocorrencias_base` (`base_id`),
  ADD KEY `fk_ocorrencias_atualizado_por` (`atualizado_por`);

--
-- Índices para tabela `ocorrencias_midia`
--
ALTER TABLE `ocorrencias_midia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ocorrencia_id` (`ocorrencia_id`);

--
-- Índices para tabela `prestadores_servico`
--
ALTER TABLE `prestadores_servico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_prestadores_atualizado_por` (`atualizado_por`);

--
-- Índices para tabela `sindicos`
--
ALTER TABLE `sindicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `edificio_id` (`edificio_id`);

--
-- Índices para tabela `situacoes_entrega`
--
ALTER TABLE `situacoes_entrega`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices para tabela `telas_layout`
--
ALTER TABLE `telas_layout`
  ADD PRIMARY KEY (`id`),
  ADD KEY `base_id` (`base_id`),
  ADD KEY `fk_edificio1` (`edificio1_id`),
  ADD KEY `fk_edificio2` (`edificio2_id`),
  ADD KEY `fk_edificio3` (`edificio3_id`);

--
-- Índices para tabela `transportadoras`
--
ALTER TABLE `transportadoras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuarios_funcao` (`funcao_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acoes_disciplinares`
--
ALTER TABLE `acoes_disciplinares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `administradoras`
--
ALTER TABLE `administradoras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `bases`
--
ALTER TABLE `bases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `categorias_ramais`
--
ALTER TABLE `categorias_ramais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de tabela `contracheques`
--
ALTER TABLE `contracheques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `controle_ata`
--
ALTER TABLE `controle_ata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `controle_dvr`
--
ALTER TABLE `controle_dvr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `controle_faciais`
--
ALTER TABLE `controle_faciais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `controle_ips`
--
ALTER TABLE `controle_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `controle_pop_dependentes`
--
ALTER TABLE `controle_pop_dependentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de tabela `controle_radio_fibra`
--
ALTER TABLE `controle_radio_fibra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `controle_ramais`
--
ALTER TABLE `controle_ramais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `edificios`
--
ALTER TABLE `edificios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de tabela `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `extras`
--
ALTER TABLE `extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `faciais`
--
ALTER TABLE `faciais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `faltas`
--
ALTER TABLE `faltas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `ferias`
--
ALTER TABLE `ferias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `funcoes`
--
ALTER TABLE `funcoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `locacoes`
--
ALTER TABLE `locacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `locacoes_inquilinos`
--
ALTER TABLE `locacoes_inquilinos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de tabela `locacoes_veiculos`
--
ALTER TABLE `locacoes_veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `ocorrencias_midia`
--
ALTER TABLE `ocorrencias_midia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `prestadores_servico`
--
ALTER TABLE `prestadores_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `sindicos`
--
ALTER TABLE `sindicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de tabela `situacoes_entrega`
--
ALTER TABLE `situacoes_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `telas_layout`
--
ALTER TABLE `telas_layout`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `transportadoras`
--
ALTER TABLE `transportadoras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `acoes_disciplinares`
--
ALTER TABLE `acoes_disciplinares`
  ADD CONSTRAINT `acoes_disciplinares_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `contracheques`
--
ALTER TABLE `contracheques`
  ADD CONSTRAINT `contracheques_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `controle_ata`
--
ALTER TABLE `controle_ata`
  ADD CONSTRAINT `controle_ata_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `controle_dvr`
--
ALTER TABLE `controle_dvr`
  ADD CONSTRAINT `controle_dvr_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `controle_dvr_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `controle_faciais`
--
ALTER TABLE `controle_faciais`
  ADD CONSTRAINT `controle_faciais_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `controle_faciais_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `controle_ips`
--
ALTER TABLE `controle_ips`
  ADD CONSTRAINT `controle_ips_ibfk_1` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `controle_pop_dependentes`
--
ALTER TABLE `controle_pop_dependentes`
  ADD CONSTRAINT `controle_pop_dependentes_ibfk_1` FOREIGN KEY (`pop_id`) REFERENCES `controle_radio_fibra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `controle_pop_dependentes_ibfk_2` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `controle_radio_fibra`
--
ALTER TABLE `controle_radio_fibra`
  ADD CONSTRAINT `controle_radio_fibra_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crf_base` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pop_responsavel` FOREIGN KEY (`pop_responsavel_id`) REFERENCES `controle_radio_fibra` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `controle_ramais`
--
ALTER TABLE `controle_ramais`
  ADD CONSTRAINT `controle_ramais_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`),
  ADD CONSTRAINT `controle_ramais_ibfk_2` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`),
  ADD CONSTRAINT `controle_ramais_ibfk_3` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_ramais` (`id`),
  ADD CONSTRAINT `controle_ramais_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `edificios`
--
ALTER TABLE `edificios`
  ADD CONSTRAINT `edificios_ibfk_1` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `edificios_ibfk_2` FOREIGN KEY (`sindico_id`) REFERENCES `sindicos` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `entregas`
--
ALTER TABLE `entregas`
  ADD CONSTRAINT `entregas_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entregas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_entregas_atualizado_por` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `extras`
--
ALTER TABLE `extras`
  ADD CONSTRAINT `extras_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_extras_atualizado_por` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `faltas`
--
ALTER TABLE `faltas`
  ADD CONSTRAINT `faltas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `ferias`
--
ALTER TABLE `ferias`
  ADD CONSTRAINT `ferias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `locacoes`
--
ALTER TABLE `locacoes`
  ADD CONSTRAINT `locacoes_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `locacoes_inquilinos`
--
ALTER TABLE `locacoes_inquilinos`
  ADD CONSTRAINT `locacoes_inquilinos_ibfk_1` FOREIGN KEY (`locacao_id`) REFERENCES `locacoes` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `locacoes_veiculos`
--
ALTER TABLE `locacoes_veiculos`
  ADD CONSTRAINT `locacoes_veiculos_ibfk_1` FOREIGN KEY (`locacao_id`) REFERENCES `locacoes` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD CONSTRAINT `fk_ocorrencias_atualizado_por` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ocorrencias_base` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`),
  ADD CONSTRAINT `ocorrencias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ocorrencias_ibfk_2` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ocorrencias_ibfk_3` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`);

--
-- Limitadores para a tabela `ocorrencias_midia`
--
ALTER TABLE `ocorrencias_midia`
  ADD CONSTRAINT `ocorrencias_midia_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `prestadores_servico`
--
ALTER TABLE `prestadores_servico`
  ADD CONSTRAINT `fk_prestadores_atualizado_por` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prestadores_servico_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prestadores_servico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `sindicos`
--
ALTER TABLE `sindicos`
  ADD CONSTRAINT `sindicos_ibfk_1` FOREIGN KEY (`edificio_id`) REFERENCES `edificios` (`id`);

--
-- Limitadores para a tabela `telas_layout`
--
ALTER TABLE `telas_layout`
  ADD CONSTRAINT `fk_edificio1` FOREIGN KEY (`edificio1_id`) REFERENCES `edificios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_edificio2` FOREIGN KEY (`edificio2_id`) REFERENCES `edificios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_edificio3` FOREIGN KEY (`edificio3_id`) REFERENCES `edificios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `telas_layout_ibfk_1` FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_funcao` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
