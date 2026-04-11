-- Create disposition history audit table
-- Run this SQL on both local and cloud databases

CREATE TABLE IF NOT EXISTS `disposition_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_created` (`campaignnumber_id`,`created_at`),
  KEY `idx_company_campaign` (`company_id`,`campaign_id`),
  KEY `idx_changed_by` (`changed_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
