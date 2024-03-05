<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\UserEntities;
use mod\admin\models\Users;

class UsersRMC extends Controller3
{
    public $model = Users::class;

    public $log_event_id = 'Felhasználó';

    public $permissons = [
        'list' => true,
        'view' => true,
        'create' => ['SysAdmin', 'users_admin'],
        'update' => ['SysAdmin', 'users_admin'],
        'export' => ['SysAdmin', 'users_admin'],
    ];
    public $metaNoUpdate = true;

    public $defaultSearch = [
        'state_id' => [1, 2, 5, 6, 7]
    ];

    public $select = [
        'id',
        'login',
        'name',
        'email',
        'sys_admin',
        'created_at',
        'last_login',
        'inactive_date',
        'state_id',
        'state_name' => 'Rel_state.state',
        'updated_at',
        'inactivation_justification'
    ];

    public $metaList = [
        'sys_admin' => false,
    ];

    public function cols()
    {
        return [
            'login' => [
                'create' => true,
                'update' => false,
            ],
            'name' => [
                'update' => false,
            ],
            'email' => [
                'create' => true,
                'validation' => 'email|required',
                'update' => false,
            ],
            'sys_admin' => [
                'list' => hasPerm('SysAdmin'),
                'search' => hasPerm('SysAdmin') ? [
                    'type' => 'cigennem',
                ] : false,
                'update' => false,
                'details' => hasPerm('SysAdmin'),
                // 'update' => hasPerm('SysAdmin') ? [
                //     'type' => 'rigennem',
                //     'startValue' => 'N',
                // ] : false,
            ],
            'state_name' => [
                'title' => 'Státusz',
                'create' => false,
                'update' => false,
                'search' => false,
                'list' => true
            ],
            'state_id' => [
                'list' => false,
                'search' => [
                    'title' => 'Státusz',
                    'type' => 'checkboxgroup',
                    'store' => 'userstatescombo',
                ],
                'create' => false,
                'update' => false,
            ],
            'created_at' => [
                'title' => 'Létrehozás dátuma'
            ],
            'last_login' => [
                'title' => 'Utolsó belépés dátuma'
            ],
            'updated_at' => [
                'title' => 'Utolsó módosítás dátuma',
                'cu' => false,
            ]
        ];
    }

    public function create(Request $request)
    {
        Users::$allStates = true;
        $login = strtolower($request->login);
        if (Users::findOne('login', $login)) {
            return response()->json([
                'login' => 'Ezzel az felhasználónévvel (login) már van regisztrálva felhasználó!'
            ], 422);
        }

        if (!preg_match('/^[a-zA-Z0-9\.\_\-\@]{5,}$/', $login)) {
            return response()->json([
                'login' => 'A felhasználónév (login) nem megfelelő! Minimum 5 karaktert kell tartalmaznia. Speciális karakterek közül csak a következőket tartalmazhatja: . _ - @ )'
            ], 422);
        }

        if (!hasTemplate('user_activate')) sendError("Felhasználó aktiválás email sablon nincs paraméterezve!");

        $model = $this->model::make();

        $model->fill($request->all());

        $model->state_id = 1;   // Aktiválásra váró
        $token = uniqid();
        $model->activation_token = $token;
        $model->login = $login;
        $model->created_by = getUserId();
        $model->save();

        UserEntities::create([
            'entity_id' => getEntity(),
            'user_id' => $model->id
        ]);

        sendMessage('user_activate_login', $model->email, [
            'name' => $model->name,
            'login' => $model->login,
            'email' => $model->email,
            'user_id' => $model->id,
        ]);

        sendMessage('user_activate', $model->email, [
            'token' => $token,
            'name' => $model->name,
            'email' => $model->email,
            'user_id' => $model->id,
            'link' => '<a href="' . config('app.url') . '#activate|' . $token . '" >Felhasználó aktiválás</a>'
        ]);

        sendMessage('user_activated_email', getUser(), [
            'name' => $model->name,
            'login' => $model->login,
            'email' => $model->email,
            'user_id' => $model->id,
        ]);

        return ['success' => true];
    }


    public function list()
    {
        Users::$allStates = true;
        return $this->defaultList(null, null, 'lastlogin')->entity();
    }

    public function view()
    {
        Users::$allStates = true;
        return $this->defaultView();
    }

    public function update(Request $request, $id)
    {
        Users::$allStates = true;
        if ($id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');

        $model = Users::find($id);
        if (!$model) sendError('A rekord nem található!');
        // $model->fill($request->all());
        // $model->validate();
        $data = $model->validate();
        $model->fill($data);

        if (isSysAdmin() && $request->has('sys_admin')) {
            $model->sys_admin = $request->sys_admin;
        }

        $dirty = $model->getDirty();

        if (array_key_exists('state_id', $dirty)) {

            if ($dirty['state_id'] == 4 && $model->getOriginal()['state_id'] == 1) {

                sendMessage('not_activated', $model->email, [
                    'name' => $model->name,
                    'login' => $model->login,
                    'email' => $model->email,
                    'user_id' => $model->id,
                ], $model->Rel_UserEntities_user()->first()->entity_id, 'admin', true);

                return $model->delete();
            }

            // Inaktiválás
            if ($dirty['state_id'] == 3) {
                $model->inactive_date = date('Y-m-d H:i:s');
                $model->state_id = 3;
                sendMessage('user_state_inactivated', $model, [
                    'name' => $model->name,
                    'login' => $model->login,
                    'email' => $model->email,
                    'user_id' => $model->id,
                ]);
            }

            // Szüneteltett
            if ($dirty['state_id'] == 5) {
                $model->inactive_date = null;
                $model->state_id = 5;
                sendMessage('user_state_szunet', $model, [
                    'name' => $model->name,
                    'login' => $model->login,
                    'email' => $model->email,
                    'user_id' => $model->id,
                ]);
            }

            // Aktiválás
            if ($dirty['state_id'] == 2) {
                $model->inactive_date = null;
                $model->state_id = 2;

                switch ($model->getOriginal()['state_id']) {
                    case 7:
                        sendMessage('user_lift_suspension', $model, [
                            'name' => $model->name,
                            'login' => $model->login,
                            'email' => $model->email,
                            'user_id' => $model->id,
                        ]);
                        break;
                    case 6:
                        sendMessage('user_lift_lock', $model, [
                            'name' => $model->name,
                            'login' => $model->login,
                            'email' => $model->email,
                            'user_id' => $model->id,
                        ]);
                        break;
                    case 5:
                        sendMessage('user_state_activated', $model, [
                            'name' => $model->name,
                            'login' => $model->login,
                            'email' => $model->email,
                            'user_id' => $model->id,
                        ]);
                        break;
                }
            }
        }
        $model->save();
        return [];
    }

    public function export()
    {
        $headers = [];
        if (hasPerm('SysAdmin')) {
            $headers = [
                'login' => 'Felhasználónév',
                'name' => 'Név',
                'email' => 'E-mail',
                'sys_admin' => 'Supervisor',
                'state_name' => 'Státusz',
                'created_at' => 'Létrehozás dátuma',
                'last_login' => 'Utolsó belépés dátuma',
                'updated_at' => 'Utolsó módosítás dátuma',
            ];
        } else {
            $headers = [
                'login' => 'Felhasználónév',
                'name' => 'Név',
                'email' => 'E-mail',
                'state_name' => 'Státusz',
                'created_at' => 'Létrehozás dátuma',
                'last_login' => 'Utolsó belépés dátuma',
                'updated_at' => 'Utolsó módosítás dátuma',
            ];
        }

        return $this->defaultExport('felhasznalok_' . date('YmdHis') . '.xlsx', $headers, $this->list());
    }
}
