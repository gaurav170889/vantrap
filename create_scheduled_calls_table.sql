-- Create scheduled_calls table for user-scheduled callbacks
-- Run this SQL on both local and cloud databases
-- Zoho-specific fields were intentionally removed for this project

CREATE TABLE IF NOT EXISTS `scheduled_calls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `campaign_id` INT NOT NULL,
  `campaignnumber_id` INT NOT NULL,
  `route_type` VARCHAR(20) NOT NULL DEFAULT 'Agent',
  `queue_dn` VARCHAR(50) DEFAULT NULL,
  `agent_id` INT DEFAULT NULL,
  `agent_ext` VARCHAR(50) DEFAULT NULL,
  `scheduled_for` DATETIME NOT NULL,
  `timezone` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending_agent',
  `source_module` VARCHAR(50) NOT NULL DEFAULT 'dialednumbers',
  `disposition_label` VARCHAR(100) DEFAULT NULL,
  `note_text` TEXT DEFAULT NULL,
  `meta_json` LONGTEXT DEFAULT NULL,
  `attempt_count` INT NOT NULL DEFAULT 0,
  `last_attempt_at` DATETIME DEFAULT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_status_time` (`company_id`, `status`, `scheduled_for`),
  KEY `idx_campaignnumber` (`campaignnumber_id`),
  KEY `idx_agent_schedule` (`agent_id`, `scheduled_for`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Safe cleanup for older versions that still contain Zoho fields
SET @drop_zoho_schedule_id = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'scheduled_calls'
        AND COLUMN_NAME = 'zoho_schedule_id'
    ),
    'ALTER TABLE `scheduled_calls` DROP COLUMN `zoho_schedule_id`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @drop_zoho_schedule_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_zoho_activity_id = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'scheduled_calls'
        AND COLUMN_NAME = 'zoho_activity_id'
    ),
    'ALTER TABLE `scheduled_calls` DROP COLUMN `zoho_activity_id`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @drop_zoho_activity_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_zoho_payload = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'scheduled_calls'
        AND COLUMN_NAME = 'zoho_payload'
    ),
    'ALTER TABLE `scheduled_calls` DROP COLUMN `zoho_payload`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @drop_zoho_payload;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
