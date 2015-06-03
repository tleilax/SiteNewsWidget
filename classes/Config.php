<?php
namespace SiteNews;

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
        return array(
            'autor'  => array(
                'label'   => _('Gäste'),
                'role_id' => 5,
            ),
            'tutor'  => array(
                'label'   => _('Studierende'),
                'role_id' => 6,
            ),
            'dozent' => array(
                'label'   => _('Lehrende'),
                'role_id' => 4,
            ),
            'admin'  => array(
                'label'   => _('Admins'),
                'role_id' => 2,
            ),
        );
    }
}