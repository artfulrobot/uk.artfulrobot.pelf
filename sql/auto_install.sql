DROP TABLE IF EXISTS `civicrm_pelffunding`;
-- /*******************************************************
-- *
-- * civicrm_pelffunding
-- *
-- * Holds details of how funding is split between financial years, and possibly projects.
-- *
-- *******************************************************/
CREATE TABLE `civicrm_pelffunding` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique PelfFunding ID',
     `activity_id` int unsigned    COMMENT 'FK to Activity',
     `amount` decimal(20,2) NOT NULL   COMMENT 'Amount',
     `financial_year` text NOT NULL   COMMENT 'Financial year, like 2017-2018',
     `project_contact_id` int unsigned    COMMENT 'Optional FK to Contact ID - requires Project extension',
     `note` text    COMMENT 'Note and/or Comment.' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_pelffunding_activity_id FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_pelffunding_project_contact_id FOREIGN KEY (`project_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL  
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

