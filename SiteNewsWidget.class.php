<?php
/**
 * SiteNewsWidget.class.php
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @version 1.0
 */
class SiteNewsWidget extends StudIPPlugin implements PortalPlugin
{
    public $config;
    protected $is_root;

    public function __construct()
    {
        parent::__construct();

        StudipAutoloader::addAutoloadPath($this->getPluginPath() . '/models', 'SiteNews');
        StudipAutoloader::addAutoloadPath($this->getPluginPath() . '/classes', 'SiteNews');

        $this->config = SiteNews\Config::Get();

        if (Request::isXhr()) {
            header('Content-Type: text/html;charset=windows-1252');
            header('X-Initialize-Dialog: true');
        }

        $this->is_root = $GLOBALS['perm']->have_perm('root');
        $this->perm    = $this->is_root
                       ? Request::option('perm', 'tutor')
                       : $GLOBALS['user']->perms;
    }

    public function getPluginName()
    {
        return Config::get()->SITE_NEWS_WIDGET_TITLE ?: _('In eigener Sache');
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
        $navigation = array();

        if ($this->is_root) {
            $nav = new Navigation('', PluginEngine::getLink($this, array(), 'add'));
            $nav->setImage('icons/16/blue/add.png', tooltip2(_('Eintrag hinzufügen')) + array('data-dialog' => ''));
            $navigation[] = $nav;

            $nav = new Navigation('', PluginEngine::getLink($this, array(), 'settings'));
            $nav->setImage('icons/16/blue/admin.png', tooltip2(_('Einstellungen')) + array('data-dialog' => 'size=auto'));
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

        $this->setPageTitle(_('Eintrag hinzufügen'));

        $template = $this->getTemplate('edit.php', true);
        $template->entry   = new SiteNews\Entry;
        echo $template->render();
    }

    public function edit_action($id)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        $this->setPageTitle(_('Eintrag bearbeiten'));

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

        PageLayout::postMessage(MessageBox::success(_('Der Eintrag wurde gespeichert.')));
        header('Location: ' . URLHelper::getLink('dispatch.php/start'));
    }

    public function visit_action()
    {
        $id = Request::option('sitenews-toggle');
        SiteNews\Entry::find($id)->is_new = true;

        header('Content-Type: application/json');
        echo json_encode(true);
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

        PageLayout::postMessage(MessageBox::success(_('Der Eintrag wurde gelöscht.')));
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

        $this->setPageTitle(_('Einstellungen'));

        if (Request::isPost()) {
            $title = Request::get('title', _('In eigener Sache'));
            $title = trim($title);

            Config::get()->store('SITE_NEWS_WIDGET_TITLE', $title);

            PageLayout::postMessage(MessageBox::success(_('Die Einstellungen wurden gespeichert.')));
            header('Location: ' . URLHelper::getURL('dispatch.php/start'));
            return;
        }

        $template = $this->getTemplate('settings.php', true);
        $template->title = Config::get()->SITE_NEWS_WIDGET_TITLE;
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

    public function setPageTitle($title)
    {
        $args = array_slice(func_get_args(), 1);
        $title = vsprintf($title, $args);

        if (Request::isXhr()) {
            header('X-Title: ' . $title);
        } else {
            PageLayout::setTitle($title);
        }
    }

    public function url_for($to)
    {
        $arguments = func_get_args();
        $last = end($arguments);
        if (is_array($last)) {
            $params = array_pop($arguments);
        } else {
            $params = array();
        }

        $path = implode('/', $arguments);

        return PluginEngine::getURL($this, $params, $path);
    }
}
