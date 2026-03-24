-- Create missing agentgroup table
-- Run this SQL on both local and cloud databases

CREATE TABLE IF NOT EXISTS `agentgroup` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `grpname` VARCHAR(255) NOT NULL,
  `company_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_company_id` (`company_id`),
  UNIQUE KEY `unique_grpname_company` (`grpname`, `company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default sample groups (optional - adjust as needed)
-- INSERT INTO agentgroup (grpname, company_id) VALUES 
-- ('Default Group', NULL),
-- ('Sales Team', NULL),
-- ('Support Team', NULL);
