<?php
/**
 * @var SiteNews\Entry[] $entries
 * @var bool $show_inactive
 * @var bool $is_root
 * @var SiteNewsWidget $controller
 * @var array $config
 * @var string $group
 * @var callable $_
 */
?>
<?php
    $visible = count(array_filter(
        $entries,
        function ($entry) use ($show_inactive) {
            return $show_inactive || $entry->is_active;
        }
    ));
?>
<article class="studip sitenews-widget">
<? if ($is_root): ?>
    <nav class="widget-tabs" data-source="<?= $controller->link_for('content/#{group}') ?>">
        <ul>
        <? foreach ($config as $g => $label): ?>
            <li <? if ($group === $g) echo 'class="current"'; ?>>
                <a href="<?= URLHelper::getLink('?', ['group' => $g]) ?>" data-group="<?= htmlReady($g) ?>">
                    <?= htmlReady($label) ?>
                    (<?= SiteNews\Entry::countByGroup($g, false) ?>)
                </a>
            </li>
        <? endforeach; ?>
        </ul>
    </nav>
<? endif; ?>
<? foreach ($entries as $entry): ?>
    <article class="studip toggle <? if ($entry->is_new): ?>new<? endif; ?>" data-visiturl="<?= $controller->url_for("visit/{$entry->id}") ?>" id="sitenews-<?= htmlReady($entry->id) ?>" data-active="<?= json_encode($entry->is_active) ?>" <? if (!$show_inactive && !$entry->is_active) echo 'style="display: none;"'; ?>>
        <header>
            <h1>
                <a href="#">
                    <?= htmlReady($entry->subject) ?>
                </a>
            </h1>
            <nav>
            <? if ($entry->author !== null): ?>

                <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $entry->author->username) ?>">
                    <?= Avatar::getAvatar($entry->author->id)->getImageTag(Avatar::SMALL) ?>
                    <?= htmlReady($entry->author->getFullname()) ?>
                </a>
            <? else: ?>
                <?= $_('unbekannt') ?>
            <? endif; ?>
                <span>
                    <?= strftime('%x', $entry->mkdate) ?>
                <? if ($is_root): ?>
                    /
                    <?= strftime('%x', $entry->expires) ?>
                <? endif; ?>
                </span>
                <span style="color: #050;"><?= $entry->views ?></span>
            <? if ($is_root): ?>
                <a href="<?= $controller->link_for('edit', $entry->id, ['group' => $g]) ?>" data-dialog>
                    <?= Icon::create('edit')->asImg(tooltip2($_('Eintrag bearbeiten')))?>
                </a>
                <form action="<?= $controller->link_for('delete', $entry->id) ?>" method="post"
                    data-confirm="<?= $_('Wollen Sie diesen Eintrag wirklich löschen?') ?>">
                    <?= Icon::create('trash')->asInput(tooltip2($_('Eintrag löschen')) + [
                        'style' => 'vertical-align: middle',
                    ]) ?>
                </form>
            <? endif; ?>
            </nav>
        </header>
        <section>
            <article>
                <?= formatReady($entry->content) ?>
            </article>
        </section>
    </article>
<? endforeach; ?>
    <section class="no-entries" <? if ($entries && $visible > 0) echo 'style="display: none"'; ?>>
        <?= $_('Es sind keine Einträge vorhanden') ?>
    </section>
</article>
