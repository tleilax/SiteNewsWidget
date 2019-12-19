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
     * @return string containing the name of the cronjob
     */
    public static function getName()
    {
        return '"In eigener Sache" Cronjob';
    }

    /**
     * Returns the description of the cronjob.
     *
     * @return string containing the description of the cronjob
     */
    public static function getDescription()
    {
        return 'Prüft die Gültigkeit der Einträge für "In eigener Sache" und (de)aktiviert das Widget für die entsprechenden Nutzerkreise.';
    }

    /**
     * Initializes the cronjob execution. Loads required classes.
     */
    public function setUp()
    {
        require_once __DIR__ . '/../bootstrap.php';
    }

    /**
     * Executes/engages the cronjob.
     *
     * @param mixed $last_result The result of the last execution
     * @param array $parameters  Any defined parameters
     */
    public function execute($last_result, $parameters = [])
    {
        $info = PluginManager::getInstance()->getPluginInfo('SiteNewsWidget');
        if (!$info) {
            return false;
        }

        $plugin_id = $info['id'];

        $entries = Entry::findBySQL('expires > UNIX_TIMESTAMP()');
        if (!$entries) {
            return;
        }

        $activate   = [];
        $deactivate = array_keys(Config::Get());

        foreach ($entries as $entry) {
            $visibilities = $entry->groups->pluck('group_id');
            $deactivate   = array_diff($deactivate, $visibilities);

            if (!$entry->activated) {
                $activate = array_merge($activate, $visibilities);
                $activate = array_unique($activate);

                $entry->activated = true;
                $entry->store();
            }
        }

        foreach ($activate as $group) {
            echo 'Activating for group "' . $group . '"' . PHP_EOL;
            $this->activatePluginForGroup($plugin_id, $group);
            $this->positionWidgetByGroup($plugin_id, $group);
        }

        foreach ($deactivate as $group) {
            echo 'Deactivating for group "' . $group . '"' . PHP_EOL;
            $this->deactivatePluginForGroup($plugin_id, $group);
        }

        if (mt_rand() / mt_getrandmax() <= self::GC_PROPABILITY) {
            $this->gc();
        }
    }

    /**
     * Activates the widget (which is a plugin) for a certain role.
     *
     * @param string $plugin_id Id of the plugin
     * @param string $role_id   If of the role
     */
    private function activatePluginForGroup($plugin_id, $group)
    {
        Group::find($group)->roles->each(function (GroupRole $role) {
            RolePersistence::assignPluginRoles($plugin_id, $role->role_id);
        });
    }

    /**
     * Deactivates the widget (which is a plugin) for a certain role.
     *
     * @param string $plugin_id Id of the plugin
     * @param string $role_id   If of the role
     */
    private function deactivatePluginForGroup($plugin_id, $group)
    {
        Group::find($group)->roles->each(function (GroupRole $role) {
            RolePersistence::deleteAssignedPluginRoles($plugin_id, $role->role_id);
        });
    }

    /**
     * Positions the widget for a certain group.
     *
     * @param string $plugin_id Id of the plugin
     * @param string $group      The group
     */
    private function positionWidgetByGroup($plugin_id, $group)
    {
        $query = "DELETE FROM `widget_user`
                  WHERE `pluginid` = :plugin_id
                    AND `range_id` = :user_id";
        $delete_statement = DBManager::get()->prepare($query);
        $delete_statement->bindValue(':plugin_id', $plugin_id);

        $query = "UPDATE `widget_user`
                  SET `position` = `position` + 1
                  WHERE `col` = 0
                    AND `range_id` = :user_id";
        $reposition_statement = DBManager::get()->prepare($query);

        $query = "INSERT INTO `widget_user` (`pluginid`, `position`, `range_id`, `col`)
                  VALUES (:plugin_id, 0, :user_id, 0)";
        $add_statement = DBManager::get()->prepare($query);
        $add_statement->bindValue(':plugin_id', $plugin_id);

        Group::find($group)->eachUser(function ($user) use ($delete_statement, $reposition_statement, $add_statement) {
            $delete_statement->bindValue(':user_id', $user->id);
            $delete_statement->execute();

            $reposition_statement->bindValue(':user_id', $user->id);
            $reposition_statement->execute();

            $add_statement->bindValue(':user_id', $user->id);
            $add_statement->execute();
        });
    }

    /**
     * Garbage collector. Reorders the defined widgets for users.
     * Removes gaps and high position numbers.
     */
    private function gc()
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
