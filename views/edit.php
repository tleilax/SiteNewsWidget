<form action="<?= $controller->url_for("store/{$entry->id}") ?>" method="post" class="sitenews-editor default">
    <fieldset>
        <legend class="hide-in-dialog"><?= $_('Inhalte bearbeiten') ?></legend>

        <fieldset class="multi-checkbox-required">
            <label for="visibility"><?= $_('Sichtbar für') ?></label>
        <? foreach ($controller->config as $status => $config): ?>
            <label>
                <input type="checkbox" name="visibility[]" value="<?= htmlReady($status) ?>" <? if ($entry->isVisibleForPerm($status)) echo 'checked'; ?>>
                <?= htmlReady($config['label']) ?>
            <? if ($status === 'autor'): ?>
                <?= tooltipIcon($_('Gäste können nicht einzeln angesprochen werden. Die Einträge sind immer auch für Studenten sichtbar.')) ?>
            <? endif; ?>
            </label>
        <? endforeach; ?>
        </fieldset>

        <fieldset>
            <label for="expires"><?= $_('Anzeigen bis') ?></label>
            <input type="text" name="expires" class="has-datepicker" value="<?= date('d.m.Y', $entry->expires ?: time()) ?>">
        </fieldset>

        <fieldset>
            <label for="subject"><?= $_('Titel') ?></label>
            <input required type="text" name="subject" id="subject" value="<?= htmlReady($entry->subject) ?>" placeholder="<?= $_('Titel des Eintrags') ?>">
        </fieldset>

        <fieldset>
            <label for="content"><?= $_('Inhalt') ?></label>
            <textarea required name="content" id="content" class="add_toolbar" data-secure placeholder="<?= $_('Inhalt des Eintrags') ?>"><?= htmlReady($entry->content) ?></textarea>
        </fieldset>

    </fieldset>

    <div data-dialog-button>
        <?= Studip\Button::createAccept($_('Speichern')) ?>
        <?= Studip\LinkButton::createCancel($_('Abbrechen'), URLHelper::getLink('dispatch.php/start')) ?>
    </div>
</form>
