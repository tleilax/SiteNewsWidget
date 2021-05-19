<?php
final class InactiveUserConfig extends Migration
{
    public function up()
    {
        Config::get()->create('SITE_NEWS_WIDGET_SHOW_INACTIVE', [
            'value'       => 0,
            'is_default'  => 1,
            'type'        => 'boolean',
            'range'       => 'user',
            'section'     => '',
            'description' => 'Gibt an, ob abgelaufene Einträge angezeigt werden sollen oder nicht',
        ]);
    }

    public function down()
    {
        Config::get()->delete('SITE_NEWS_WIDGET_SHOW_INACTIVE');
    }
}
