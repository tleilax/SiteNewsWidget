<?php
namespace SiteNews;

class GroupRole extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'sitenews_groups_roles';
        
        parent::configure($config);
    }
}
