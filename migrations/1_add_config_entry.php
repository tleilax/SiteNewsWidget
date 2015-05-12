<?php
class AddConfigEntry extends Migration
{
    public function description()
    {
        return 'Adds database config entry';
    }

    public function up()
    {
        $query = "INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`,
                                               `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`)
                  VALUES (MD5(:field), '', :field, :value, '1', 'string',
                          'global', '', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description, '', '')";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':field', 'SITE_NEWS_WIDGET_CONTENT');
        $statement->bindValue(':value', '');
        $statement->bindValue(':description', 'Enthält den Inhalt des "Neuigkeiten an diesem Stud.IP-Standort"-Widgets');
        $statement->execute();

        $statement->bindValue(':field', 'SITE_NEWS_WIDGET_TITLE');
        $statement->bindValue(':value', 'In eigener Sache');
        $statement->bindValue(':description', 'Enthält den Titel des "Neuigkeiten an diesem Stud.IP-Standort"-Widgets');
        $statement->execute();
    }

    public function down()
    {
        $query = "DELETE FROM `config` WHERE `field` IN ('SITE_NEWS_WIDGET_CONTENT', 'SITE_NEWS_WIDGET_TITLE')";
        DBManager::get()->exec($query);
    }
}