<form action="<?= $controller->url_for('settings', $entry->id) ?>" method="post" class="sitenews-editor default">
    <fieldset>
        <legend class="hide-in-dialog"><?= _('Einstellungen bearbeiten') ?></legend>

        <fieldset>
            <label for="title"><?= _('Titel') ?></label>
            <input required type="text" name="title" id="title" value="<?= htmlReady($title) ?>" placeholder="<?= _('In eigener Sache') ?>">
        </fieldset>
    </fieldset>

    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), URLHelper::getLink('dispatch.php/start')) ?>
    </div>
</form>