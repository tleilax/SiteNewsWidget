<?php
namespace SiteNews;

use DBManager;

/**
 * @property string $id
 * @property string $group_id
 * @property \I18NString $name
 * @property int $position
 *
 * @property GroupRole[]|\SimpleCollection $roles
 * @property Entry[] $entries
 */
class Group extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'sitenews_groups';

        $config['has_many'] = [
            'roles' => [
                'class_name'        => GroupRole::class,
                'assoc_foreign_key' => 'group_id',
                'on_delete'         => 'delete',
                'on_store'          => 'store',
            ],
        ];
        $config['has_and_belongs_to_many'] = [
            'entries' => [
                'class_name'     => Entry::class,
                'thru_table'     => 'sitenews_entries_groups',
                'thru_key'       => 'group_id',
                'thru_assoc_key' => 'news_id',
                'on_delete'      => 'delete',
                'on_store'       => 'store',
            ],
        ];

        $config['i18n_fields'] = [
            'name' => true,
        ];

        $config['registered_callbacks'] = [
            'before_create' => [
                function (Group $group) {
                    if (!$group->position) {
                        $query = "SELECT MAX(`position`) FROM `sitenews_groups`";
                        $group->position = 1 + (int) DBManager::get()->fetchColumn($query);
                    }
                },
            ],
            'before_store' => [
                function (Group $group) {
                    if (!$group->isNew() && $group->isFieldDirty('id')) {
                        $group->roles->each(function (GroupRole $role) use ($group) {
                            $role->group_id = $group->id;
                            $role->store();
                        });
                    }
                },
            ],
        ];

        parent::configure($config);
    }

    public static function findAll(): array
    {
        return self::findBySQL('1 ORDER BY position');
    }

    public static function findFirst(): Group
    {
        return self::findOneBySQL('1 ORDER BY position');
    }

    public function hasRole($id): bool
    {
        if ($id instanceof \Role) {
            $id = $id->getRoleid();
        }
        return $this->roles->findOneBy('role_id', $id) !== null;
    }

    public function setRoles(array $role_ids): void
    {
        GroupRole::deleteBySQL('group_id = ?', [$this->id]);

        foreach ($role_ids as $role_id) {
            GroupRole::create([
                'group_id' => $this->id,
                'role_id'  => $role_id,
            ]);
        }

        $this->resetRelation('roles');
    }

    /**
     * @param callable $callback
     *
     * @return \User[]
     */
    public function eachUser(callable $callback): array
    {
        $condition = "LEFT JOIN `roles_user` -- Explicit assignment
                        ON `roles_user`.`userid` = `auth_user_md5`.`user_id`
                      -- Implicit assignment
                      LEFT JOIN `roles_studipperms`
                        ON `roles_studipperms`.`permname` = `auth_user_md5`.`perms`
                      -- Group roles
                      LEFT JOIN `sitenews_groups_roles`
                        ON `sitenews_groups_roles`.`role_id` IN (
                            `roles_user`.`roleid`,
                            `roles_studipperms`.`roleid`
                        )
                      WHERE `sitenews_groups_roles`.`group_id` = ?
                      GROUP BY `auth_user_md5`.`user_id`";
        return \User::findAndMapBySQL($callback, $condition, [$this->id]);
    }
}
