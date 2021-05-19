<?php
require_once __DIR__ . '/bootstrap.php';

/**
 * SiteNewsWidget.class.php
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @version 1.0
 */
class SiteNewsWidget extends SiteNews\Plugin implements PortalPlugin
{
    use SiteNews\PluginLocalizationTrait;

    const GETTEXT_DOMAIN = 'site-news';

    public $config;
    protected $is_root;

    public function __construct()
    {
        parent::__construct();

        $this->initializeLocalization(static::GETTEXT_DOMAIN);

        $this->is_root = $GLOBALS['perm']->have_perm('root');
        $this->group   = $this->is_root
                       ? Request::option('group', SiteNews\Group::findFirst()->id)
                       : null;
    }

    public function getPluginName()
    {
        return (string) SiteNews\Config::getTitle() ?: $this->_('In eigener Sache');
    }

    public function getPortalTemplate()
    {
        $widget = $GLOBALS['template_factory']->open('shared/string');
        $widget->content = $this->getContent($this->group);
        $widget->icons   = $this->getNavigation();
        $widget->title   = $this->getPluginName();
        return $widget;
    }

    protected function getNavigation()
    {
        $navigation = [];

        if ($this->is_root) {
            $nav = new Navigation('', $this->url_for('add'));
            $nav->setImage(Icon::create('add'), tooltip2($this->_('Eintrag hinzufügen')) + ['data-dialog' => '']);
            $navigation[] = $nav;

            $show_inactive = $GLOBALS['user']->cfg->SITE_NEWS_WIDGET_SHOW_INACTIVE;
            $nav = new Navigation('', $this->url_for('toggle'));
            $nav->setImage(Icon::create($show_inactive ? 'checkbox-unchecked' : 'checkbox-checked'), tooltip2($this->_('Inaktive Einträge ausblenden')) + [
                'class'              => 'sitenews-active-toggle',
                'data-show-inactive' => json_encode($show_inactive),
            ]);
            $navigation[] = $nav;

            $nav = new Navigation('', $this->url_for('config'));
            $nav->setImage(Icon::create('admin'), tooltip2($this->_('Einstellungen bearbeiten')) + ['data-dialog' => '']);
            $navigation[] = $nav;
        }

        return $navigation;
    }

    protected function getContent($group)
    {
        return $this->getTemplate('widget.php')->render([
            'is_root'       => $this->is_root,
            'entries'       => SiteNews\Entry::findByGroup($group, !$this->is_root),
            'group'         => $group,
            'config'        => SiteNews\Config::Get(),
            'show_inactive' => $GLOBALS['user']->cfg->SITE_NEWS_WIDGET_SHOW_INACTIVE,
        ]);
    }

    public function add_action()
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        $this->setPageTitle($this->_('Eintrag hinzufügen'));

        echo $this->getTemplate('edit.php', true)->render([
            'entry' => new SiteNews\Entry(),
            'config' => SiteNews\Config::Get(),
        ]);
    }

    public function edit_action($id)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        $this->setPageTitle($this->_('Eintrag bearbeiten'));

        echo $this->getTemplate('edit.php', true)->render([
            'entry'  => SiteNews\Entry::find($id),
            'config' => SiteNews\Config::Get(),
            'group'  => $this->group,
        ]);
    }

    public function store_action($id = null)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        if (!Request::isPost()) {
            throw new InvalidMethodException();
        }

        $entry = new SiteNews\Entry($id);
        $entry->expires = strtotime(Request::get('expires') . ' 23:59:59');
        $entry->subject = Request::i18n('subject');
        $entry->content = Request::i18n('content');
        $entry->user_id = $GLOBALS['user']->id;
        $entry->groups  = SiteNews\Group::findMany(Request::getArray('groups'));
        $entry->store();

        PageLayout::postSuccess($this->_('Der Eintrag wurde gespeichert.'));
        $this->redirect("dispatch.php/start?group={$this->group}");
    }

    public function visit_action($id)
    {
        $id = Request::option('sitenews-toggle', $id);
        if ($entry = SiteNews\Entry::find($id)) {
            $entry->is_new = false;
        }

        if (Request::isXhr()) {
            $this->render_json(true);
        } else {
            $this->redirect("dispatch.php/start#sitenews-{$id}");
        }
    }

    public function delete_action($id)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        if (!Request::isPost()) {
            throw new MethodNotAllowedException();
        }

        SiteNews\Entry::find($id)->delete();

        if (Request::isXhr()) {
            $this->render_json(true);
        } else {
            PageLayout::postSuccess($this->_('Der Eintrag wurde gelöscht.'));
            $this->redirect('dispatch.php/start');
        }
    }

    public function content_action($perm)
    {
        if (!$this->is_root) {
            throw new AccessDeniedException;
        }

        echo $this->getContent($perm);
    }

    public function config_action()
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        if (Request::isPost()) {
            SiteNews\Config::setTitle(Request::i18n('title'));

            foreach (Request::getArray('groups') as $id => $data) {
                $group = (is_numeric($id) && $id < 0)
                       ? new SiteNews\Group()
                       : SiteNews\Group::find($id);
                $group->id       = $data['id'];
                $group->name     = Request::i18n("groups_{$id}_name");
                $group->position = (int) $data['position'];
                $group->store();

                $group->setRoles($data['roles']);
            }

            PageLayout::postSuccess($this->_('Die Einstellungen wurden gespeichert.'));
            $this->redirect('dispatch.php/start');
        }

        $this->setPageTitle($this->_('Einstellungen'));

        $template = $this->getTemplate('config.php', true);
        $template->title = SiteNews\Config::getTitle();
        $template->roles = RolePersistence::getAllRoles();
        $template->groups = SiteNews\Group::findAll();
        echo $template->render();
    }

    public function delete_group_action()
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        if (!Request::isPost()) {
            throw new MethodNotAllowedException();
        }

        $id = Request::option('id');
        if (!$id) {
            throw new RuntimeException('No id');
        }

        SiteNews\Group::find($id)->delete();

        if (Request::isXhr()) {
            $this->render_json(true);
        } else {
            PageLayout::postSuccess($this->_('Die Gruppe wurde gelöscht.'));
            $this->redirect('dispatch.php/start');
        }
    }

    public function toggle_action()
    {
        if (!$this->is_root) {
            throw new AccessDeniedException();
        }

        if (!Request::isPost()) {
            throw new MethodNotAllowedException();
        }

        $GLOBALS['user']->cfg->store(
            'SITE_NEWS_WIDGET_SHOW_INACTIVE',
            !$GLOBALS['user']->cfg->SITE_NEWS_WIDGET_SHOW_INACTIVE
        );

        $this->render_json($GLOBALS['user']->cfg->SITE_NEWS_WIDGET_SHOW_INACTIVE);
    }
}
