<?php
namespace SiteNews;

/**
 * Defines a single site news entry.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 *
 * @property string $id
 * @property string $news_id
 * @property \I18NString $subject
 * @property \I18NString $content
 * @property string $user_id
 * @property bool $activated
 * @property int $expires
 * @property int $mkdate
 * @property int $chdate
 *
 * @property \User $author
 * @property Group[]|\SimpleORMapCollection $groups
 *
 * @property bool $is_new
 * @property bool $is_active
 * @property int $views
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
     * @param array $config Configuration array
     */
    public static function configure($config = [])
    {
        $config['db_table'] = 'sitenews_entries';

        $config['has_one'] = [
            'author' => [
                'class_name'        => \User::class,
                'assoc_foreign_key' => 'user_id',
                'foreign_key'       => 'user_id',
            ],
        ];
        $config['has_and_belongs_to_many'] = [
            'groups' => [
                'class_name'     => Group::class,
                'thru_table'     => 'sitenews_entries_groups',
                'thru_key'       => 'news_id',
                'thru_assoc_key' => 'group_id',
                'on_delete'      => 'delete',
                'on_store'       => 'store',
            ],
        ];
        $config['additional_fields'] = [
            'is_new' => [
                'get' => function (Entry $entry) {
                    $visit = object_get_visit($entry->id, 'news', '', '', $GLOBALS['user']->id);
                    return !$visit || $visit < $entry->mkdate;
                },
                'set' => function (Entry $entry) {
                    object_set_visit($entry->id, 'news', $GLOBALS['user']->id);
                    object_add_view($entry->id);
                },
            ],
            'is_active' => [
                'get' => function (Entry $entry) {
                    return $entry->activated
                        && $entry->expires >= time();
                },
                'set' => false,
            ],
            'views' => [
                'get' => function (Entry $entry) {
                    return object_return_views($entry->id);
                },
                'set' => false,
            ],
        ];

        $config['registered_callbacks'] = [
            'after_delete' => [
                function ($item) {
                    object_kill_visits(false, $item->id);
                    object_kill_views($item->id);
                },
            ],
        ];

        $config['i18n_fields'] = [
            'subject' => true,
            'content' => true,
        ];

        parent::configure($config);
    }

    /**
     * Finds a set of entries by group (and optionally by visible state).
     * Entries are visible when they are not yet expired.
     *
     * @param string|null $group        Group/roles to get entries for
     * @param bool        $only_visible Show only visible / not expired entries
     *                                  (optional, defaults to true)
     * @return array of matching entries
     */
    public static function findByGroup(?string $group, bool $only_visible = true): array
    {
        if (!User::findCurrent()) {
            return [];
        }

        if ($group === null) {
            $condition = "JOIN `sitenews_entries_groups` USING (`news_id`)
                          JOIN `sitenews_groups_roles` USING (`group_id`)
                          -- Explicit assignment
                          LEFT JOIN `roles_user`
                            ON `roles_user`.`roleid` = `sitenews_groups_roles`.`role_id`
                               AND `roles_user`.`userid` = :user_id
                          -- Implicit assignment
                          LEFT JOIN `roles_studipperms`
                            ON `roles_studipperms`.`roleid` = `sitenews_groups_roles`.`role_id`
                          LEFT JOIN `auth_user_md5`
                            ON `auth_user_md5`.`perms` = `roles_studipperms`.`permname`
                               AND `auth_user_md5`.`user_id` = :user_id
                          WHERE `roles_user`.`userid` IS NOT NULL
                            OR `auth_user_md5`.`user_id` IS NOT NULL";
            $parameters = [
                ':user_id'  => \User::findCurrent()->id,
            ];
        } else {
            $condition = "JOIN `sitenews_entries_groups` USING (`news_id`)
                          WHERE `group_id`= ?";
            $parameters = [$group];
        }

        if ($only_visible) {
            $condition .= ' AND expires > UNIX_TIMESTAMP()';
        }
        $condition .= " ORDER BY mkdate DESC";
        return self::findBySQL($condition, $parameters);
    }

    /**
     * Count the number of entries by group (and optionally by visible
     * state). Entries are visible when they are not yet expired.
     *
     * @param string $group Group/roles to count entries for
     * @param bool $only_visible Show only visible / not expired entries
     *                           (optional, defaults to true)
     * @return int Number of found entries
     */
    public static function countByGroup(string $group, bool $only_visible = true): int
    {
        $condition = "JOIN `sitenews_entries_groups` USING (`news_id`)
                      WHERE `group_id`= ?";
        if ($only_visible) {
            $condition .= ' AND expires > UNIX_TIMESTAMP()';
        }
        return self::countBySQL($condition, [$group]);
    }
}
