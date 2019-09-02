<?php
namespace SiteNews;

use Flexi_TemplateFactory;
use PageLayout;
use PluginEngine;
use Request;

class Plugin extends \StudIPPlugin
{
    protected $injected = false;

    protected function getTemplate($template, $layout = false)
    {
        if (Request::isXhr()) {
            header('X-Initialize-Dialog: true');
        }

        if (!$this->injected) {
            $this->addStylesheet('assets/sitenewswidget.less');
            PageLayout::addScript($this->getPluginURL() . '/assets/sitenewswidget.js?v=' . $this->getPluginVersion());

            $this->injected = true;
        }

        $factory  = new Flexi_TemplateFactory(__DIR__ . '/../views');
        $template = $factory->open($template);
        $template->controller = $this;
        if ($layout && !Request::isXhr()) {
            $template->set_layout($GLOBALS['template_factory']->open('layouts/base.php'));
        }
        $template->_ = function ($string) {
            return $this->_($string);
        };
        return $template;
    }

    public function setPageTitle($title)
    {
        $args = array_slice(func_get_args(), 1);
        $title = vsprintf($title, $args);
        PageLayout::setTitle($title);
    }

    public function url_for($to)
    {
        $arguments = func_get_args();
        $last = end($arguments);
        if (is_array($last)) {
            $params = array_pop($arguments);
        } else {
            $params = [];
        }

        $path = implode('/', $arguments);

        return PluginEngine::getURL($this, $params, $path);
    }

    public function link_for($to)
    {
        return htmlReady(call_user_func_array([$this, 'url_for'], func_get_args()));
    }

    public function render_json($what)
    {
        header('Content-Type: application/json');
        echo json_encode($what);
    }

    public function redirect($to)
    {
        page_close();

        $url = call_user_func_array('URLHelper::getURL', func_get_args());
        header("Location: {$url}");
        die;
    }

    protected function getPluginVersion()
    {
        static $manifest = null;
        if ($manifest === null) {
            $manifest = $this->getMetadata();
        }
        return $manifest['version'];
    }
}
