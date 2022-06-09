-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2022 at 02:21 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yenosdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(60) NOT NULL,
  `level` enum('admin','user') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `name`, `username`, `password`, `level`) VALUES
(1, 'Administrator', 'admin', '$2y$10$YLU.SGqYuJzmFcD22olzNOFPtjb0R2jIibIxuuwwUtx2OZZhfaFRO', 'admin'),
(2, 'Filly Willy', 'filly', '$2y$10$SB0S3c/lfGBUUpj/EZlwQOZZzJNhsUvZuTeR2g4POaO.zlxS3wZqy', 'user'),
(3, 'Helly Filly', 'helly', '$2y$10$TFV2mUdWErolav9i0zf7COfvJh5FdG24vqPbTFtvb4WDKjPRvBYEW', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_account` int(10) UNSIGNED NOT NULL,
  `id_category` int(5) UNSIGNED NOT NULL,
  `title` varchar(300) NOT NULL,
  `cover` varchar(300) NOT NULL,
  `description` text NOT NULL,
  `datetime_added` datetime NOT NULL DEFAULT current_timestamp(),
  `datetime_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','non-active') NOT NULL DEFAULT 'non-active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `id_account`, `id_category`, `title`, `cover`, `description`, `datetime_added`, `datetime_updated`, `status`) VALUES
(1, 2, 1, 'Sejarah! Timnas Basket Indonesia Sabet Emas SEA Games 2021', 'https://akcdn.detik.net.id/community/media/visual/2022/05/22/sea-games-2021-timnas-basket-indonesia-lawan-vietnam.jpeg?w=700&q=90', 'Timnas basket Indonesia mencatatkan sejarah. Pertama kalinya akhirnya raih medali emas di SEA Games 2021 setelah kalahkan Filipina!\nTimnas basket Indonesia hadapi favorit juara Filipina di laga final SEA Games 2021, Minggu (22/5). Di luar dugaan, tim Merah Putih menang!\nPertandingan berjalan ketat sejak quarter pertama dan kedua. Di quarter ketiga, skor terus ketat 60-60.\nDi akhir laga, timnas basket Indonesia terus jaga jarak. Kemenangan dan medali emas di raih dengan skor 85-81!\nHasil itu jadi torehan bersejarah buat timnas basket Indonesia untuk pertama kali raih emas di ajang SEA Games. Sebelumnya, baru empat medali perak dan tiga perunggu yang diraih.\nSementara Filipina sudah punya 12 medali emas SEA Games di cabang olahraga tersebut.\n', '2022-06-07 13:48:38', '2022-06-07 13:48:38', 'non-active'),
(2, 1, 1, 'Karena Apriyani Kuatkan Siti Untuk Sabet Emas SEA Games 2021', 'https://akcdn.detik.net.id/community/media/visual/2022/05/22/apriyanisiti-1.jpeg?w=700&q=90', 'Apriyani Rahayu/Siti Fadia Silva Ramadhanti riah emas di SEA Games 2021. Bagi Siti, sosok Apriyani selalu memberi kekuatan dan keyakinan untuk terus berjuang!\nDari cabor bulutangkis per orangan nomor ganda putri, Apriyani Rahayu/Siti Fadia Silva Ramadhanti hadapi wakil Thailand di laga final. Laga digelar di Bac Giang Gymnasium, Vietnam, Minggu (22/5).\nApriyani/Siti, duet baru ini meraih kemenangan dua gim langsung. Mereka menang 21-14 dan 21-17.\nRaihan medali emas Indonesia pun bertambah di SEA Games 2021. Bagi Apriyani Rahayu, kesuksesan dirinya dan Siti tak lepas dari doa masyarakat Indonesia!\n\"Mengucap syukur kepada Allah SWT, kita diberikan kemenangan, diberikan juara tanpa ada cedera. Terima kasih untuk semua doa rakyat Indonesia untuk kita,\" katanya dalam keterangan dari PBSI.\nSiti Fadia Silva Ramadhanti ungkap salah satu kunci kesuksesannya. Baginya, pengalaman dan arahan dari Apriyani yang sudah bersaing di level tertinggi jadi motivasinya.\n\"Pertama-tama grogi, tapi balik ke diri sendiri bahwa saya bisa, percaya diri. Kak Apri selalu yakinkan saya, itu yang buat saya yakin,\" ungkapnya.\n', '2022-06-07 15:15:06', '2022-06-07 15:25:24', 'non-active'),
(3, 1, 1, 'Final Thailand Open 2022: Fajar/Rian Mundur, Hoki/Kobayashi Juara', 'https://akcdn.detik.net.id/community/media/visual/2022/05/12/fajar-alfianmuhammad-rian-ardianto_169.jpeg?w=700&q=90', 'Fajar Alfian/Muhammad Rian Ardianto tak mampu menyelesaikan laga final Thailand Open 2022. Alhasil Takuro Hoki/Yugo Kobayashi tampil sebagai juara.\nFinal Thailand Open 2022 berlangsung di Impact Arena, Pak Kret, Minggu (22/5/2022) sore WIB. Fajar Alfian/Muhammad Rian Ardianto menjadi satu-satunya wakil Indonesia di partai puncak.\nNamun pertandingan harus selesai dengan cepat. Baru sembilan menit berjalan, Fajar/Rian memutuskan menghentikan permainan dan mundur dalam skor 4-13.\nKeputusan ini diduga harus diambil karena Fajar Alfian mengalami cedera. Takuro Hoki/Yugo Kobayashi pun memastikan diri menjadi juara.\nDi sektor lainnya, ganda Jepang Nami Matsuyama/Chiharu Shida memenangi sektor ganda putri usai mengalahkan rekan senegara, Mayu Matsumoto/Wakana Nagahara. Sementara di sektor tunggal, Lee Zii Jia jadi juara di nomor putra usai mengalahkan Li Shi Feng dan Tai Tzu Ying memenangi nomor putri setelah mengalahkan Chen Yu Fei.\nPartai final Thailand Open 2022 masih mempertandingkan sektor ganda campuran. Dechapol Puavaranukroh/Sapsiree Taerattanachai melawan Zheng Si Wei/Huang Ya Qiong untuk memperebutkan gelar juara.\n', '2022-06-07 15:16:02', '2022-06-07 15:16:02', 'non-active'),
(5, 3, 1, 'Tim Menembak Indonesia Juara Umum SEA Games 2021, Apa Rahasianya?', 'https://akcdn.detik.net.id/community/media/visual/2022/05/22/atlet-menembak-1.jpeg?w=700&q=90', 'Tim Menembak Indonesia jadi salah satu cabang olahraga yang sukses raup banyak medali emas di SEA Games 2021. Apa sih, rahasianya?\nStatus tersebut disandang Merah Putih usai menambah raihan dua medali emas, satu perak, satu perunggu pada hari terakhir pertandingan. Emas disumbangkan Anang Yulianto (25 meter standar pistol putra) dan Muhammad Sejahtera Dwi Putra (10 meter running target putra).\nPerak diberikan trio M Chuwai Zam/M Sejathera Dwi Putra/Irfandi Julio yang turun di nomor 10m running target team putra. Sementara perunggu dipersembahkan Muhammad Chuwai Zam di nomor inidividu 10m running target putra.\nPersembahan mereka membuat Tim Menembak Indonesia mendapatkan 8 emas, 6 perak, dan 2 perunggu. Jumlah tersebut melampaui pencapaian di SEA Games 2019.\nManajer Tim Menembak Indonesia, Arh Candy Christian Riantori sangat puas dengan hasil ini. Ia merasa persiapan matang yang telah disusun tidak sia-sia.\n\"Sebetulnya hasil ini tidak diraih semudah membalik telapak tangan. Kami menjalankan program latihan yang ketat dan keras sejak proses seleksi,\" kata Candy dalam keterangan dari NOC Indonesia.\n\"Kemudian pelatnasnya juga kami kombinasikan dengan mengikuti kejuaraan-kejuaraan dunia dan regional. Terakhir ke Budapest, Hungaria yang notabene seperti uji nyali karena kiblat cabor menembak memang di sana.\"\n\"Alhamdulillah dari apa yang sudah kita laksanakan, atlet-atlet kita memiliki pengalaman bertanding yang memadai dan dalam event ini skornya meningkat. Padahal yang paling kita khawatirkan saat bertanding skornya turun dari latihan,\" tambahnya.\nMeski begitu, Candy tak mau puas dengan raihan ini. Tim menembak Indonesia langsung membidik target lain yang lebih tinggi.\nMeraih tiket ke Olimpiade 2024 menjadi prioritas utama. Pada edisi 2020, cabor menembak hanya mengirim satu wakil yaitu Vidya Rafika.\n\"Kami sudah ada skema menuju Olimpiade Paris, salah satunya mengikuti kejuaraan dunia. Namun demikian semua peluang terbuka untuk siapa pun, tergantung bagaimana progres mereka,\" terang Candy.\nTim Indonesia yang dipimpin Chef de Mission (CdM) untuk SEA Games Vietnam Ferry Kono berkekuatan 499 atlet serta 214 official yang berpartisipasi di 32 cabor. Tim Indonesia ini juga mendapat dukungan dari official patners, seperti Wall\'s dan Li-Ning, serta official media patner Merah Putih Media.\n', '2022-06-08 09:27:16', '2022-06-08 10:44:06', 'non-active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `article_details`
-- (See below for the actual view)
--
CREATE TABLE `article_details` (
`id_article` int(10) unsigned
,`title` varchar(300)
,`cover` varchar(300)
,`content` text
,`status` enum('active','non-active')
,`added_at` datetime
,`updated_at` datetime
,`id_author` int(10) unsigned
,`author` varchar(255)
,`author_username` varchar(50)
,`id_category` int(5) unsigned
,`category` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(4, 'Astronomy'),
(2, 'Entertainment'),
(1, 'Sport');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_account` int(10) UNSIGNED NOT NULL,
  `id_article` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `id_account`, `id_article`, `content`) VALUES
(1, 3, 1, 'Wah, Timnas Basket Indonesia keren !!!');

-- --------------------------------------------------------

--
-- Structure for view `article_details`
--
DROP TABLE IF EXISTS `article_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `article_details`  AS SELECT `a`.`id` AS `id_article`, `a`.`title` AS `title`, `a`.`cover` AS `cover`, `a`.`description` AS `content`, `a`.`status` AS `status`, `a`.`datetime_added` AS `added_at`, `a`.`datetime_updated` AS `updated_at`, `acc`.`id` AS `id_author`, `acc`.`name` AS `author`, `acc`.`username` AS `author_username`, `a`.`id_category` AS `id_category`, `c`.`name` AS `category` FROM ((`articles` `a` join `accounts` `acc` on(`acc`.`id` = `a`.`id_account`)) join `categories` `c` on(`c`.`id` = `a`.`id_category`))  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_account_articles` (`id_account`),
  ADD KEY `FK_id_category_articles` (`id_category`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_account_comments` (`id_account`),
  ADD KEY `FK_id_article_comments` (`id_article`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `FK_id_account_articles` FOREIGN KEY (`id_account`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `FK_id_category_articles` FOREIGN KEY (`id_category`) REFERENCES `categories` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `FK_id_account_comments` FOREIGN KEY (`id_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_id_article_comments` FOREIGN KEY (`id_article`) REFERENCES `articles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
