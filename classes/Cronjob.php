<?php
namespace SiteNews;

use DBManager;
use PDO;
use PluginManager;
use RolePersistence;
use SiteNewsWidget;

/**
 * SiteNewsCronjob.php
 *
 * Cronjob for the site news plugin that checks which news are visible and
 * activates the according widget for the associated permissions. If the
 * widget is activated, it is also positioned in the upper left corner
 * of all widgets - regardless of any user settings.
 * Essentially we force the user to acknowledge the news. The widget may
 * be removed but if a news is published, the widget will be repositioned.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 */
class Cronjob extends \CronJob
{
    // Defines the probability of the garbage collector
    const GC_PROPABILITY = 0.05;

    /**
     * Returns the name of the cronjob
     *
     * @return String containing the name of the cronjob
     */
    public static function getName()
    {
        return dgettext(SiteNewsWidget::GETTEXT_DOMAIN, '"In eigener Sache" Cronjob');
    }

    /**
     * Returns the description of the cronjob.
     *
     * @return String containing the description of the cronjob
     */
    public static function getDescription()
    {
        return dgettext(SiteNewsWidget::GETTEXT_DOMAIN, 'Prüft die Gültigkeit der Einträge für "In eigener Sache" und (de)aktiviert das Widget für die entsprechenden Nutzerkreise.');
    }

    /**
     * Initializes the cronjob execution. Loads required classes.
     */
    public function setUp()
    {
        require 'Config.php';
        require __DIR__ . '/../models/Entry.php';
    }

    /**
     * Executes/engages the cronjob.
     *
     * @param mixed $last_result The result of the last execution
     * @param array $parameters  Any defined parameters
     */
    public function execute($last_result, $parameters = array())
    {
        $info = PluginManager::getInstance()->getPluginInfo('SiteNewsWidget');
        if (!$info) {
            return false;
        }

        $plugin_id = $info['id'];

        $entries = Entry::findBySQL('expires > UNIX_TIMESTAMP()');
        $config  = Config::Get();

        $activate   = array();
        $deactivate = array_keys($config);

        if (!$entries) {
            return;
        }

        $perms = array();

        foreach ($entries as $entry) {
            $visibilities = explode(',', $entry->visibility);
            $deactivate   = array_diff($deactivate, $visibilities);

            if (!$entry->activated) {
                $activate = array_merge($activate, $visibilities);
                $activate = array_unique($activate);

                $entry->activated = true;
                $entry->store();
            }
        }

        foreach ($activate as $perm) {
            echo 'Activating for perm "' . $perm . '"' . PHP_EOL;
            $this->activatePluginForRole($plugin_id, $config[$perm]['role_id']);
            $this->positionWidgetByPerm($plugin_id, $perm);
        }

        foreach ($deactivate as $perm) {
            echo 'Deactivating for perm "' . $perm . '"' . PHP_EOL;
            $this->deactivatePluginForRole($plugin_id, $config[$perm]['role_id']);
        }

        if (mt_rand() / mt_getrandmax() <= self::GC_PROPABILITY) {
            $this->gc($perm);
        }
    }

    /**
     * Activates the widget (which is a plugin) for a certain role.
     *
     * @param String $plugin_id Id of the plugin
     * @param String $role_id   If of the role
     */
    private function activatePluginForRole($plugin_id, $role_id)
    {
        RolePersistence::assignPluginRoles($plugin_id, array($role_id));
    }

    /**
     * Deactivates the widget (which is a plugin) for a certain role.
     *
     * @param String $plugin_id Id of the plugin
     * @param String $role_id   If of the role
     */
    private function deactivatePluginForRole($plugin_id, $role_id)
    {
        RolePersistence::deleteAssignedPluginRoles($plugin_id, array($role_id));
    }

    /**
     * Positions the widget for a certain permission.
     *
     * @param String $plugin_id Id of the plugin
     * @param String $perm      The permission
     */
    private function positionWidgetByPerm($plugin_id, $perm)
    {
        $query = "DELETE FROM `widget_user`
                  WHERE `pluginid` = :plugin_id
                    AND `range_id` IN (
                        SELECT `user_id`
                        FROM `auth_user_md5`
                        WHERE `perms` = :perm
                    )";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':plugin_id', $plugin_id);
        $statement->bindValue(':perm', $perm);
        $statement->execute();

        $query = "UPDATE `widget_user`
                  SET `position` = `position` + 1
                  WHERE `col` = 0
                    AND `range_id` IN (
                        SELECT `user_id`
                        FROM `auth_user_md5`
                        WHERE `perms` = :perm
                    )";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':perm', $perm);
        $statement->execute();

        $query = "INSERT INTO `widget_user` (`pluginid`, `position`, `range_id`, `col`)
                  SELECT DISTINCT :plugin_id, 0, `user_id`, 0
                  FROM `auth_user_md5`
                  JOIN `widget_user` ON `auth_user_md5`.`user_id` = `widget_user`.`range_id`
                  WHERE `perms` = :perm";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':plugin_id', $plugin_id);
        $statement->bindValue(':perm', $perm);
        $statement->execute();
    }

    /**
     * Garbage collector. Reorders the defined widgets for users.
     * Removes gaps and high position numbers.
     *
     * @param String $perm The permission to garbage collect for
     */
    private function gc($perm)
    {
        $query = "SELECT DISTINCT `range_id`
                  FROM `widget_user`
                  WHERE `col` = 0";
        $statement = DBManager::get()->query($query);
        $user_ids = $statement->fetchAll(PDO::FETCH_COLUMN);
        $statement->closeCursor();

        $query = "SELECT `id`
                  FROM `widget_user`
                  WHERE `range_id` = :user_id
                    AND `col` = 0
                  ORDER BY `position` ASC";
        $ids_statement = DBManager::get()->prepare($query);

        $query = "UPDATE `widget_user`
                  SET `position` = :position
                  WHERE `id` = :id";
        $position_statement = DBManager::get()->prepare($query);

        foreach ($user_ids as $user_id) {
            $ids_statement->bindValue(':user_id', $user_id);
            $ids_statement->execute();
            $ids = $ids_statement->fetchAll(PDO::FETCH_COLUMN);
            $ids_statement->closeCursor();


            foreach ($ids as $position => $id) {
                $position_statement->bindValue(':position', $position);
                $position_statement->bindValue(':id', $id);
                $position_statement->execute();
            }
        }
    }
}
