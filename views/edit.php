<form action="<?= htmlReady($action) ?>" method="post" class="sitenews-editor studip_form">
    <fieldset>
        <legend class="hide-in-dialog"><?= _('Inhalte bearbeiten') ?></legend>

        <fieldset>
            <label for="title"><?= _('Titel') ?></label>
            <input type="text" name="title" id="title" value="<?= htmlReady($title) ?>">
        </fieldset>
        
        <fieldset>
            <label for="content"><?= _('Inhalt') ?></label>
            <textarea name="content" id="content" class="add_toolbar"><?= htmlReady($content) ?></textarea>
        </fieldset>
    </fieldset>

    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $cancel) ?>
    </div>
</form>