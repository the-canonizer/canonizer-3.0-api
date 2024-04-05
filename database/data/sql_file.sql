-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 15, 2022 at 12:36 PM
-- Server version: 8.0.28-0ubuntu0.20.04.3
-- PHP Version: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `canonizer_3.0`
--

-- --------------------------------------------------------

DROP TABLE IF EXISTS `countries_test`;

--
-- Table structure for table `countries_test`
--

CREATE TABLE `countries_test` (
  `id` bigint UNSIGNED NOT NULL,
  `phone_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alpha_3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '0 => Inactive, 1 => Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries_test`
--

INSERT INTO `countries_test` (`id`, `phone_code`, `country_code`, `name`, `alpha_3`, `created_at`, `status`) VALUES
(1, '+93', 'AF', 'Afghanistan', 'AFG', '2022-02-23 12:06:40', 1),
(2, '+358', 'AX', 'Aland Islands', 'ALA', '2022-02-23 12:06:40', 1),
(3, '+355', 'AL', 'Albania', 'ALB', '2022-02-23 12:06:40', 1),
(4, '+213', 'DZ', 'Algeria', 'DZA', '2022-02-23 12:06:40', 1),
(5, '+1684', 'AS', 'American Samoa', 'ASM', '2022-02-23 12:06:40', 1),
(6, '+376', 'AD', 'Andorra', 'AND', '2022-02-23 12:06:40', 1),
(7, '+244', 'AO', 'Angola', 'AGO', '2022-02-23 12:06:40', 1),
(8, '+1264', 'AI', 'Anguilla', 'AIA', '2022-02-23 12:06:40', 1),
(9, '+672', 'AQ', 'Antarctica', 'ATA', '2022-02-23 12:06:40', 1),
(10, '+1268', 'AG', 'Antigua and Barbuda', 'ATG', '2022-02-23 12:06:40', 1),
(11, '+54', 'AR', 'Argentina', 'ARG', '2022-02-23 12:06:40', 1),
(12, '+374', 'AM', 'Armenia', 'ARM', '2022-02-23 12:06:40', 1),
(13, '+297', 'AW', 'Aruba', 'ABW', '2022-02-23 12:06:40', 1),
(14, '+61', 'AU', 'Australia', 'AUS', '2022-02-23 12:06:40', 1),
(15, '+43', 'AT', 'Austria', 'AUT', '2022-02-23 12:06:40', 1),
(16, '+994', 'AZ', 'Azerbaijan', 'AZE', '2022-02-23 12:06:40', 1),
(17, '+1242', 'BS', 'Bahamas', 'BHS', '2022-02-23 12:06:40', 1),
(18, '+973', 'BH', 'Bahrain', 'BHR', '2022-02-23 12:06:40', 1),
(19, '+880', 'BD', 'Bangladesh', 'BGD', '2022-02-23 12:06:40', 1),
(20, '+1246', 'BB', 'Barbados', 'BRB', '2022-02-23 12:06:40', 1),
(21, '+375', 'BY', 'Belarus', 'BLR', '2022-02-23 12:06:41', 1),
(22, '+32', 'BE', 'Belgium', 'BEL', '2022-02-23 12:06:41', 1),
(23, '+501', 'BZ', 'Belize', 'BLZ', '2022-02-23 12:06:41', 1),
(24, '+229', 'BJ', 'Benin', 'BEN', '2022-02-23 12:06:41', 1),
(25, '+1441', 'BM', 'Bermuda', 'BMU', '2022-02-23 12:06:41', 1),
(26, '+975', 'BT', 'Bhutan', 'BTN', '2022-02-23 12:06:41', 1),
(27, '+591', 'BO', 'Bolivia', 'BOL', '2022-02-23 12:06:41', 1),
(28, '+599', 'BQ', 'Bonaire, Sint Eustatius and Saba', 'BES', '2022-02-23 12:06:41', 1),
(29, '+387', 'BA', 'Bosnia and Herzegovina', 'BIH', '2022-02-23 12:06:41', 1),
(30, '+267', 'BW', 'Botswana', 'BWA', '2022-02-23 12:06:41', 1),
(31, '+55', 'BV', 'Bouvet Island', 'BVT', '2022-02-23 12:06:41', 1),
(32, '+55', 'BR', 'Brazil', 'BRA', '2022-02-23 12:06:41', 1),
(33, '+246', 'IO', 'British Indian Ocean Territory', 'IOT', '2022-02-23 12:06:41', 1),
(34, '+673', 'BN', 'Brunei Darussalam', 'BRN', '2022-02-23 12:06:41', 1),
(35, '+359', 'BG', 'Bulgaria', 'BGR', '2022-02-23 12:06:41', 1),
(36, '+226', 'BF', 'Burkina Faso', 'BFA', '2022-02-23 12:06:41', 1),
(37, '+257', 'BI', 'Burundi', 'BDI', '2022-02-23 12:06:41', 1),
(38, '+855', 'KH', 'Cambodia', 'KHM', '2022-02-23 12:06:41', 1),
(39, '+237', 'CM', 'Cameroon', 'CMR', '2022-02-23 12:06:41', 1),
(40, '+1', 'CA', 'Canada', 'CAN', '2022-02-23 12:06:41', 1),
(41, '+238', 'CV', 'Cape Verde', 'CPV', '2022-02-23 12:06:41', 1),
(42, '+1345', 'KY', 'Cayman Islands', 'CYM', '2022-02-23 12:06:41', 1),
(43, '+236', 'CF', 'Central African Republic', 'CAF', '2022-02-23 12:06:41', 1),
(44, '+235', 'TD', 'Chad', 'TCD', '2022-02-23 12:06:41', 1),
(45, '+56', 'CL', 'Chile', 'CHL', '2022-02-23 12:06:41', 1),
(46, '+86', 'CN', 'China', 'CHN', '2022-02-23 12:06:41', 1),
(47, '+61', 'CX', 'Christmas Island', 'CXR', '2022-02-23 12:06:41', 1),
(48, '+672', 'CC', 'Cocos (Keeling) Islands', 'CCK', '2022-02-23 12:06:41', 1),
(49, '+57', 'CO', 'Colombia', 'COL', '2022-02-23 12:06:41', 1),
(50, '+269', 'KM', 'Comoros', 'COM', '2022-02-23 12:06:41', 1),
(51, '+242', 'CG', 'Congo', 'COG', '2022-02-23 12:06:41', 1),
(52, '+242', 'CD', 'Congo, Democratic Republic of the Congo', 'COD', '2022-02-23 12:06:41', 1),
(53, '+682', 'CK', 'Cook Islands', 'COK', '2022-02-23 12:06:41', 1),
(54, '+506', 'CR', 'Costa Rica', 'CRI', '2022-02-23 12:06:41', 1),
(55, '+225', 'CI', 'Cote D\'Ivoire', 'CIV', '2022-02-23 12:06:41', 1),
(56, '+385', 'HR', 'Croatia', 'HRV', '2022-02-23 12:06:41', 1),
(57, '+53', 'CU', 'Cuba', 'CUB', '2022-02-23 12:06:41', 1),
(58, '+599', 'CW', 'Curacao', 'CUW', '2022-02-23 12:06:41', 1),
(59, '+357', 'CY', 'Cyprus', 'CYP', '2022-02-23 12:06:41', 1),
(60, '+420', 'CZ', 'Czech Republic', 'CZE', '2022-02-23 12:06:41', 1),
(61, '+45', 'DK', 'Denmark', 'DNK', '2022-02-23 12:06:41', 1),
(62, '+253', 'DJ', 'Djibouti', 'DJI', '2022-02-23 12:06:41', 1),
(63, '+1767', 'DM', 'Dominica', 'DMA', '2022-02-23 12:06:41', 1),
(64, '+1809', 'DO', 'Dominican Republic', 'DOM', '2022-02-23 12:06:41', 1),
(65, '+593', 'EC', 'Ecuador', 'ECU', '2022-02-23 12:06:41', 1),
(66, '+20', 'EG', 'Egypt', 'EGY', '2022-02-23 12:06:41', 1),
(67, '+503', 'SV', 'El Salvador', 'SLV', '2022-02-23 12:06:41', 1),
(68, '+240', 'GQ', 'Equatorial Guinea', 'GNQ', '2022-02-23 12:06:41', 1),
(69, '+291', 'ER', 'Eritrea', 'ERI', '2022-02-23 12:06:41', 1),
(70, '+372', 'EE', 'Estonia', 'EST', '2022-02-23 12:06:41', 1),
(71, '+251', 'ET', 'Ethiopia', 'ETH', '2022-02-23 12:06:41', 1),
(72, '+500', 'FK', 'Falkland Islands (Malvinas)', 'FLK', '2022-02-23 12:06:41', 1),
(73, '+298', 'FO', 'Faroe Islands', 'FRO', '2022-02-23 12:06:41', 1),
(74, '+679', 'FJ', 'Fiji', 'FJI', '2022-02-23 12:06:41', 1),
(75, '+358', 'FI', 'Finland', 'FIN', '2022-02-23 12:06:41', 1),
(76, '+33', 'FR', 'France', 'FRA', '2022-02-23 12:06:41', 1),
(77, '+594', 'GF', 'French Guiana', 'GUF', '2022-02-23 12:06:41', 1),
(78, '+689', 'PF', 'French Polynesia', 'PYF', '2022-02-23 12:06:41', 1),
(79, '+262', 'TF', 'French Southern Territories', 'ATF', '2022-02-23 12:06:41', 1),
(80, '+241', 'GA', 'Gabon', 'GAB', '2022-02-23 12:06:41', 1),
(81, '+220', 'GM', 'Gambia', 'GMB', '2022-02-23 12:06:41', 1),
(82, '+995', 'GE', 'Georgia', 'GEO', '2022-02-23 12:06:41', 1),
(83, '+49', 'DE', 'Germany', 'DEU', '2022-02-23 12:06:41', 1),
(84, '+233', 'GH', 'Ghana', 'GHA', '2022-02-23 12:06:41', 1),
(85, '+350', 'GI', 'Gibraltar', 'GIB', '2022-02-23 12:06:41', 1),
(86, '+30', 'GR', 'Greece', 'GRC', '2022-02-23 12:06:41', 1),
(87, '+299', 'GL', 'Greenland', 'GRL', '2022-02-23 12:06:41', 1),
(88, '+1473', 'GD', 'Grenada', 'GRD', '2022-02-23 12:06:41', 1),
(89, '+590', 'GP', 'Guadeloupe', 'GLP', '2022-02-23 12:06:41', 1),
(90, '+1671', 'GU', 'Guam', 'GUM', '2022-02-23 12:06:41', 1),
(91, '+502', 'GT', 'Guatemala', 'GTM', '2022-02-23 12:06:41', 1),
(92, '+44', 'GG', 'Guernsey', 'GGY', '2022-02-23 12:06:41', 1),
(93, '+224', 'GN', 'Guinea', 'GIN', '2022-02-23 12:06:41', 1),
(94, '+245', 'GW', 'Guinea-Bissau', 'GNB', '2022-02-23 12:06:41', 1),
(95, '+592', 'GY', 'Guyana', 'GUY', '2022-02-23 12:06:41', 1),
(96, '+509', 'HT', 'Haiti', 'HTI', '2022-02-23 12:06:41', 1),
(97, '+0', 'HM', 'Heard Island and Mcdonald Islands', 'HMD', '2022-02-23 12:06:41', 1),
(98, '+39', 'VA', 'Holy See (Vatican City State)', 'VAT', '2022-02-23 12:06:41', 1),
(99, '+504', 'HN', 'Honduras', 'HND', '2022-02-23 12:06:41', 1),
(100, '+852', 'HK', 'Hong Kong', 'HKG', '2022-02-23 12:06:41', 1),
(101, '+36', 'HU', 'Hungary', 'HUN', '2022-02-23 12:06:41', 1),
(102, '+354', 'IS', 'Iceland', 'ISL', '2022-02-23 12:06:41', 1),
(103, '+91', 'IN', 'India', 'IND', '2022-02-23 12:06:41', 1),
(104, '+62', 'ID', 'Indonesia', 'IDN', '2022-02-23 12:06:41', 1),
(105, '+98', 'IR', 'Iran, Islamic Republic of', 'IRN', '2022-02-23 12:06:41', 1),
(106, '+964', 'IQ', 'Iraq', 'IRQ', '2022-02-23 12:06:41', 1),
(107, '+353', 'IE', 'Ireland', 'IRL', '2022-02-23 12:06:41', 1),
(108, '+44', 'IM', 'Isle of Man', 'IMN', '2022-02-23 12:06:41', 1),
(109, '+972', 'IL', 'Israel', 'ISR', '2022-02-23 12:06:41', 1),
(110, '+39', 'IT', 'Italy', 'ITA', '2022-02-23 12:06:41', 1),
(111, '+1876', 'JM', 'Jamaica', 'JAM', '2022-02-23 12:06:41', 1),
(112, '+81', 'JP', 'Japan', 'JPN', '2022-02-23 12:06:41', 1),
(113, '+44', 'JE', 'Jersey', 'JEY', '2022-02-23 12:06:41', 1),
(114, '+962', 'JO', 'Jordan', 'JOR', '2022-02-23 12:06:41', 1),
(115, '+7', 'KZ', 'Kazakhstan', 'KAZ', '2022-02-23 12:06:41', 1),
(116, '+254', 'KE', 'Kenya', 'KEN', '2022-02-23 12:06:41', 1),
(117, '+686', 'KI', 'Kiribati', 'KIR', '2022-02-23 12:06:41', 1),
(118, '+850', 'KP', 'Korea, Democratic People\'s Republic of', 'PRK', '2022-02-23 12:06:41', 1),
(119, '+82', 'KR', 'Korea, Republic of', 'KOR', '2022-02-23 12:06:41', 1),
(120, '+381', 'XK', 'Kosovo', 'XKX', '2022-02-23 12:06:41', 1),
(121, '+965', 'KW', 'Kuwait', 'KWT', '2022-02-23 12:06:41', 1),
(122, '+996', 'KG', 'Kyrgyzstan', 'KGZ', '2022-02-23 12:06:41', 1),
(123, '+856', 'LA', 'Lao People\'s Democratic Republic', 'LAO', '2022-02-23 12:06:41', 1),
(124, '+371', 'LV', 'Latvia', 'LVA', '2022-02-23 12:06:41', 1),
(125, '+961', 'LB', 'Lebanon', 'LBN', '2022-02-23 12:06:41', 1),
(126, '+266', 'LS', 'Lesotho', 'LSO', '2022-02-23 12:06:41', 1),
(127, '+231', 'LR', 'Liberia', 'LBR', '2022-02-23 12:06:41', 1),
(128, '+218', 'LY', 'Libyan Arab Jamahiriya', 'LBY', '2022-02-23 12:06:41', 1),
(129, '+423', 'LI', 'Liechtenstein', 'LIE', '2022-02-23 12:06:41', 1),
(130, '+370', 'LT', 'Lithuania', 'LTU', '2022-02-23 12:06:41', 1),
(131, '+352', 'LU', 'Luxembourg', 'LUX', '2022-02-23 12:06:41', 1),
(132, '+853', 'MO', 'Macao', 'MAC', '2022-02-23 12:06:41', 1),
(133, '+389', 'MK', 'Macedonia, the Former Yugoslav Republic of', 'MKD', '2022-02-23 12:06:41', 1),
(134, '+261', 'MG', 'Madagascar', 'MDG', '2022-02-23 12:06:41', 1),
(135, '+265', 'MW', 'Malawi', 'MWI', '2022-02-23 12:06:41', 1),
(136, '+60', 'MY', 'Malaysia', 'MYS', '2022-02-23 12:06:41', 1),
(137, '+960', 'MV', 'Maldives', 'MDV', '2022-02-23 12:06:41', 1),
(138, '+223', 'ML', 'Mali', 'MLI', '2022-02-23 12:06:41', 1),
(139, '+356', 'MT', 'Malta', 'MLT', '2022-02-23 12:06:41', 1),
(140, '+692', 'MH', 'Marshall Islands', 'MHL', '2022-02-23 12:06:41', 1),
(141, '+596', 'MQ', 'Martinique', 'MTQ', '2022-02-23 12:06:41', 1),
(142, '+222', 'MR', 'Mauritania', 'MRT', '2022-02-23 12:06:41', 1),
(143, '+230', 'MU', 'Mauritius', 'MUS', '2022-02-23 12:06:41', 1),
(144, '+269', 'YT', 'Mayotte', 'MYT', '2022-02-23 12:06:41', 1),
(145, '+52', 'MX', 'Mexico', 'MEX', '2022-02-23 12:06:41', 1),
(146, '+691', 'FM', 'Micronesia, Federated States of', 'FSM', '2022-02-23 12:06:41', 1),
(147, '+373', 'MD', 'Moldova, Republic of', 'MDA', '2022-02-23 12:06:41', 1),
(148, '+377', 'MC', 'Monaco', 'MCO', '2022-02-23 12:06:41', 1),
(149, '+976', 'MN', 'Mongolia', 'MNG', '2022-02-23 12:06:41', 1),
(150, '+382', 'ME', 'Montenegro', 'MNE', '2022-02-23 12:06:41', 1),
(151, '+1664', 'MS', 'Montserrat', 'MSR', '2022-02-23 12:06:41', 1),
(152, '+212', 'MA', 'Morocco', 'MAR', '2022-02-23 12:06:41', 1),
(153, '+258', 'MZ', 'Mozambique', 'MOZ', '2022-02-23 12:06:41', 1),
(154, '+95', 'MM', 'Myanmar', 'MMR', '2022-02-23 12:06:41', 1),
(155, '+264', 'NA', 'Namibia', 'NAM', '2022-02-23 12:06:41', 1),
(156, '+674', 'NR', 'Nauru', 'NRU', '2022-02-23 12:06:41', 1),
(157, '+977', 'NP', 'Nepal', 'NPL', '2022-02-23 12:06:41', 1),
(158, '+31', 'NL', 'Netherlands', 'NLD', '2022-02-23 12:06:41', 1),
(159, '+599', 'AN', 'Netherlands Antilles', 'ANT', '2022-02-23 12:06:41', 1),
(160, '+687', 'NC', 'New Caledonia', 'NCL', '2022-02-23 12:06:41', 1),
(161, '+64', 'NZ', 'New Zealand', 'NZL', '2022-02-23 12:06:41', 1),
(162, '+505', 'NI', 'Nicaragua', 'NIC', '2022-02-23 12:06:41', 1),
(163, '+227', 'NE', 'Niger', 'NER', '2022-02-23 12:06:41', 1),
(164, '+234', 'NG', 'Nigeria', 'NGA', '2022-02-23 12:06:41', 1),
(165, '+683', 'NU', 'Niue', 'NIU', '2022-02-23 12:06:41', 1),
(166, '+672', 'NF', 'Norfolk Island', 'NFK', '2022-02-23 12:06:41', 1),
(167, '+1670', 'MP', 'Northern Mariana Islands', 'MNP', '2022-02-23 12:06:41', 1),
(168, '+47', 'NO', 'Norway', 'NOR', '2022-02-23 12:06:41', 1),
(169, '+968', 'OM', 'Oman', 'OMN', '2022-02-23 12:06:41', 1),
(170, '+92', 'PK', 'Pakistan', 'PAK', '2022-02-23 12:06:41', 1),
(171, '+680', 'PW', 'Palau', 'PLW', '2022-02-23 12:06:41', 1),
(172, '+970', 'PS', 'Palestinian Territory, Occupied', 'PSE', '2022-02-23 12:06:41', 1),
(173, '+507', 'PA', 'Panama', 'PAN', '2022-02-23 12:06:41', 1),
(174, '+675', 'PG', 'Papua New Guinea', 'PNG', '2022-02-23 12:06:41', 1),
(175, '+595', 'PY', 'Paraguay', 'PRY', '2022-02-23 12:06:41', 1),
(176, '+51', 'PE', 'Peru', 'PER', '2022-02-23 12:06:41', 1),
(177, '+63', 'PH', 'Philippines', 'PHL', '2022-02-23 12:06:41', 1),
(178, '+64', 'PN', 'Pitcairn', 'PCN', '2022-02-23 12:06:41', 1),
(179, '+48', 'PL', 'Poland', 'POL', '2022-02-23 12:06:41', 1),
(180, '+351', 'PT', 'Portugal', 'PRT', '2022-02-23 12:06:41', 1),
(181, '+1787', 'PR', 'Puerto Rico', 'PRI', '2022-02-23 12:06:41', 1),
(182, '+974', 'QA', 'Qatar', 'QAT', '2022-02-23 12:06:41', 1),
(183, '+262', 'RE', 'Reunion', 'REU', '2022-02-23 12:06:41', 1),
(184, '+40', 'RO', 'Romania', 'ROM', '2022-02-23 12:06:41', 1),
(185, '+70', 'RU', 'Russian Federation', 'RUS', '2022-02-23 12:06:41', 1),
(186, '+250', 'RW', 'Rwanda', 'RWA', '2022-02-23 12:06:41', 1),
(187, '+590', 'BL', 'Saint Barthelemy', 'BLM', '2022-02-23 12:06:41', 1),
(188, '+290', 'SH', 'Saint Helena', 'SHN', '2022-02-23 12:06:41', 1),
(189, '+1869', 'KN', 'Saint Kitts and Nevis', 'KNA', '2022-02-23 12:06:41', 1),
(190, '+1758', 'LC', 'Saint Lucia', 'LCA', '2022-02-23 12:06:41', 1),
(191, '+590', 'MF', 'Saint Martin', 'MAF', '2022-02-23 12:06:41', 1),
(192, '+508', 'PM', 'Saint Pierre and Miquelon', 'SPM', '2022-02-23 12:06:41', 1),
(193, '+1784', 'VC', 'Saint Vincent and the Grenadines', 'VCT', '2022-02-23 12:06:41', 1),
(194, '+684', 'WS', 'Samoa', 'WSM', '2022-02-23 12:06:41', 1),
(195, '+378', 'SM', 'San Marino', 'SMR', '2022-02-23 12:06:41', 1),
(196, '+239', 'ST', 'Sao Tome and Principe', 'STP', '2022-02-23 12:06:41', 1),
(197, '+966', 'SA', 'Saudi Arabia', 'SAU', '2022-02-23 12:06:41', 1),
(198, '+221', 'SN', 'Senegal', 'SEN', '2022-02-23 12:06:41', 1),
(199, '+381', 'RS', 'Serbia', 'SRB', '2022-02-23 12:06:41', 1),
(200, '+381', 'CS', 'Serbia and Montenegro', 'SCG', '2022-02-23 12:06:41', 1),
(201, '+248', 'SC', 'Seychelles', 'SYC', '2022-02-23 12:06:41', 1),
(202, '+232', 'SL', 'Sierra Leone', 'SLE', '2022-02-23 12:06:41', 1),
(203, '+65', 'SG', 'Singapore', 'SGP', '2022-02-23 12:06:41', 1),
(204, '+1', 'SX', 'Sint Maarten', 'SXM', '2022-02-23 12:06:41', 1),
(205, '+421', 'SK', 'Slovakia', 'SVK', '2022-02-23 12:06:41', 1),
(206, '+386', 'SI', 'Slovenia', 'SVN', '2022-02-23 12:06:41', 1),
(207, '+677', 'SB', 'Solomon Islands', 'SLB', '2022-02-23 12:06:41', 1),
(208, '+252', 'SO', 'Somalia', 'SOM', '2022-02-23 12:06:41', 1),
(209, '+27', 'ZA', 'South Africa', 'ZAF', '2022-02-23 12:06:41', 1),
(210, '+500', 'GS', 'South Georgia and the South Sandwich Islands', 'SGS', '2022-02-23 12:06:41', 1),
(211, '+211', 'SS', 'South Sudan', 'SSD', '2022-02-23 12:06:41', 1),
(212, '+34', 'ES', 'Spain', 'ESP', '2022-02-23 12:06:41', 1),
(213, '+94', 'LK', 'Sri Lanka', 'LKA', '2022-02-23 12:06:41', 1),
(214, '+249', 'SD', 'Sudan', 'SDN', '2022-02-23 12:06:41', 1),
(215, '+597', 'SR', 'Suriname', 'SUR', '2022-02-23 12:06:41', 1),
(216, '+47', 'SJ', 'Svalbard and Jan Mayen', 'SJM', '2022-02-23 12:06:41', 1),
(217, '+268', 'SZ', 'Swaziland', 'SWZ', '2022-02-23 12:06:41', 1),
(218, '+46', 'SE', 'Sweden', 'SWE', '2022-02-23 12:06:41', 1),
(219, '+41', 'CH', 'Switzerland', 'CHE', '2022-02-23 12:06:41', 1),
(220, '+963', 'SY', 'Syrian Arab Republic', 'SYR', '2022-02-23 12:06:41', 1),
(221, '+886', 'TW', 'Taiwan, Province of China', 'TWN', '2022-02-23 12:06:41', 1),
(222, '+992', 'TJ', 'Tajikistan', 'TJK', '2022-02-23 12:06:41', 1),
(223, '+255', 'TZ', 'Tanzania, United Republic of', 'TZA', '2022-02-23 12:06:41', 1),
(224, '+66', 'TH', 'Thailand', 'THA', '2022-02-23 12:06:41', 1),
(225, '+670', 'TL', 'Timor-Leste', 'TLS', '2022-02-23 12:06:41', 1),
(226, '+228', 'TG', 'Togo', 'TGO', '2022-02-23 12:06:41', 1),
(227, '+690', 'TK', 'Tokelau', 'TKL', '2022-02-23 12:06:41', 1),
(228, '+676', 'TO', 'Tonga', 'TON', '2022-02-23 12:06:42', 1),
(229, '+1868', 'TT', 'Trinidad and Tobago', 'TTO', '2022-02-23 12:06:42', 1),
(230, '+216', 'TN', 'Tunisia', 'TUN', '2022-02-23 12:06:42', 1),
(231, '+90', 'TR', 'Turkey', 'TUR', '2022-02-23 12:06:42', 1),
(232, '+7370', 'TM', 'Turkmenistan', 'TKM', '2022-02-23 12:06:42', 1),
(233, '+1649', 'TC', 'Turks and Caicos Islands', 'TCA', '2022-02-23 12:06:42', 1),
(234, '+688', 'TV', 'Tuvalu', 'TUV', '2022-02-23 12:06:42', 1),
(235, '+256', 'UG', 'Uganda', 'UGA', '2022-02-23 12:06:42', 1),
(236, '+380', 'UA', 'Ukraine', 'UKR', '2022-02-23 12:06:42', 1),
(237, '+971', 'AE', 'United Arab Emirates', 'ARE', '2022-02-23 12:06:42', 1),
(238, '+44', 'GB', 'United Kingdom', 'GBR', '2022-02-23 12:06:42', 1),
(239, '+1', 'US', 'United States', 'USA', '2022-02-23 12:06:42', 1),
(240, '+1', 'UM', 'United States Minor Outlying Islands', 'UMI', '2022-02-23 12:06:42', 1),
(241, '+598', 'UY', 'Uruguay', 'URY', '2022-02-23 12:06:42', 1),
(242, '+998', 'UZ', 'Uzbekistan', 'UZB', '2022-02-23 12:06:42', 1),
(243, '+678', 'VU', 'Vanuatu', 'VUT', '2022-02-23 12:06:42', 1),
(244, '+58', 'VE', 'Venezuela', 'VEN', '2022-02-23 12:06:42', 1),
(245, '+84', 'VN', 'Viet Nam', 'VNM', '2022-02-23 12:06:42', 1),
(246, '+1284', 'VG', 'Virgin Islands, British', 'VGB', '2022-02-23 12:06:42', 1),
(247, '+1340', 'VI', 'Virgin Islands, U.s.', 'VIR', '2022-02-23 12:06:42', 1),
(248, '+681', 'WF', 'Wallis and Futuna', 'WLF', '2022-02-23 12:06:42', 1),
(249, '+212', 'EH', 'Western Sahara', 'ESH', '2022-02-23 12:06:42', 1),
(250, '+967', 'YE', 'Yemen', 'YEM', '2022-02-23 12:06:42', 1),
(251, '+260', 'ZM', 'Zambia', 'ZMB', '2022-02-23 12:06:42', 1),
(252, '+263', 'ZW', 'Zimbabwe', 'ZWE', '2022-02-23 12:06:42', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `countries_test`
--
ALTER TABLE `countries_test`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `countries_test`
--
ALTER TABLE `countries_test`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
