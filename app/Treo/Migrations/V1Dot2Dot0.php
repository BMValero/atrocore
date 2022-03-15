<?php
/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

namespace Treo\Migrations;

use Treo\Console\Cron;

/**
 * Migration for version 1.2.0
 */
class V1Dot2Dot0 extends V1Dot1Dot23
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->execute("DROP TABLE email");
        $this->execute("DROP TABLE email_account");
        $this->execute("DROP TABLE email_email_account");
        $this->execute("DROP TABLE email_email_address");
        $this->execute("DROP TABLE email_filter");
        $this->execute("DROP TABLE email_folder");
        $this->execute("DROP TABLE email_inbound_email");
        $this->execute("DROP TABLE email_template");
        $this->execute("DROP TABLE email_template_category");
        $this->execute("DROP TABLE email_user");
        $this->execute("DROP TABLE entity_user");
        $this->execute("DROP TABLE inbound_email");
        $this->execute("DROP TABLE inbound_email_team");
        $this->execute("DROP TABLE integration");
        $this->execute("DROP TABLE template");
        $this->execute("DROP TABLE external_account");
        $this->execute("DROP TABLE lead_capture");
        $this->execute("DROP TABLE lead_capture_log_record");
        $this->execute("DROP TABLE reminder");
        $this->execute("DROP TABLE unique_id");
        $this->execute("ALTER TABLE `notification` DROP email_is_processed");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->execute("CREATE TABLE `email_account` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `email_address` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `status` VARCHAR(255) DEFAULT 'Active' COLLATE utf8mb4_unicode_ci, `host` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `port` VARCHAR(255) DEFAULT '143' COLLATE utf8mb4_unicode_ci, `ssl` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `username` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `password` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `monitored_folders` VARCHAR(255) DEFAULT 'INBOX' COLLATE utf8mb4_unicode_ci, `sent_folder` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `store_sent_emails` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `keep_fetched_emails_unread` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `fetch_since` DATE DEFAULT NULL COLLATE utf8mb4_unicode_ci, `fetch_data` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `use_imap` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `use_smtp` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `smtp_host` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `smtp_port` INT DEFAULT '25' COLLATE utf8mb4_unicode_ci, `smtp_auth` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `smtp_security` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `smtp_username` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `smtp_password` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `email_folder_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `assigned_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_EMAIL_FOLDER_ID` (email_folder_id), INDEX `IDX_ASSIGNED_USER_ID` (assigned_user_id), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_filter` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `from` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `to` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `subject` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `body_contains` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `is_global` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `action` VARCHAR(255) DEFAULT 'Skip' COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `parent_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `parent_type` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `email_folder_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_PARENT` (parent_id, parent_type), INDEX `IDX_EMAIL_FOLDER_ID` (email_folder_id), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_folder` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `order` INT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `skip_notifications` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `assigned_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_ASSIGNED_USER_ID` (assigned_user_id), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `from_name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `from_string` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `reply_to_string` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `address_name_map` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `is_replied` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `message_id` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `message_id_internal` VARCHAR(300) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `body_plain` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `body` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `is_html` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `status` VARCHAR(255) DEFAULT 'Archived' COLLATE utf8mb4_unicode_ci, `has_attachment` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `date_sent` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `delivery_date` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `is_system` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `from_email_address_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `parent_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `parent_type` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `sent_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `assigned_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `replied_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_MESSAGE_ID` (message_id), INDEX `IDX_FROM_EMAIL_ADDRESS_ID` (from_email_address_id), INDEX `IDX_PARENT` (parent_id, parent_type), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_SENT_BY_ID` (sent_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), INDEX `IDX_ASSIGNED_USER_ID` (assigned_user_id), INDEX `IDX_REPLIED_ID` (replied_id), INDEX `IDX_DATE_SENT` (date_sent, deleted), INDEX `IDX_DATE_SENT_STATUS` (date_sent, status, deleted), FULLTEXT INDEX `IDX_SYSTEM_FULL_TEXT_SEARCH` (name, body_plain, body), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_template_category` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `order` INT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `description` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `parent_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), INDEX `IDX_PARENT_ID` (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_template` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `subject` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `body` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `is_html` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `one_off` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `category_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `assigned_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_CATEGORY_ID` (category_id), INDEX `IDX_ASSIGNED_USER_ID` (assigned_user_id), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `inbound_email` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `email_address` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `status` VARCHAR(255) DEFAULT 'Active' COLLATE utf8mb4_unicode_ci, `host` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `port` VARCHAR(255) DEFAULT '143' COLLATE utf8mb4_unicode_ci, `ssl` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `username` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `password` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `monitored_folders` VARCHAR(255) DEFAULT 'INBOX' COLLATE utf8mb4_unicode_ci, `fetch_since` DATE DEFAULT NULL COLLATE utf8mb4_unicode_ci, `fetch_data` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `add_all_team_users` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `sent_folder` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `store_sent_emails` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `use_imap` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `use_smtp` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `smtp_is_shared` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `smtp_is_for_mass_email` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `smtp_host` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `smtp_port` INT DEFAULT '25' COLLATE utf8mb4_unicode_ci, `smtp_auth` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `smtp_security` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `smtp_username` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `smtp_password` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `create_case` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `case_distribution` VARCHAR(255) DEFAULT 'Direct-Assignment' COLLATE utf8mb4_unicode_ci, `target_user_position` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `reply` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `reply_from_address` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `reply_to_address` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `reply_from_name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `from_name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `assign_to_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `team_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `reply_email_template_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_ASSIGN_TO_USER_ID` (assign_to_user_id), INDEX `IDX_TEAM_ID` (team_id), INDEX `IDX_REPLY_EMAIL_TEMPLATE_ID` (reply_email_template_id), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `integration` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `data` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `enabled` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `template` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `body` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `header` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `footer` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `entity_type` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `left_margin` DOUBLE PRECISION DEFAULT '10' COLLATE utf8mb4_unicode_ci, `right_margin` DOUBLE PRECISION DEFAULT '10' COLLATE utf8mb4_unicode_ci, `top_margin` DOUBLE PRECISION DEFAULT '10' COLLATE utf8mb4_unicode_ci, `bottom_margin` DOUBLE PRECISION DEFAULT '25' COLLATE utf8mb4_unicode_ci, `print_footer` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `footer_position` DOUBLE PRECISION DEFAULT '15' COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `page_orientation` VARCHAR(255) DEFAULT 'Portrait' COLLATE utf8mb4_unicode_ci, `page_format` VARCHAR(255) DEFAULT 'A4' COLLATE utf8mb4_unicode_ci, `font_face` VARCHAR(255) DEFAULT '' COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_email_account` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `email_account_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `email_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_32C12DC337D8AD65` (email_account_id), INDEX `IDX_32C12DC3A832C1C9` (email_id), UNIQUE INDEX `UNIQ_32C12DC337D8AD65A832C1C9` (email_account_id, email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_inbound_email` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `email_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `inbound_email_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_41D62720A832C1C9` (email_id), INDEX `IDX_41D62720E540AEA2` (inbound_email_id), UNIQUE INDEX `UNIQ_41D62720A832C1C9E540AEA2` (email_id, inbound_email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_email_address` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `email_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `email_address_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `address_type` VARCHAR(4) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_42B914E6A832C1C9` (email_id), INDEX `IDX_42B914E659045DAA` (email_address_id), UNIQUE INDEX `UNIQ_42B914E6A832C1C959045DAAF19287C2` (email_id, email_address_id, address_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `email_user` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `email_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `is_read` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `is_important` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `in_trash` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `folder_id` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_12A5F6CCA832C1C9` (email_id), INDEX `IDX_12A5F6CCA76ED395` (user_id), UNIQUE INDEX `UNIQ_12A5F6CCA832C1C9A76ED395` (email_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `entity_user` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `entity_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `entity_type` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_C55F6F6281257D5D` (entity_id), INDEX `IDX_C55F6F62A76ED395` (user_id), UNIQUE INDEX `UNIQ_C55F6F6281257D5DA76ED395C412EE02` (entity_id, user_id, entity_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `inbound_email_team` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `inbound_email_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `team_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_D2054DE540AEA2` (inbound_email_id), INDEX `IDX_D2054D296CD8AE` (team_id), UNIQUE INDEX `UNIQ_D2054DE540AEA2296CD8AE` (inbound_email_id, team_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `external_account` (`id` VARCHAR(64) NOT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `data` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `enabled` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `lead_capture` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `is_active` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `subscribe_to_target_list` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `subscribe_contact_to_target_list` TINYINT(1) DEFAULT '1' NOT NULL COLLATE utf8mb4_unicode_ci, `field_list` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci COMMENT 'default={[\"firstName\",\"lastName\",\"emailAddress\"]}', `opt_in_confirmation` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `opt_in_confirmation_lifetime` INT DEFAULT '48' COLLATE utf8mb4_unicode_ci, `opt_in_confirmation_success_message` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `api_key` VARCHAR(36) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `campaign_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `target_list_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `opt_in_confirmation_email_template_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `target_team_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_CAMPAIGN_ID` (campaign_id), INDEX `IDX_TARGET_LIST_ID` (target_list_id), INDEX `IDX_OPT_IN_CONFIRMATION_EMAIL_` (opt_in_confirmation_email_template_id), INDEX `IDX_TARGET_TEAM_ID` (target_team_id), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `lead_capture_log_record` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `number` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `data` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `is_created` TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci, `description` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `lead_capture_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `target_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `target_type` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_LEAD_CAPTURE_ID` (lead_capture_id), INDEX `IDX_TARGET` (target_id, target_type), UNIQUE INDEX `UNIQ_D422237396901F54` (number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `reminder` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `remind_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `start_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `type` VARCHAR(36) DEFAULT 'Popup' COLLATE utf8mb4_unicode_ci, `seconds` INT DEFAULT '0' COLLATE utf8mb4_unicode_ci, `entity_type` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `entity_id` VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `user_id` VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_REMIND_AT` (remind_at), INDEX `IDX_START_AT` (start_at), INDEX `IDX_TYPE` (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("CREATE TABLE `unique_id` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `data` MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `terminate_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `target_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `target_type` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_NAME` (name), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_TARGET` (target_id, target_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->execute("ALTER TABLE `notification` ADD email_is_processed TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci");
    }
}
