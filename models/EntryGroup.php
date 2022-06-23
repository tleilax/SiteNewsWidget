<?php
namespace SiteNews;

/**
 * @property string $news_id
 * @property string $group_id
 */
class EntryGroup extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'sitenews_entries_groups';

        parent::configure($config);
    }
}
