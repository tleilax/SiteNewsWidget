<?php
namespace SiteNews;

use SiteNewsWidget;

/**
 * This class represents the config for the site news widget.
 *
 * @author  Jan-Hendrik Willms <tleilxa+studip@gmail.com>
 * @license GPL2 or any later version
 */
final class Config
{
    public static function Get(): array
    {
        $config = [];
        foreach (Group::findAll() as $group) {
            $config[$group->id] = (string) $group->name;
        }
        return $config;
    }

    public static function getTitle(): \I18NString
    {
        $title = \Config::get()->SITE_NEWS_WIDGET_TITLE;
        return new \I18NString($title, null, [
            'object_id' => 'title',
            'table'     => 'config',
            'field'     => 'site-news',
        ]);
    }

    public static function setTitle(\I18NString $title): void
    {
        \Config::get()->store('SITE_NEWS_WIDGET_TITLE', $title->original());

        $i18n = self::getTitle();
        $i18n->setOriginal($title->original());
        $i18n->setTranslations($title->toArray());
        $i18n->storeTranslations();
    }
}
