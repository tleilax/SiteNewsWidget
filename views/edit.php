<?php
/**
 * @var SiteNewsWidget $controller
 * @var SiteNews\Entry $entry
 * @var string $group
 * @var array $config
 * @var callable $_
 */
?>
<form action="<?= $controller->link_for("store/{$entry->id}", compact('group')) ?>" method="post" class="sitenews-editor default">
    <fieldset>
        <legend><?= $_('Inhalte bearbeiten') ?></legend>

        <label>
            <?= $_('Anzeigen bis') ?>
            <input type="text" name="expires" data-date-picker
                   value="<?= strftime('%x', $entry->expires ?: time()) ?>">
        </label>

        <label>
            <?= $_('Titel') ?>
            <?= I18N::input('subject', $entry->subject, [
                'required'    => '',
                'placeholder' => $_('Titel des Eintrags'),
            ]) ?>
        </label>

        <label>
            <?= $_('Inhalt') ?>
            <?= I18N::textarea('content', $entry->content, [
                'required' => '',
                'data-secure' => '',
                'class' => 'add_toolbar wysiwyg',
                'placeholder' => $_('Inhalt des Eintrags'),
            ]) ?>
        </label>
    </fieldset>

    <fieldset class="multi-checkbox-required">
        <legend><?= $_('Sichtbar für') ?></legend>

    <? foreach ($config as $group => $label): ?>
        <label>
            <input type="checkbox" name="groups[]" value="<?= htmlReady($group) ?>"
                   <? if ($entry->groups->find($group)) echo 'checked'; ?>>
            <?= htmlReady($label) ?>
        </label>
    <? endforeach; ?>
    </fieldset>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept($_('Speichern')) ?>
        <?= Studip\LinkButton::createCancel(
            $_('Abbrechen'),
            URLHelper::getURL('dispatch.php/start')
        ) ?>
    </footer>
</form>
