<?php
/**
 * @var SiteNewsWidget $controller
 * @var callable $_
 * @var I18NString $title
 * @var SiteNews\Group[] $groups
 * @var Role[] $roles
 */
?>
<form action="<?= $controller->link_for('config') ?>" method="post" class="default">
    <fieldset>
        <legend><?= $_('Einstellungen bearbeiten') ?></legend>

        <label>
            <?= $_('Titel') ?>
            <?= I18N::input('title', $title, [
                'placeholder' => $_('In eigener Sache'),
            ]) ?>
        </label>
    </fieldset>

    <fieldset>
        <legend><?= $_('Gruppen verwalten') ?></legend>

        <table class="default group-administration">
            <colgroup>
                <col style="width: 12px">
                <col style="width: 150px">
                <col>
                <col style="width: 300px">
                <col style="width: 24px">
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th><?= $_('ID') ?></th>
                    <th><?= $_('Name') ?></th>
                    <th><?= $_('Rollen') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <? foreach ($groups as $group): ?>
                <tr>
                    <td>
                        <?= Assets::img('anfasser_24.png') ?>
                    </td>
                    <td>
                        <input required type="text"
                               name="groups[<?= htmlReady($group->id) ?>][id]"
                               value="<?= htmlReady($group->id) ?>"
                               pattern="^\w+$">
                    </td>
                    <td>
                        <?= I18N::input("groups_{$group->id}_name", $group->name, [
                            'required' => '',
                        ]) ?>
                    </td>
                    <td>
                        <select required name="groups[<?= htmlReady($group->id) ?>][roles][]" multiple class="nested-select">
                        <? foreach ($roles as $role): ?>
                            <option value="<?= htmlReady($role->getRoleid()) ?>" <? if ($group->hasRole($role)) echo 'selected'; ?>>
                                <?= htmlReady($role->getRolename()) ?>
                            </option>
                        <? endforeach; ?>
                        </select>
                    </td>
                    <td class="actions">
                        <?= Icon::create('trash')->asInput(tooltip2($_('Gruppe löschen')) + [
                            'class'        => 'old-row',
                            'formaction'   => $controller->url_for('delete_group', ['id' => $group->id]),
                            'data-confirm' => $_('Wollen Sie die Gruppe wirklich löschen?'),
                            'data-dialog'  => 'size=auto',
                        ]) ?>

                        <input type="hidden" value="<?= htmlReady($group->position) ?>"
                               name="groups[<?= htmlReady($group->id) ?>][position]">
                    </td>
                </tr>
            <? endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">
                        <?= Studip\Button::create($_('Neue Gruppe anlegen'), 'new-group') ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </fieldset>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept($_('Speichern')) ?>
    </footer>
</form>

<script type="text/x-template" id="new-group-row">
<tr>
    <td>
        <?= Assets::img('anfasser_24.png') ?>
    </td>
    <td>
        <input required type="text"
               name="groups[#{new-id}][id]"
               value=""
               pattern="^\w+$">
    </td>
    <td>
        <?= I18N::input("groups_#{new-id}_name", (new SiteNews\Group())->name, [
            'required' => '',
        ]) ?>
    </td>
    <td>
        <select required name="groups[#{new-id}][roles][]" multiple class="nested-select">
        <? foreach ($roles as $role): ?>
            <option value="<?= htmlReady($role->getRoleid()) ?>">
                <?= htmlReady($role->getRolename()) ?>
            </option>
        <? endforeach; ?>
        </select>
    </td>
    <td class="actions">
        <?= Icon::create('trash')->asInput(tooltip2($_('Gruppe löschen')) + [
            'class'        => 'new-row',
            'data-confirm' => $_('Wollen Sie die Gruppe wirklich löschen?'),
        ]) ?>

        <input type="hidden" value="#{position}"
               name="groups[#{new-id}][position]">
    </td>
</tr>
</script>
