<?php
namespace SiteNews;

use Flexi_TemplateFactory;
use PageLayout;
use PluginEngine;
use Request;

abstract class Plugin extends \StudIPPlugin
{
    protected $injected = false;

    protected function getTemplate(string $template, bool $layout = false): \Flexi_Template
    {
        if (Request::isXhr()) {
            header('X-Initialize-Dialog: true');
        }

        if (!$this->injected) {
            $this->addStylesheet('assets/sitenewswidget.scss');
            $this->addScript('assets/sitenewswidget.js');

            $this->injected = true;
        }

        $factory  = new Flexi_TemplateFactory(__DIR__ . '/../views');
        $template = $factory->open($template);
        $template->controller = $this;
        if ($layout && !Request::isXhr()) {
            $template->set_layout($GLOBALS['template_factory']->open('layouts/base.php'));
        }
        $template->_ = function (string $string): string {
            return $this->_($string);
        };
        return $template;
    }

    public function setPageTitle(string $title): void
    {
        $args = array_slice(func_get_args(), 1);
        $title = vsprintf($title, $args);
        PageLayout::setTitle($title);
    }

    public function url_for($to): string
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

    public function link_for($to): string
    {
        return htmlReady($this->url_for(...func_get_args()));
    }

    public function render_json($what): void
    {
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($what);
    }

    public function redirect(string $to): void
    {
        page_close();

        $url = URLHelper::getURL(...func_get_args());
        header("Location: {$url}");
        die;
    }
}
