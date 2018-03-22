<?php
/**
 * SiteNewsWidget.class.php
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @version 1.0
 */
class SiteNewsWidget extends StudIPPlugin implements PortalPlugin
{
    const GETTEXT_DOMAIN = 'site-news';

    public $config;
    protected $is_root;

    public function __construct()
    {
        parent::__construct();

        bindtextdomain(static::GETTEXT_DOMAIN, $this->getPluginPath() . '/locale');
        bind_textdomain_codeset(static::GETTEXT_DOMAIN, 'UTF-8');

        StudipAutoloader::addAutoloadPath($this->getPluginPath() . '/models', 'SiteNews');
        StudipAutoloader::addAutoloadPath($this->getPluginPath() . '/classes', 'SiteNews');

        $this->config = SiteNews\Config::Get();

        $this->is_root = $GLOBALS['perm']->have_perm('root');
        $this->perm    = $this->is_root
                       ? Request::option('perm', 'tutor')
                       : $GLOBALS['user']->perms;
    }

    public function getPluginName()
    {
        return Config::get()->SITE_NEWS_WIDGET_TITLE ?: $this->_('In eigener Sache');
    }

    public function getPortalTemplate()
    {
        $this->addStylesheet('assets/sitenewswidget.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/sitenewswidget.js');

        $widget = $GLOBALS['template_factory']->open('shared/string');
        $widget->content = $this->getContent($this->perm);
        $widget->icons   = $this->getNavigation();
        $widget->title   = $this->getPluginName();
        return $widget;
    }

    protected function getNavigation()
    {
        $navigation = [];

        if ($this->is_root) {
            $nav = new Navigation('', PluginEngine::getLink($this, [], 'add'));
            $nav->setImage(Icon::create('add'), tooltip2($this->_('Eintrag hinzufügen')) + ['data-dialog' => '']);
            $navigation[] = $nav;

            $nav = new Navigation('', '#');
            $nav->setImage(Icon::create('checkbox-unchecked'), tooltip2($this->_('Inaktive Einträge ausblenden')) + [
                'class'              => 'sitenews-active-toggle',
                'data-show-inactive' => json_encode(true),
            ]);
            $navigation[] = $nav;

            $nav = new Navigation('', PluginEngine::getLink($this, [], 'settings'));
            $nav->setImage(Icon::create('admin'), tooltip2($this->_('Einstellungen')) + ['data-dialog' => 'size=auto']);
            $navigation[] = $nav;
        }

        return $navigation;
    }

    protected function getContent($perm)
    {
        $template = $this->getTemplate('widget.php');
        $template->is_root = $this->is_root;
        $template->entries = SiteNews\Entry::findByPerm($perm, !$this->is_root);
        $template->perm    = $perm;
        return $template->render();
    }

    public function add_action()
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        $this->setPageTitle($this->_('Eintrag hinzufügen'));

        $template = $this->getTemplate('edit.php', true);
        $template->entry   = new SiteNews\Entry;
        echo $template->render();
    }

    public function edit_action($id)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        $this->setPageTitle($this->_('Eintrag bearbeiten'));

        $template = $this->getTemplate('edit.php', true);
        $template->entry = SiteNews\Entry::find($id);
        echo $template->render();
    }

    public function store_action($id = null)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        if (!Request::isPost()) {
            throw new InvalidMethodException();
        }

        $visibility = Request::optionArray('visibility');

        $entry = new SiteNews\Entry($id);
        $entry->subject    = Request::get('subject');
        $entry->content    = Request::get('content');
        $entry->user_id    = $GLOBALS['user']->id;
        $entry->visibility = implode(',', $visibility);
        $entry->expires    = strtotime(Request::get('expires') . ' 23:59:59');
        $entry->store();

        PageLayout::postSuccess($this->_('Der Eintrag wurde gespeichert.'));
        header('Location: ' . URLHelper::getLink('dispatch.php/start'));
    }

    public function visit_action($id)
    {
        $id = Request::option('sitenews-toggle', $id);
        if ($entry = SiteNews\Entry::find($id)) {
            $entry->is_new = false;
        }

        if (Request::isXhr()) {
            header('Content-Type: application/json');
            echo json_encode(true);
        } else {
            header('Location: ' . URLHelper::getLink('dispatch.php/start#sitenews-' . $id));
        }
    }

    public function delete_action($id)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        if (!Request::isPost()) {
            throw new InvalidMethodException();
        }

        SiteNews\Entry::find($id)->delete();

        PageLayout::postSuccess($this->_('Der Eintrag wurde gelöscht.'));
        header('Location: ' . URLHelper::getLink('dispatch.php/start'));
    }

    public function content_action($perm)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException;
        }

        echo $this->getContent($perm);
    }

    public function settings_action()
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        $this->setPageTitle($this->_('Einstellungen'));

        if (Request::isPost()) {
            $title = Request::get('title', $this->_('In eigener Sache'));
            $title = trim($title);

            Config::get()->store('SITE_NEWS_WIDGET_TITLE', $title);

            PageLayout::postSuccess($this->_('Die Einstellungen wurden gespeichert.'));
            header('Location: ' . URLHelper::getURL('dispatch.php/start'));
            return;
        }

        $template = $this->getTemplate('settings.php', true);
        $template->title = Config::get()->SITE_NEWS_WIDGET_TITLE;
        echo $template->render();
    }

    protected function getTemplate($template, $layout = false)
    {
        if (Request::isXhr()) {
            header('X-Initialize-Dialog: true');
        }

        $factory  = new Flexi_TemplateFactory(__DIR__ . '/views');
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

    /**
     * Plugin localization for a single string.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string String to translate
     * @return translated string
     */
    public function _($string)
    {
        $result = static::GETTEXT_DOMAIN === null
                ? $string
                : dcgettext(static::GETTEXT_DOMAIN, $string, LC_MESSAGES);
        if ($result === $string) {
            $result = _($string);
        }

        if (func_num_args() > 1) {
            $arguments = array_slice(func_get_args(), 1);
            $result = vsprintf($result, $arguments);
        }

        return $result;
    }

    /**
     * Plugin localization for plural strings.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string0 String to translate (singular)
     * @param String $string1 String to translate (plural)
     * @param mixed  $n       Quantity factor (may be an array or array-like)
     * @return translated string
     */
    public function _n($string0, $string1, $n)
    {
        if (is_array($n)) {
            $n = count($n);
        }

        $result = static::GETTEXT_DOMAIN === null
                ? $string0
                : dngettext(static::GETTEXT_DOMAIN, $string0, $string1, $n);
        if ($result === $string0 || $result === $string1) {
            $result = ngettext($string0, $string1, $n);
        }

        if (func_num_args() > 3) {
            $arguments = array_slice(func_get_args(), 3);
            $result = vsprintf($result, $arguments);
        }

        return $result;
    }
}
