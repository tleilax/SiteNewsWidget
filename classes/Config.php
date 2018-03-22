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
    /**
     * Return the config for the widget.
     *
     * @return Array containing the configuration
     */
    public static function Get()
    {
        return [
            'autor'  => [
                'label'   => dgettext(SiteNewsWidget::GETTEXT_DOMAIN, 'Gäste'),
                'role_id' => 5,
            ],
            'tutor'  => [
                'label'   => dgettext(SiteNewsWidget::GETTEXT_DOMAIN, 'Studierende'),
                'role_id' => 6,
            ],
            'dozent' => [
                'label'   => dgettext(SiteNewsWidget::GETTEXT_DOMAIN, 'Lehrende'),
                'role_id' => 4,
            ],
            'admin'  => [
                'label'   => dgettext(SiteNewsWidget::GETTEXT_DOMAIN, 'Admins'),
                'role_id' => 2,
            ],
        ];
    }
}
