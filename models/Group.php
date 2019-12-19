<?php
namespace SiteNews;

use DBManager;
use PDO;

class Group extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'sitenews_groups';

        $config['has_many']['roles'] = [
            'class_name'        => GroupRole::class,
            'assoc_foreign_key' => 'group_id',
            'on_delete'         => 'delete',
            'on_store'          => 'store',
        ];
        $config['has_and_belongs_to_many']['entries'] = [
            'class_name'     => Entry::class,
            'thru_table'     => 'sitenews_entries_groups',
            'thru_key'       => 'group_id',
            'thru_assoc_key' => 'news_id',
            'on_delete'      => 'delete',
            'on_store'       => 'store',
        ];

        $config['i18n_fields']['name'] = true;

        $config['registered_callbacks']['before_create'][] = function (Group $group) {
            if (!$group->position) {
                $query = "SELECT MAX(`position`) FROM `sitenews_groups`";
                $group->position = 1 + DBManager::get()->fetchColumn($query);
            }
        };

        $config['registered_callbacks']['before_store'][] = function (Group $group) {
            if (!$group->isNew() && $group->isFieldDirty('id')) {
                $group->roles->each(function (GroupRole $role) use ($group) {
                    $role->group_id = $group->id;
                    $role->store();
                });
            }
        };

        parent::configure($config);
    }

    public static function findAll()
    {
        return self::findBySQL('1 ORDER BY position ASC');
    }

    public static function findFirst()
    {
        return self::findOneBySQL('1 ORDER BY position ASC');
    }

    public function hasRole($id)
    {
        if ($id instanceof \Role) {
            $id = $id->getRoleid();
        }
        return $this->roles->findOneBy('role_id', $id) !== null;
    }

    public function setRoles(array $role_ids)
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

    public function eachUser(Callable $callback)
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
