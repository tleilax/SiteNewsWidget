<section class="contentbox sitenews-widget">
<? if ($is_root): ?>
    <nav class="widget-tabs" data-source="<?= $controller->url_for('content/#{perm}') ?>">
        <ul>
        <? foreach ($controller->config as $status => $config): ?>
            <li <? if ($perm === $status) echo 'class="current"'; ?>>
                <a href="<?= URLHelper::getLink('?', array('perm' => $status)) ?>" data-perm="<?= htmlReady($status) ?>">
                    <?= htmlReady($config['label']) ?>
                    (<?= SiteNews\Entry::countByPerm($status, false) ?>)
                </a>
            </li>
        <? endforeach; ?>
        </ul>
    </nav>
<? endif; ?>
<? foreach ($entries as $entry): ?>
    <article <? if ($entry->is_new): ?>class="new" data-visiturl="<?= $controller->url_for('visit/' . $entry->id) ?>"<? endif; ?>>
        <header>
            <h1>
                <a href="<?= URLHelper::getLink('?sitenews-toggle=' . $entry->id) ?>">
                    <?= htmlReady($entry->subject) ?>
                </a>
            </h1>
            <nav>
                <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $entry->author->username) ?>">
                    <?= Avatar::getAvatar($entry->author->id)->getImageTag(Avatar::SMALL) ?>
                    <?= htmlReady($entry->author->getFullname()) ?>
                </a>
                <span>
                    <?= strftime('%x', $entry->mkdate) ?>
                <? if ($is_root): ?>
                    /
                    <?= strftime('%x', $entry->expires) ?>
                <? endif; ?>
                </span>
                <span style="color: #050;"><?= $entry->views ?></span>
            <? if ($is_root): ?>
                <a href="<?= $controller->url_for('edit', $entry->id) ?>" data-dialog>
                    <?= Icon::create('edit', 'clickable', tooltip2(_('Eintrag bearbeiten')))?>
                </a>
                <form action="<?= $controller->url_for('delete', $entry->id) ?>" method="post" data-confirm="<?= _('Wollen Sie diesen Eintrag wirklich löschen?') ?>">
                    <?= Icon::create('trash', 'clickable', tooltip2(_('Eintrag löschen')))->render(Icon::SVG | Icon::INPUT) ?>
                </form>
            <? endif; ?>
            </nav>
        </header>
        <section>
            <?= formatReady($entry->content) ?>
        </section>
    </article>
<? endforeach; ?>
<? if (!$entries): ?>
    <section>
        <?= _('Es sind keine Einträge vorhanden') ?>
    </section>
<? endif; ?>
</section>

