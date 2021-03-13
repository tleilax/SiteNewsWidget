<?php
/**
 * Migrations that creates the table for the site news.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 */
class SetupDb extends Migration
{
    /**
     * Returns the description of the migration.
     *
     * @return String containing the migration
     */
    public function description()
    {
        return 'Creates table that stores the site news';
    }

    /**
     * Create table.
     */
    public function up()
    {
        $query = "CREATE TABLE IF NOT EXISTS `sitenews_entries` (
                      `news_id` CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                      `subject` VARCHAR(256) NOT NULL,
                      `content` TEXT NOT NULL,
                      `user_id` CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                      `visibility` SET('autor', 'tutor', 'dozent', 'admin') NOT NULL,
                      `activated` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
                      `expires` INT(11) UNSIGNED NOT NULL,
                      `mkdate` INT(11) UNSIGNED NOT NULL,
                      `chdate` INT(11) UNSIGNED NOT NULL,
                      PRIMARY KEY (`news_id`)
                  ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC";
        DBManager::get()->exec($query);
    }

    /**
     * Remove table.
     */
    public function down()
    {
        $query = "DROP TABLE `sitenews_entries`";
        DBManager::get()->exec($query);
    }
}
