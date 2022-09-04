<?php
namespace SiteNews;

/**
 * @property string $group_id
 * @property int $role_id
 */
class GroupRole extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'sitenews_groups_roles';

        parent::configure($config);
    }
}
