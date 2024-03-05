<?php

namespace mod\admin\controllers\permissions;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\GroupPerms;
use mod\admin\models\UserPerms;
use mod\admin\models\Users;

class PermissionsRMC extends Controller3
{

    public $log_event_id = 'Jogosultságok szerepkör/jogosultság szerint';

    public $listCollection = "statistic2";


    public $select = [
        'perm_name',
        'perm_type',
        'modul_name',
        // 'name' => 'Rel_user.name',
    ];


    function cols()
    {
        return [
            'modul_name' => [
                'title' => 'Modul neve',
                'sortable' => false,
                'list' => true,
                'search' => false
            ],
            'modul_azon' => [
                'title' => 'Modul',
                'list' => false,
                'search' => [
                    'type' => 'combo',
                    'store' => 'moduls'
                ]
            ],
            'perm_name' => [
                'title' => 'Jogosultság neve',
                'sortable' => false,
                'list' => true,
                'search' => [
                    'type' => 'multicombo',
                    'name' => 'perm',
                    'parent' => 'modul_azon',
                    'store' => 'permscombo'
                ]
            ],
            'perm_type' => [
                'sortable' => false,
                'list' => true,
                'title' => 'Jogosultság típus',
                'search' => false,
            ],
            'user_name' => [
                'title' => 'Érintett felhasználók - Státuszok',
                'sortable' => false,
                'list' => true,
                'search' => [
                    'type' => 'text',
                    'label' => 'Érintett felhasználó neve'
                ]
            ],
        ];
    }

    public function list(Request $request)
    {
        $modul_azons = array_keys(config('mods'));

        $users = [];
        if ($request->filled('user_name')) {
            Users::$allStates = true;
            $users = Users::select(['id', 'user_name' => [' - ', 'name', 'login']])
                ->whereIn('state_id', [1, 2, 5, 6, 7])
                ->entity()
                ->search()
                ->get()->pluck('id')->toArray();
        }

        $UserPerms = UserPerms::entity()
            ->select([
                "modul_azon",
                "perm",
            ])
            ->whereIn('modul_azon', $modul_azons)
            ->search([
                'user_name' => function ($q) use ($users) {
                    $q->whereIn('user_id', $users);
                }
            ]);

        $groupUserPerms = GroupPerms::entity()
            ->select([
                "modul_azon",
                "perm",
            ])
            ->whereIn('modul_azon', $modul_azons)
            ->join('Rel_user_group')
            ->groupBy('modul_azon', 'perm')
            ->search([
                'user_name' => function ($q) use ($users) {
                    $q->whereIn('Rel_user_group.user_id', $users);
                }
            ]);

        $groupUserPerms->union($UserPerms);

        return $groupUserPerms;
    }


    public function export(Request $request)
    {
        return $this->defaultExport('jogosultsagok_' . date('YmdHis') . 'xlsx', [
            'modul_name' => 'Modul neve',
            'perm_name' => 'Jogosultság neve',
            'perm_type' => 'Jogosultság típusa',
            'user_name' => 'Felhasználók',
        ], false, 'statistic2export', true, true);
    }
}
