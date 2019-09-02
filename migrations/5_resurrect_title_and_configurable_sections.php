<?php
class ResurrectTitleAndConfigurableSections extends Migration
{
    public function up()
    {
        Config::get()->create('SITE_NEWS_WIDGET_TITLE', [
            'value'       => 'In eigener Sache',
            'is_default'  => 1,
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'Sprechstunden',
            'description' => 'Enthält den Titel des "Neuigkeiten an diesem Stud.IP-Standort"-Widgets',
        ]);

        $query = "CREATE TABLE IF NOT EXISTS `sitenews_groups` (
                    `group_id` CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
                    `name` VARCHAR(256) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
                    `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (`group_id`)
                  ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC";
        DBManager::get()->exec($query);

        $query = "CREATE TABLE IF NOT EXISTS `sitenews_groups_roles` (
                    `group_id` CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
                    `role_id` INT(11) NOT NULL,
                    PRIMARY KEY (`group_id`,`role_id`)
                  ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC";
        DBManager::get()->exec($query);

        $query = "CREATE TABLE IF NOT EXISTS `sitenews_entries_groups` (
                    `news_id` CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
                    `group_id` CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
                    PRIMARY KEY (`news_id`, `group_id`)
                  ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC";
        DBManager::get()->exec($query);

        $query = "INSERT IGNORE INTO `sitenews_groups` (`group_id`, `name`, `position`)
                  VALUES ('autor', 'Gäste', 1), ('tutor', 'Studierende', 2),
                         ('dozent', 'Lehrende', 3), ('admin', 'Admins', 4)";
        DBManager::get()->exec($query);

        $query = "INSERT IGNORE INTO `sitenews_groups_roles` (`group_id`, `role_id`)
                  VALUES ('autor', 5), ('tutor', 6), ('dozent', 4), ('admin', 2)";
        DBManager::get()->exec($query);

        // TODO TEST
        $query = "SELECT `news_id`, `visibility` FROM `sitenews_entries`";
        $data = DBManager::get()->fetchAll($query);

        $query = "INSERT IGNORE INTO `sitenews_entries_groups` (`news_id`, `group_id`)
                  VALUES (:news_id, :group)";
        $statement = DBManager::get()->prepare($query);
        foreach ($data as $row) {
            $statement->bindValue(':news_id', $row['news_id']);
            foreach (explode(',', $row['visibility']) as $group) {
                $statement->bindValue(':group', $group);
                $statement->execute();
            }
        }

        $query = "ALTER TABLE `sitenews_entries`
                  DROP COLUMN `visibility`";
        DBManager::get()->exec($query);

        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        Config::get()->delete('SITE_NEWS_WIDGET_TITLE');

        $query = "DROP TABLE IF EXISTS
                    `sitenews_entries_visibilities`,
                    `sitenews_groups`,
                    `sitenews_groups_roles`";
        DBManager::get()->exec($query);

        $query = "ALTER TABLE `sitenews_entries`
                  ADD COLUMN `visibility` SET('autor', 'tutor', 'dozent', 'admin') CHARACTER SET latin1 COLLATE latin1_bin AFTER `user_id`";
        DBManager::get()->exec($query);

        SimpleORMap::expireTableScheme();
    }
}
