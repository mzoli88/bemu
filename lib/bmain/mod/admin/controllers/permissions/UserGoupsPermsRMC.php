<?php

namespace mod\admin\controllers\permissions;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mod\admin\models\GroupPerms;
use mod\admin\models\UserPerms;

class UserGoupsPermsRMC extends Controller3
{
    public $listCollection = "statistic";

    public $log_event_id = 'Jogosultságok felhasználók szerint';

    public $select = [
        '*',
        'name' => 'Rel_user.name',
        'login' => 'Rel_user.login',
        'state' => 'Rel_user.Rel_state.state',
        'group_name' => 'Rel_group.name',
    ];


    function cols()
    {
        return [
            'name' => [
                'list' => true,
                'search' => true
            ],
            'login' => [
                'list' => true,
                'search' => true
            ],
            'state' => [
                'list' => true,
                'search' => false
            ],
            'state_id' => [
                'title' => 'Státusz',
                'list' => false,
                // 'search' => false,
                'search' => [
                    'type' => 'chgroup',
                    'store' => 'userstates'
                ]
            ],
            'modul_name' => [
                'title' => 'Modul',
                'list' => true,
                'sortable' => false,
                'search' => false
            ],
            'modul_azon' => [
                'title' => 'Modul',
                'list' => false,
                'sortable' => false,
                'search' => [
                    'type' => 'combo',
                    'store' => 'moduls'
                ]
            ],
            'perm_type' => [
                'list' => true,
                'title' => 'Jogosultság típus',
                'sortable' => false,
                'search' => false,
                // [
                //     'type' => 'chgroup',
                //     'boxes' => [
                //         ['name' => 'Elemi jog', 'value' => 'Elemi jog'],
                //         ['name' => 'Menüpont', 'value' => 'Menüpont'],
                //     ]
                // ]
            ],
            'perm_name' => [
                'title' => 'Jogosultság',
                'sortable' => false,
                'search' => [
                    'type' => 'multicombo',
                    'name' => 'perm',
                    'parent' => 'modul_azon',
                    'store' => 'permscombo'
                ]
            ],
            // 'perm' => [
            //     "sortable" => false,
            //     'title' => 'Jogosultság',
            // ],
            'perm_origin' => [
                'title' => 'Származás',
                'sortable' => false,
                'search' => [
                    'type' => 'combo',
                    'boxes' => [
                        ['name' => 'Közvetlen', 'value' => 'I'],
                        ['name' => 'Közvetett', 'value' => 'N'],
                    ]
                ]
            ],
            'group_name' => [
                'title' => 'Szerepkör',
                'list' => true,
                'sortable' => false,
                'search' => false
            ],
            'group_id' => [
                'title' => 'Szerepkör',
                'list' => false,
                'search' => [
                    'type' => 'multicombo',
                    'store' => 'groups'
                ]
            ],
        ];
    }

    public function list(Request $request)
    {

        $modul_azons = array_keys(config('mods'));

        $UserPerms = UserPerms::entity()
            ->select([
                "modul_azon",
                "perm",
                "user_id",
                "name" => "Rel_user.name",
                "login" => "Rel_user.login",
                "state_id" => "Rel_user.state_id",
                "state" => "Rel_user.Rel_state.state",
            ])
            ->whereIn('state_id', [1, 2, 5, 6, 7])
            ->whereIn('modul_azon', $modul_azons)
            ->search([]);


        $groupUserPerms = GroupPerms::entity()
            ->select([
                "modul_azon",
                "perm",
                "user_id" => "Rel_user_group.user_id",
                "name" => "Rel_user_group.Rel_user.name",
                "login" => "Rel_user_group.Rel_user.login",
                "state_id" => "Rel_user_group.Rel_user.state_id",
                "state" => "Rel_user_group.Rel_user.Rel_state.state",
            ])
            ->whereIn('modul_azon', $modul_azons)
            ->join('Rel_user_group')
            ->groupBy('modul_azon', 'perm', 'user_id')
            ->whereIn('state_id', [1, 2, 5, 6, 7])
            ->search([
                'group_id' => function ($q1, $values) {
                    $q1->whereHas('Rel_user_group', function ($q2) use ($values) {
                        $q2->whereIn('group_id', $values);
                    });
                },
            ]);

        if ($request->filled('perm_origin')) {
            if ($request->perm_origin == 'I') {
                return $UserPerms->sort();
            } else {
                $groupUserPerms->whereNotExists(function ($query) {

                    $query->select(DB::raw(1))
                        ->from('admin_user_perms')
                        ->whereColumn('admin_user_perms.modul_azon', 'admin_group_perms.modul_azon')
                        ->whereColumn('admin_user_perms.perm', 'admin_group_perms.perm')
                        ->whereColumn('admin_user_perms.entity_id', 'admin_group_perms.entity_id')
                        ->whereColumn('admin_user_perms.user_id', 'Rel_user_group.user_id');
                });
                $groupUserPerms->sort([
                    'name' => 'name',
                    'state' => 'state',
                    'login' => 'login',
                ]);
                return $groupUserPerms;
            }
        }

        if (!$request->filled('group_id')) $groupUserPerms->union($UserPerms);

        $groupUserPerms->sort([
            'name' => 'name',
            'state' => 'state',
            'login' => 'login',
        ]);
        return $groupUserPerms;
    }


    public function export()
    {
        return $this->defaultExport('jogosultsagok_felhasznalok_' . date('YmdHis') . 'xlsx', [], false, 'statistic', true, true);
    }
}
