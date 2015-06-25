<?php
namespace SiteNews;

class Entry extends \SimpleORMap
{
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

    public static function findByPerm($perm, $only_visible = true)
    {
        $condition = 'FIND_IN_SET(?, visibility) > 0';
        if ($only_visible) {
            $condition .= ' AND expires > UNIX_TIMESTAMP()';
        }
        return self::findBySQL($condition, array($perm));
    }

    public static function countByPerm($perm, $only_visible = true)
    {
        $condition = 'FIND_IN_SET(?, visibility) > 0';
        if ($only_visible) {
            $condition .= ' AND expires > UNIX_TIMESTAMP()';
        }
        return self::countBySQL($condition, array($perm));
    }

    public function isVisibleForPerm($perm)
    {
        $visibility = explode(',', $this->visibility);
        return in_array($perm, $visibility);
    }
}
