<?php
/**
 * Migration that creates the config entry for the title of the widget.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 */
class AddConfigEntry extends Migration
{
    /**
     * Returns the description of the migration.
     *
     * @return String containing the migration
     */
    public function description()
    {
        return 'Adds database config entry';
    }

    /**
     * Creates the config entry
     */
    public function up()
    {
        Config::get()->create('SITE_NEWS_WIDGET_TITLE', [
            'value' => 'In eigener Sache',
            'is_default' => 1,
            'type' => 'string',
            'range' => 'global',
            'section' => 'Sprechstunden',
            'description' => 'Enthält den Titel des "Neuigkeiten an diesem Stud.IP-Standort"-Widgets',
        ]);
    }

    /**
     * Removes the config entry
     */
    public function down()
    {
        Config::get()->delete('SITE_NEWS_WIDGET_TITLE');
    }
}
