-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 04, 2018 at 07:45 AM
-- Server version: 5.6.38
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacy_disputedoc`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_history`
--

CREATE TABLE `account_history` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) UNSIGNED NOT NULL,
  `credit_bureau` varchar(65) NOT NULL,
  `account` varchar(65) NOT NULL,
  `account_type` varchar(65) NOT NULL,
  `account_type_detail` varchar(65) NOT NULL,
  `bureau_code` varchar(16) NOT NULL,
  `account_status` varchar(16) NOT NULL,
  `monthly_payment` varchar(10) NOT NULL,
  `date_opened` date NOT NULL,
  `balance` varchar(10) NOT NULL,
  `no_of_months_terms` int(10) NOT NULL,
  `high_credit` varchar(16) NOT NULL,
  `credit_limit` varchar(16) NOT NULL,
  `past_due` varchar(16) NOT NULL,
  `payment_status` varchar(16) NOT NULL,
  `last_reported` date NOT NULL,
  `comments` varchar(250) NOT NULL,
  `date_last_active` date NOT NULL,
  `date_of_last_payment` date NOT NULL,
  `fi_id` int(11) NOT NULL COMMENT 'Financial Institution Id'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cct_creditcard_other_debt_bureau_info`
--

CREATE TABLE `cct_creditcard_other_debt_bureau_info` (
  `id` int(10) UNSIGNED NOT NULL,
  `doc_id` int(11) NOT NULL,
  `master_id` int(11) NOT NULL,
  `bureau` varchar(25) NOT NULL,
  `account_name` varchar(75) NOT NULL,
  `account` varchar(75) NOT NULL,
  `account_type` varchar(75) NOT NULL,
  `balance` varchar(25) NOT NULL,
  `past_due` varchar(25) NOT NULL,
  `date_opened` date NOT NULL,
  `account_status` varchar(25) NOT NULL,
  `mo_payment` varchar(25) NOT NULL,
  `payment_status` varchar(25) NOT NULL,
  `high_balance` varchar(25) NOT NULL,
  `limits` varchar(25) NOT NULL,
  `terms` varchar(25) NOT NULL,
  `comments` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cct_creditcard_other_debt_master`
--

CREATE TABLE `cct_creditcard_other_debt_master` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) NOT NULL,
  `title` varchar(125) NOT NULL,
  `address` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cct_credit_inquiry_bureau_info`
--

CREATE TABLE `cct_credit_inquiry_bureau_info` (
  `id` int(10) UNSIGNED NOT NULL,
  `doc_id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `business_name` varchar(125) NOT NULL,
  `inquiry_date` date NOT NULL,
  `business_type` varchar(125) NOT NULL,
  `bureau` varchar(65) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cct_credit_inquiry_master`
--

CREATE TABLE `cct_credit_inquiry_master` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) NOT NULL,
  `title` varchar(65) NOT NULL,
  `address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cct_fico_credit_scores`
--

CREATE TABLE `cct_fico_credit_scores` (
  `id` int(10) UNSIGNED NOT NULL,
  `doc_id` int(11) NOT NULL,
  `title` varchar(125) NOT NULL,
  `experian` int(11) NOT NULL,
  `equifax` int(11) NOT NULL,
  `transunion` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cct_personal_information`
--

CREATE TABLE `cct_personal_information` (
  `id` int(11) NOT NULL,
  `doc_id` int(11) NOT NULL,
  `bureau` varchar(35) NOT NULL,
  `name` varchar(75) NOT NULL,
  `year_of_birth` year(4) NOT NULL,
  `addresses` text NOT NULL,
  `current_employer` varchar(35) NOT NULL,
  `previous_employer` varchar(35) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cct_report_summary`
--

CREATE TABLE `cct_report_summary` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) NOT NULL,
  `type` varchar(35) NOT NULL,
  `bureau` varchar(35) NOT NULL,
  `count` varchar(25) NOT NULL,
  `balance` varchar(25) NOT NULL,
  `current` varchar(25) NOT NULL,
  `delinquent` varchar(25) NOT NULL,
  `other` varchar(25) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `creditors_contact`
--

CREATE TABLE `creditors_contact` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) UNSIGNED NOT NULL,
  `creditor_name` varchar(125) NOT NULL,
  `address` text NOT NULL,
  `phone_number` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `credit_scores`
--

CREATE TABLE `credit_scores` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) UNSIGNED NOT NULL,
  `bureau` varchar(65) NOT NULL,
  `credit_score` int(11) NOT NULL,
  `lender_rank` varchar(15) NOT NULL,
  `score_scale` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `financial_institutions`
--

CREATE TABLE `financial_institutions` (
  `id` int(11) UNSIGNED NOT NULL,
  `institution` varchar(125) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `personal_information`
--

CREATE TABLE `personal_information` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) UNSIGNED NOT NULL,
  `credit_report_date` date NOT NULL,
  `name` varchar(75) NOT NULL,
  `also_known_as` varchar(75) NOT NULL,
  `former` varchar(75) NOT NULL,
  `date_of_birth` date NOT NULL,
  `current_addresses` varchar(255) NOT NULL,
  `previous_addresses` varchar(255) NOT NULL,
  `employers` varchar(255) NOT NULL,
  `bureau` varchar(24) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `public_record_information`
--

CREATE TABLE `public_record_information` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) UNSIGNED NOT NULL,
  `type` varchar(65) NOT NULL,
  `status` varchar(65) NOT NULL,
  `date_filedreported` date NOT NULL,
  `reference` varchar(65) NOT NULL,
  `closing_date` date NOT NULL,
  `asset_amount` varchar(15) NOT NULL,
  `court` varchar(125) NOT NULL,
  `liability` varchar(65) NOT NULL,
  `exempt_amount` varchar(15) NOT NULL,
  `remarks` varchar(125) NOT NULL,
  `closing_satisfied` varchar(125) NOT NULL,
  `action_amount` varchar(15) NOT NULL,
  `plaintiff` varchar(125) NOT NULL,
  `amount` varchar(15) NOT NULL,
  `released_date` date NOT NULL,
  `garnishee` varchar(125) NOT NULL,
  `name_of_spouse` varchar(65) NOT NULL,
  `information` text NOT NULL,
  `industry_type` varchar(65) NOT NULL,
  `date_deferred` date NOT NULL,
  `liability_amount` varchar(15) NOT NULL,
  `credit_bureau` varchar(65) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `public_record_types`
--

CREATE TABLE `public_record_types` (
  `id` int(11) UNSIGNED NOT NULL,
  `record_type` varchar(65) NOT NULL,
  `created` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `report_documents`
--

CREATE TABLE `report_documents` (
  `id` int(11) NOT NULL,
  `document_name` varchar(125) NOT NULL,
  `created` datetime NOT NULL,
  `deleted` enum('Y','N') NOT NULL DEFAULT 'N',
  `user_id` int(11) NOT NULL,
  `file_name` varchar(125) NOT NULL,
  `status` enum('Y','N') NOT NULL DEFAULT 'N',
  `report_date` date NOT NULL,
  `reference_number` varchar(16) NOT NULL,
  `report_type` int(11) NOT NULL,
  `created_for` varchar(75) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `report_types`
--

CREATE TABLE `report_types` (
  `id` int(11) NOT NULL,
  `type` varchar(65) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `web` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `report_types`
--

INSERT INTO `report_types` (`id`, `type`, `full_name`, `active`, `web`) VALUES
(1600, 'identity-iq', 'Identity IQ', 'Y', 'https://www.identityiq.com'),
(1601, 'credit-check-total', 'Credit Check Total', 'Y', 'http://creditchecktotal.com/');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(40) NOT NULL,
  `password` varchar(80) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `status` enum('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `import` tinyint(1) NOT NULL DEFAULT '0',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `alt_phone_number` varchar(15) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime DEFAULT NULL,
  `passcode` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_history`
--
ALTER TABLE `account_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doc_id` (`doc_id`),
  ADD KEY `fi_id` (`fi_id`);

--
-- Indexes for table `cct_creditcard_other_debt_bureau_info`
--
ALTER TABLE `cct_creditcard_other_debt_bureau_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cct_creditcard_other_debt_master`
--
ALTER TABLE `cct_creditcard_other_debt_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cct_credit_inquiry_bureau_info`
--
ALTER TABLE `cct_credit_inquiry_bureau_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inquiry_id` (`inquiry_id`);

--
-- Indexes for table `cct_credit_inquiry_master`
--
ALTER TABLE `cct_credit_inquiry_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cct_fico_credit_scores`
--
ALTER TABLE `cct_fico_credit_scores`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cct_personal_information`
--
ALTER TABLE `cct_personal_information`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cct_report_summary`
--
ALTER TABLE `cct_report_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `creditors_contact`
--
ALTER TABLE `creditors_contact`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doc_id` (`doc_id`);

--
-- Indexes for table `credit_scores`
--
ALTER TABLE `credit_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doc_id` (`doc_id`);

--
-- Indexes for table `financial_institutions`
--
ALTER TABLE `financial_institutions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_information`
--
ALTER TABLE `personal_information`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doc_id` (`doc_id`);

--
-- Indexes for table `public_record_information`
--
ALTER TABLE `public_record_information`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doc_id` (`doc_id`);

--
-- Indexes for table `public_record_types`
--
ALTER TABLE `public_record_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `report_documents`
--
ALTER TABLE `report_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `report_types`
--
ALTER TABLE `report_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD KEY `status` (`status`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_history`
--
ALTER TABLE `account_history`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=541;
--
-- AUTO_INCREMENT for table `cct_creditcard_other_debt_bureau_info`
--
ALTER TABLE `cct_creditcard_other_debt_bureau_info`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;
--
-- AUTO_INCREMENT for table `cct_creditcard_other_debt_master`
--
ALTER TABLE `cct_creditcard_other_debt_master`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;
--
-- AUTO_INCREMENT for table `cct_credit_inquiry_bureau_info`
--
ALTER TABLE `cct_credit_inquiry_bureau_info`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;
--
-- AUTO_INCREMENT for table `cct_credit_inquiry_master`
--
ALTER TABLE `cct_credit_inquiry_master`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=318;
--
-- AUTO_INCREMENT for table `cct_fico_credit_scores`
--
ALTER TABLE `cct_fico_credit_scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `cct_personal_information`
--
ALTER TABLE `cct_personal_information`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;
--
-- AUTO_INCREMENT for table `cct_report_summary`
--
ALTER TABLE `cct_report_summary`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;
--
-- AUTO_INCREMENT for table `creditors_contact`
--
ALTER TABLE `creditors_contact`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=372;
--
-- AUTO_INCREMENT for table `credit_scores`
--
ALTER TABLE `credit_scores`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;
--
-- AUTO_INCREMENT for table `financial_institutions`
--
ALTER TABLE `financial_institutions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `personal_information`
--
ALTER TABLE `personal_information`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;
--
-- AUTO_INCREMENT for table `public_record_information`
--
ALTER TABLE `public_record_information`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
--
-- AUTO_INCREMENT for table `public_record_types`
--
ALTER TABLE `public_record_types`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
--
-- AUTO_INCREMENT for table `report_documents`
--
ALTER TABLE `report_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
