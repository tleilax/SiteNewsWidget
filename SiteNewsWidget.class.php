<?php
/**
 * SiteNewsWidget.class.php
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @version 1.0
 */

class SiteNewsWidget extends StudIPPlugin implements PortalPlugin
{
    protected $config;
    
    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get();

        if (Request::isXhr()) {
            header('Content-Type: text/html;charset=windows-1252');
            header('X-Initialize-Dialog: true');
        }
    }

    public function getPluginName()
    {
        return $this->getTitle();
    }

    public function getPortalTemplate()
    {
        $this->addStylesheet('assets/sitenewswidget.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/sitenewswidget.js');

        $widget = $GLOBALS['template_factory']->open('shared/string');
        $widget->content = $this->getContent(true);
        $widget->icons   = $this->getNavigation();
        $widget->title   = $this->getPluginName();
        return $widget;
    }

    protected function getNavigation()
    {
        $navigation = array();

        if ($GLOBALS['user']->perms === 'root') {
            $nav = new Navigation('', PluginEngine::getLink($this, array(), 'edit'));
            $nav->setImage('icons/16/blue/edit.png', tooltip2(_('Inhalte bearbeiten')) + array('data-dialog' => ''));
            $navigation[] = $nav;
        }

        return $navigation;
    }

    protected function getConfig($key)
    {
        return $this->config->$key;
    }
    
    protected function storeConfig($key, $value)
    {
        $value = trim($value);

        $this->config->store($key, $value);
    }

    protected function getTitle()
    {
        return $this->config->SITE_NEWS_WIDGET_TITLE;
    }

    protected function getContent($formatted = false)
    {
        $content = $this->getConfig('SITE_NEWS_WIDGET_CONTENT');
        if (!$formatted) {
            return $content;
        }

        $template = $this->getTemplate('widget.php');
        $template->content = $content;
        return $template->render();
    }

    public function edit_action()
    {
        PageLayout::setTitle(_('Inhalte bearbeiten'));

        if (Request::isPost()) {
            $this->storeConfig('SITE_NEWS_WIDGET_TITLE', Request::get('title'));
            $this->storeConfig('SITE_NEWS_WIDGET_CONTENT', Request::get('content'));

            header('Location: ' . URLHelper::getLink('dispatch.php/start'));
            return;
        }
        
        $template = $this->getTemplate('edit.php', true);
        $template->content = $this->getContent();
        $template->title   = $this->getTitle();
        $template->action  = PluginEngine::getLink($this, array(), 'edit');
        $template->cancel  = URLHelper::getLink('dispatch.php/start');
        echo $template->render();
    }

    protected function getTemplate($template, $layout = false)
    {
        $factory  = new Flexi_TemplateFactory(__DIR__ . '/views');
        $template = $factory->open($template);
        $template->controller = $this; 
        if ($layout && !Request::isXhr()) {
            $template->set_layout($GLOBALS['template_factory']->open('layouts/base.php'));
        }
        return $template;
    }
}
