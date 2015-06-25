<?php
namespace SiteNews;

/**
 * Defines a single site news entry.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 */
class Entry extends \SimpleORMap
{
    /**
     * Configures the model.
     *
     * Connects to author through User model and provides additional fields
     * for the entry's number of views and the information whether the entry
     * has been seen / is new for the current user.
     *
     * @param Array $config Configuration array
     */
    public static function configure($config = array())
    {
        $config['db_table'] = 'sitenews_entries';
        $config['has_one']['author'] = array(
            'class_name'  => 'User',
            'assoc_foreign_key' => 'user_id',
            'foreign_key' => 'user_id',
        );
        $config['additional_fields']['is_new'] = array(
            'get' => function ($item) {
                return !object_get_visit($item->id, 'news', '', '', $GLOBALS['user']->id);
            },
            'set' => function ($item, $field, $value) {
                object_set_visit($item->id, 'news', $GLOBALS['user']->id);
                object_add_view($item->id);
            },
        );
        $config['additional_fields']['views'] = array(
            'get' => function ($item) {
                return object_return_views($item->id);
            },
            'set' => false,
        );

        parent::configure($config);
    }

    /**
     * Finds a set of entries by permission (and optionally by visible state).
     * Entries are visible when they are not yet expired.
     *
     * @param String $perm Neccessary permission to view the entry (either
     *                     autor, tutor, dozent or admin)
     * @param bool $only_visible Show only visible / not expired entries
     *                           (optional, defaults to true)
     * @return Array of matching entries
     */
    public static function findByPerm($perm, $only_visible = true)
    {
        $condition = 'FIND_IN_SET(?, visibility) > 0';
        if ($only_visible) {
            $condition .= ' AND expires > UNIX_TIMESTAMP()';
        }
        return self::findBySQL($condition, array($perm));
    }

    /**
     * Count the number of entries by permission (and optionally by visible
     * state). Entries are visible when they are not yet expired.
     *
     * @param String $perm Neccessary permission to view the entry (either
     *                     autor, tutor, dozent or admin)
     * @param bool $only_visible Show only visible / not expired entries
     *                           (optional, defaults to true)
     * @return int Number of found entries
     */
    public static function countByPerm($perm, $only_visible = true)
    {
        $condition = 'FIND_IN_SET(?, visibility) > 0';
        if ($only_visible) {
            $condition .= ' AND expires > UNIX_TIMESTAMP()';
        }
        return self::countBySQL($condition, array($perm));
    }

    /**
     * Returns whether the entry is visible for the given permission.
     *
     * @param String $perm Neccessary permission to view the entry (either
     *                     autor, tutor, dozent or admin)
     * @return bool indicating whether the entry is visible
     */
    public function isVisibleForPerm($perm)
    {
        $visibility = explode(',', $this->visibility);
        return in_array($perm, $visibility);
    }

    /**
     * Overloaded delete method of the entry. Removes associated views
     * and visits.
     *
     * @return mixed false on error, otherwise the number of deleted records
     * @see SimpleORMap::delete
     */
    public function delete()
    {
        $result = parent::delete();

        // Remove views and visits
        if ($result !== false) {
            object_kill_visits(false, $this->id);
            object_kill_views($this->id);
        }

        return $result;
    }
}
