<?php
class RemoveConfigurableTitle extends Migration
{
    public function up()
    {
        Config::get()->delete('SITE_NEWS_WIDGET_TITLE');
    }

    public function down()
    {
        Config::get()->create('SITE_NEWS_WIDGET_TITLE', [
            'value'       => 'In eigener Sache',
            'is_default'  => 1,
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'Sprechstunden',
            'description' => 'Enthält den Titel des "Neuigkeiten an diesem Stud.IP-Standort"-Widgets',
        ]);
    }
}
