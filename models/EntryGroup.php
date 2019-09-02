<?php
namespace SiteNews;

class EntryGroup extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'sitenews_entries_groups';

        parent::configure($config);
    }
}
