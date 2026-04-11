-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 08, 2026 at 03:33 PM
-- Server version: 5.7.24
-- PHP Version: 7.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `addon3cx`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_phone_locks`
--

CREATE TABLE `active_phone_locks` (
  `company_id` bigint(20) NOT NULL,
  `phone_key` varchar(32) NOT NULL,
  `source_phone` varchar(64) NOT NULL,
  `lead_id` bigint(20) NOT NULL,
  `campaign_id` bigint(20) NOT NULL,
  `lock_token` char(36) NOT NULL,
  `locked_by` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `agent`
--

CREATE TABLE `agent` (
  `agent_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `agent_name` varchar(255) DEFAULT NULL,
  `agent_ext` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `agent_group` varchar(255) DEFAULT NULL,
  `agent_grpid` int(11) DEFAULT '0',
  `3cx_id` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `agentgroup`
--

CREATE TABLE `agentgroup` (
  `id` int(11) NOT NULL,
  `grpname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calllog`
--

CREATE TABLE `calllog` (
  `callid` varchar(100) NOT NULL,
  `agent` varchar(10) NOT NULL,
  `caller` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `callstart` datetime NOT NULL,
  `callduration` time NOT NULL,
  `fromno` varchar(50) NOT NULL,
  `tono` varchar(50) NOT NULL,
  `recordurl` text NOT NULL,
  `insertime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `campaign`
--

CREATE TABLE `campaign` (
  `id` int(32) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `routeto` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dn_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dg_reception_number` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `returncall` tinyint(1) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Stop',
  `statusupdate` datetime DEFAULT NULL,
  `active` int(13) NOT NULL DEFAULT '0',
  `weekdays` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `starttime` time DEFAULT NULL,
  `stoptime` time DEFAULT NULL,
  `insertime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `dialer_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Power Dialer',
  `route_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Queue',
  `concurrent_calls` int(11) DEFAULT '1',
  `webhook_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_status_audit`
--

CREATE TABLE `campaign_status_audit` (
  `id` bigint(20) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `campaign_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by_user_id` int(11) DEFAULT NULL,
  `changed_by_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by_role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forwarded_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `disposition_history` (
  `id` bigint(20) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `campaignnumber_id` bigint(20) NOT NULL,
  `phone_e164` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'updated',
  `previous_disposition` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_disposition` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_notes` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_notes` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by_user_id` int(11) DEFAULT NULL,
  `changed_by_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by_role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forwarded_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaignnumbers`
--

CREATE TABLE `campaignnumbers` (
  `id` bigint(20) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaignid` int(11) NOT NULL,
  `phone_e164` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_raw` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exdata` json DEFAULT NULL,
  `state` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_DIALED',
  `priority` tinyint(4) NOT NULL DEFAULT '5',
  `next_call_at` datetime DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lock_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempts_used` int(11) NOT NULL DEFAULT '0',
  `max_attempts` int(11) NOT NULL DEFAULT '3',
  `attempts_today` int(11) NOT NULL DEFAULT '0',
  `last_attempt_at` datetime DEFAULT NULL,
  `last_call_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_disposition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disposition_required` tinyint(1) NOT NULL DEFAULT '0',
  `agent_connected` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_call_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_caller_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_call_started_at` datetime DEFAULT NULL,
  `last_call_ended_at` datetime DEFAULT NULL,
  `last_call_duration_sec` int(11) DEFAULT NULL,
  `is_dnc` tinyint(1) NOT NULL DEFAULT '0',
  `invalid_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT '0',
  `updated_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaignnumbers_backup`
--

CREATE TABLE `campaignnumbers_backup` (
  `id` int(11) NOT NULL,
  `campaignid` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `exdata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `calltry1status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calltry2status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calltry3status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calltry1` tinyint(1) DEFAULT '0',
  `calltry2` tinyint(1) DEFAULT '0',
  `calltry3` tinyint(1) DEFAULT '0',
  `calltry1dt` datetime DEFAULT NULL,
  `calltry2dt` datetime DEFAULT NULL,
  `calltry3dt` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT '0',
  `updated_by` int(11) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `inserttime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `agent_connected` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `call_end_dt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_did_map`
--

CREATE TABLE `campaign_did_map` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `did_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_outbound_rule`
--

CREATE TABLE `campaign_outbound_rule` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `outbound_rule_id` int(11) NOT NULL,
  `last_used_map_id` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_prefix_usage`
--

CREATE TABLE `campaign_prefix_usage` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `prefix` varchar(50) NOT NULL,
  `usage_count` int(11) DEFAULT '0',
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_skipped_numbers`
--

CREATE TABLE `campaign_skipped_numbers` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaignid` int(11) NOT NULL,
  `number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `exdata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `inserttime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dialer_call_log`
--

CREATE TABLE `dialer_call_log` (
  `id` bigint(20) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `campaignnumber_id` bigint(20) NOT NULL,
  `attempt_no` int(11) NOT NULL,
  `call_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caller_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `call_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disposition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `duration_sec` int(11) DEFAULT NULL,
  `recording_url` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dialer_disposition_master`
--

CREATE TABLE `dialer_disposition_master` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `color_code` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#808080'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dialer_queue_status`
--

CREATE TABLE `dialer_queue_status` (
  `id` bigint(20) NOT NULL,
  `company_id` bigint(20) NOT NULL DEFAULT '0',
  `pbx_id` bigint(20) NOT NULL DEFAULT '0',
  `queue_dn` varchar(20) NOT NULL,
  `available_agents` int(11) NOT NULL DEFAULT '0',
  `loggedin_numlist_raw` text,
  `loggedin_extlist_raw` text,
  `raw_querystring` text,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `importnum`
--

CREATE TABLE `importnum` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `importfilename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tempname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `import_by` int(11) NOT NULL,
  `import_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pbxdetail`
--

CREATE TABLE `pbxdetail` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `inbound_prefix` enum('Yes','No') DEFAULT 'No',
  `pbxurl` varchar(255) DEFAULT NULL,
  `pbxloginid` varchar(100) DEFAULT NULL COMMENT 'Extension Number',
  `pbxloginpass` varchar(255) DEFAULT NULL,
  `pbxclientid` varchar(255) DEFAULT NULL,
  `pbxsecret` varchar(255) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `auth_token` text,
  `auth_updated_at` timestamp NULL DEFAULT NULL,
  `outbound_prefix` enum('Yes','No') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rating_questions_count` int(11) NOT NULL DEFAULT '0',
  `enable_sentiment` tinyint(1) DEFAULT '0',
  `enable_rating_recording` tinyint(1) DEFAULT '0',
  `simultaneous_calls` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pbx_dids`
--

CREATE TABLE `pbx_dids` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `inbound_rule_id` int(11) NOT NULL,
  `did` varchar(64) NOT NULL,
  `trunk` varchar(255) DEFAULT NULL,
  `rule_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rate`
--

CREATE TABLE `rate` (
  `rid` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `callid` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agentid` int(11) DEFAULT NULL,
  `agentno` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `queue` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callerno` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ratings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` longtext COLLATE utf8mb4_unicode_ci,
  `sentiment` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transcript` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `recording_url` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rating_questions`
--

CREATE TABLE `rating_questions` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `question_number` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_calls`
--

CREATE TABLE `scheduled_calls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `campaignnumber_id` int(11) NOT NULL,
  `route_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Agent',
  `queue_dn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `agent_ext` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_for` datetime NOT NULL,
  `timezone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_agent',
  `source_module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dialednumbers',
  `disposition_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note_text` text COLLATE utf8mb4_unicode_ci,
  `meta_json` longtext COLLATE utf8mb4_unicode_ci,
  `attempt_count` int(11) NOT NULL DEFAULT '0',
  `last_attempt_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '3CX User ID',
  `userno` varchar(50) DEFAULT NULL COMMENT 'Extension Number',
  `user_email` varchar(255) DEFAULT NULL,
  `user_type` varchar(50) NOT NULL COMMENT 'manager, user, etc.',
  `password_hash` varchar(255) DEFAULT NULL COMMENT 'For session/login',
  `auth_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `manager_agent_mode` varchar(20) DEFAULT NULL,
  `managed_agent_ids` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_phone_locks`
--
ALTER TABLE `active_phone_locks`
  ADD PRIMARY KEY (`company_id`,`phone_key`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_lock_token` (`lock_token`);

--
-- Indexes for table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`agent_id`),
  ADD UNIQUE KEY `uniq_company_ext` (`company_id`,`agent_ext`),
  ADD KEY `idx_3cx_id` (`3cx_id`),
  ADD KEY `idx_company_id` (`company_id`);

--
-- Indexes for table `agentgroup`
--
ALTER TABLE `agentgroup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grpname_company` (`grpname`,`company_id`),
  ADD KEY `idx_company_id` (`company_id`);

--
-- Indexes for table `calllog`
--
ALTER TABLE `calllog`
  ADD KEY `callid` (`callid`);

--
-- Indexes for table `campaign`
--
ALTER TABLE `campaign`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `idx_webhook_token` (`webhook_token`);

--
-- Indexes for table `campaign_status_audit`
--
ALTER TABLE `campaign_status_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_campaign_created` (`company_id`,`campaign_id`,`created_at`),
  ADD KEY `idx_changed_by_user` (`changed_by_user_id`);

--
-- Indexes for table `disposition_history`
--
ALTER TABLE `disposition_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_created` (`campaignnumber_id`,`created_at`),
  ADD KEY `idx_company_campaign` (`company_id`,`campaign_id`),
  ADD KEY `idx_changed_by` (`changed_by_user_id`);

--
-- Indexes for table `campaignnumbers`
--
ALTER TABLE `campaignnumbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_campaign_phone` (`company_id`,`campaignid`,`phone_e164`),
  ADD KEY `idx_pickup` (`company_id`,`campaignid`,`state`,`next_call_at`,`priority`),
  ADD KEY `idx_state` (`company_id`,`campaignid`,`state`),
  ADD KEY `idx_dispo_pending` (`company_id`,`campaignid`,`disposition_required`),
  ADD KEY `idx_lock` (`company_id`,`campaignid`,`locked_at`,`locked_by`);

--
-- Indexes for table `campaignnumbers_backup`
--
ALTER TABLE `campaignnumbers_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaignid` (`campaignid`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `campaign_did_map`
--
ALTER TABLE `campaign_did_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_campaign_did` (`campaign_id`,`did_id`),
  ADD KEY `idx_company_campaign` (`company_id`,`campaign_id`);

--
-- Indexes for table `campaign_outbound_rule`
--
ALTER TABLE `campaign_outbound_rule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_campaign` (`company_id`,`campaign_id`);

--
-- Indexes for table `campaign_prefix_usage`
--
ALTER TABLE `campaign_prefix_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `company_id` (`company_id`,`campaign_id`);

--
-- Indexes for table `campaign_skipped_numbers`
--
ALTER TABLE `campaign_skipped_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaignid` (`campaignid`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dialer_call_log`
--
ALTER TABLE `dialer_call_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lookup` (`company_id`,`campaign_id`,`campaignnumber_id`,`started_at`),
  ADD KEY `idx_callid` (`company_id`,`call_id`),
  ADD KEY `fk_dialer_call_log_cn` (`campaignnumber_id`);

--
-- Indexes for table `dialer_disposition_master`
--
ALTER TABLE `dialer_disposition_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dialer_queue_status`
--
ALTER TABLE `dialer_queue_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_queue` (`company_id`,`pbx_id`,`queue_dn`);

--
-- Indexes for table `importnum`
--
ALTER TABLE `importnum`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `pbxdetail`
--
ALTER TABLE `pbxdetail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_company_settings` (`company_id`);

--
-- Indexes for table `pbx_dids`
--
ALTER TABLE `pbx_dids`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_inbound_rule` (`company_id`,`inbound_rule_id`),
  ADD KEY `idx_company_did` (`company_id`,`did`);

--
-- Indexes for table `rate`
--
ALTER TABLE `rate`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `callid` (`callid`);

--
-- Indexes for table `rating_questions`
--
ALTER TABLE `rating_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token` (`webhook_token`),
  ADD KEY `company_qa` (`company_id`,`question_number`);

--
-- Indexes for table `scheduled_calls`
--
ALTER TABLE `scheduled_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_status_time` (`company_id`,`status`,`scheduled_for`),
  ADD KEY `idx_campaignnumber` (`campaignnumber_id`),
  ADD KEY `idx_agent_schedule` (`agent_id`,`scheduled_for`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_company_user` (`company_id`,`user_id`),
  ADD UNIQUE KEY `unique_company_userno` (`company_id`,`userno`),
  ADD UNIQUE KEY `unique_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agent`
--
ALTER TABLE `agent`
  MODIFY `agent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agentgroup`
--
ALTER TABLE `agentgroup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign`
--
ALTER TABLE `campaign`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_status_audit`
--
ALTER TABLE `campaign_status_audit`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disposition_history`
--
ALTER TABLE `disposition_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaignnumbers`
--
ALTER TABLE `campaignnumbers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaignnumbers_backup`
--
ALTER TABLE `campaignnumbers_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_did_map`
--
ALTER TABLE `campaign_did_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_outbound_rule`
--
ALTER TABLE `campaign_outbound_rule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_prefix_usage`
--
ALTER TABLE `campaign_prefix_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_skipped_numbers`
--
ALTER TABLE `campaign_skipped_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dialer_call_log`
--
ALTER TABLE `dialer_call_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dialer_disposition_master`
--
ALTER TABLE `dialer_disposition_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dialer_queue_status`
--
ALTER TABLE `dialer_queue_status`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `importnum`
--
ALTER TABLE `importnum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pbxdetail`
--
ALTER TABLE `pbxdetail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pbx_dids`
--
ALTER TABLE `pbx_dids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rate`
--
ALTER TABLE `rate`
  MODIFY `rid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rating_questions`
--
ALTER TABLE `rating_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheduled_calls`
--
ALTER TABLE `scheduled_calls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaign_prefix_usage`
--
ALTER TABLE `campaign_prefix_usage`
  ADD CONSTRAINT `campaign_prefix_usage_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_prefix_usage_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `campaign` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dialer_call_log`
--
ALTER TABLE `dialer_call_log`
  ADD CONSTRAINT `fk_dialer_call_log_cn` FOREIGN KEY (`campaignnumber_id`) REFERENCES `campaignnumbers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pbxdetail`
--
ALTER TABLE `pbxdetail`
  ADD CONSTRAINT `pbxdetail_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
