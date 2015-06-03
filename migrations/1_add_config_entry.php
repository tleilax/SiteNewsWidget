<?php
/**
 * Migration that creates the config entry for the title of the widget.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 */
class AddConfigEntry extends Migration
{
    /**
     * Returns the description of the migration.
     *
     * @return String containing the migration
     */
    public function description()
    {
        return 'Adds database config entry';
    }

    /**
     * Creates the config entry
     */
    public function up()
    {
        $query = "INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`,
                                               `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`)
                  VALUES (MD5(:field), '', :field, :value, '1', 'string',
                          'global', '', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description, '', '')";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':field', 'SITE_NEWS_WIDGET_TITLE');
        $statement->bindValue(':value', 'In eigener Sache');
        $statement->bindValue(':description', 'Enthält den Titel des "Neuigkeiten an diesem Stud.IP-Standort"-Widgets');
        $statement->execute();
    }

    /**
     * Removes the config entry
     */
    public function down()
    {
        $query = "DELETE FROM `config` WHERE `field` IN ('SITE_NEWS_WIDGET_TITLE')";
        DBManager::get()->exec($query);
    }
}