CREATE TABLE IF NOT EXISTS `budget_set_expenses` (
  `budget_set_expenses_id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_pay_code` int(11) NOT NULL,
  `budget_set_expenses_code` int(11) NOT NULL,
  `budget_set_expenses_name` varchar(255) NOT NULL,
  `budget_set_expenses_detail` varchar(255) DEFAULT NULL,
  `budget_set_expenses_status` tinyint(1) NOT NULL DEFAULT 1,
  `budget_set_expenses_date_create` datetime DEFAULT NULL,
  `budget_set_expenses_staff_create` varchar(20) DEFAULT NULL,
  `budget_set_expenses_date_modify` datetime DEFAULT NULL,
  `budget_set_expenses_staff_modify` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`budget_set_expenses_id`),
  UNIQUE KEY `uk_budget_set_expenses_code` (`budget_set_expenses_code`),
  KEY `idx_budget_pay_code` (`budget_pay_code`),
  KEY `idx_budget_set_expenses_status` (`budget_set_expenses_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
