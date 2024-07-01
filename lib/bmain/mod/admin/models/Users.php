<?php

namespace mod\admin\models;

use hws\rmc\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Users extends Model
{
    protected $table = 'admin_users';

    static $entity_property = 'Rel_UserEntities_user.entity_id';

    public $timestamps = false;

    static $allStates = false;

    static $latin = true;

    public function newQuery()
    {
        if (static::$allStates == true) return parent::newQuery();
        return parent::newQuery()->whereIn('state_id', [1, 2, 6, 7]);
    }

    public function defaultCollection()
    {
        $out = $this->toArray();
        if (array_key_exists('sys_admin', $out)) $out['sys_admin'] = $out['sys_admin'] == 'I' ? 'Igen' : 'Nem';
        return $out;
    }

    public function lastloginCollection()
    {
        $out = $this->toArray();
        if (array_key_exists('sys_admin', $out)) $out['sys_admin'] = $out['sys_admin'] == 'I' ? 'Igen' : 'Nem';
        // $out['state_name'] = $this->state == 1 ? 'Aktív' : ($this->state == 2 ? 'Felfüggesztett' : 'Inaktív');
        return $out;
    }

    public function delete()
    {
        $this->login = 'torolt_felhasznalo_' . $this->id;
        $this->name = 'torolt_felhasznalo_' . $this->id;
        $this->email = '-';
        $this->password = null;
        $this->state_id = 4;
        $this->save();
    }

    protected $hidden = [
        'password',
        'password2',
        'current_password',
        'password_confirmation',
        'token',
        'remember_token',
        'api_token',
        'cookie_token',
        'old_passwords',
        'activation_token',
    ];

    protected $fillable = [
        "state_id",
        "name",
        "email",
        //"email_verified_at",
        //"password",
        //"remember_token",
        //"sys_admin",
        //"api_token",
        //"last_login",
        //"cookie_token",
        // "login",
        //"inactive_date",
        //"login_try",
        //"old_passwords",
        //"last_password_modify",
        "activation_token",
        "created_by",
        "inactivation_justification",
        "uuid",
    ];

    protected $casts = [
        "id" => "integer",
        "state_id" => "integer",
        "name" => "string",
        "email" => "string",
        "email_verified_at" => "datetime:Y.m.d. H:i:s",
        "password" => "string",
        "remember_token" => "string",
        "sys_admin" => "string",
        "api_token" => "string",
        "created_at" => "datetime:Y.m.d. H:i:s",
        "updated_at" => "datetime:Y.m.d. H:i:s",
        "last_login" => "datetime:Y.m.d. H:i:s",
        "cookie_token" => "string",
        "login" => "string",
        "inactive_date" => "datetime:Y.m.d. H:i:s",
        "login_try" => "integer",
        "old_passwords" => "string",
        "last_password_modify" => "datetime:Y.m.d. H:i:s",
        "activation_token" => "string",
        "created_by" => "integer",
        "inactivation_justification" => "string",
        "uuid" => "string",
    ];

    protected $validation = [
        "state_id" => "nullable|integer|digits_between:1,11",
        "name" => "required|max:255",
        "email" => "required|max:255",
        "email_verified_at" => "nullable|date",
        "password" => "nullable|max:255",
        "remember_token" => "nullable|max:100",
        "sys_admin" => "nullable|max:1",
        "api_token" => "nullable|max:255",
        "last_login" => "nullable|date",
        "cookie_token" => "nullable|max:100",
        "login" => "required|max:255",
        "inactive_date" => "nullable|date",
        "login_try" => "nullable|integer|digits_between:1,11",
        "old_passwords" => "nullable",
        "last_password_modify" => "nullable|date",
        "activation_token" => "nullable|max:255",
        "created_by" => "nullable|integer|digits_between:1,20",
        "inactivation_justification" => "nullable",
        "uuid" => "nullable|max:255",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "state_id" => "Státusz",
        "name" => "Név",
        "email" => "E-mail",
        "email_verified_at" => "E-mail aktiválás ideje",
        "password" => "Jelszó",
        "remember_token" => "Elfelejtett jelszó token",
        "sys_admin" => "Supervisor",
        "api_token" => "Belépés token",
        "created_at" => "Létrehozás ideje",
        "updated_at" => "Módosítás ideje",
        "last_login" => "Utolsó belépés",
        "cookie_token" => "Süti token",
        "login" => "Felhasználónév",
        "inactive_date" => "Inaktiválás / felfüggesztés időpontja",
        "login_try" => "Bejelentkezési kísérletek száma",
        "old_passwords" => "Régi jelszavak",
        "last_password_modify" => "Utolsó jelszó módosítás ideje",
        "activation_token" => "Token",
        "created_by" => "Létrehozó felhasználó",
        "inactivation_justification" => "Inaktiválás indoklása",
        "uuid" => "UUID",
    ];

    public function checkPassword($password, $noMessage = false)
    {
        $errors = [];

        $password_size = getParam('password_size', 'admin') ?: 0;
        // Minimum hossz
        if ($password_size != 0) {
            if (strlen($password) < $password_size) {
                $errors[] = ('Legalább ' . $password_size . ' db karaktert kell tartalmaznia');
            }
        }

        $password_numeric_size = getParam('password_numeric_size', 'admin') ?: 0;
        //Numerikus karakterek száma
        if ($password_numeric_size != 0) {
            $tmp = preg_replace('/[^0-9]/', '', $password);

            if (strlen($tmp) < $password_numeric_size) {
                $errors[] = ('Legalább ' . $password_numeric_size . ' db numerikus karaktert kell tartalmaznia');
            }
        }

        $password_capital_size = getParam('password_capital_size', 'admin') ?: 0;
        //Nagybetűs karakterek száma
        if ($password_capital_size != 0) {
            $tmp = preg_replace('/[^A-Z]/', '', $password);
            if (strlen($tmp) < $password_capital_size) {
                $errors[] = ('Legalább ' . $password_capital_size . ' db nagybetű karaktert kell tartalmaznia');
            }
        }

        $password_spec_char = getParam('password_spec_char', 'admin') ?: 0;
        //Speciális karakterek száma
        if ($password_spec_char != 0) {
            $tmp = preg_replace('/[\w]/', '', $password);
            if (strlen($tmp) < $password_spec_char) {
                $errors[] = ('Legalább ' . $password_spec_char . ' db speciális karaktert kell tartalmaznia');
            }
        }

        //régi jelszavak ellenörzése
        $password_user_last_paswords = getParam('password_user_last_paswords', 'admin') ?: 0;
        if ($password_user_last_paswords != 0) {

            if (empty($this->old_passwords)) {
                $original = $this->getOriginal();
                if (array_key_exists('password', $original)) {
                    $this->old_passwords = json_encode([$original['password'], $this->password]);
                } else {
                    $this->old_passwords = json_encode([$this->password]);
                }
            } else {
                $oldPWS_arr = json_decode($this->old_passwords, true);
                foreach ($oldPWS_arr as $oldPWS) {
                    if (Hash::check(request()->password, $oldPWS)) {
                        $errors[] = ('Az új jelszó nem egyezhet meg az utolsó ' . $password_user_last_paswords . ' alkalommal megadott jelszóval!');
                        break;
                    }
                }
                $oldPWS_arr[] = $this->password;
                $this->old_passwords = json_encode(array_slice($oldPWS_arr, $password_user_last_paswords * (-1)));
            }
        }

        if (empty($errors)) {
            if ($noMessage == false) {
                sendMessage('password_change', $this, [
                    'name' => $this->name,
                    'login' => $this->login,
                    'email' => $this->email,
                    'user_id' => $this->id,
                ], $this->Rel_UserEntities_user()->first()->entity_id, 'admin', true);
            }
            $this->last_password_modify = date('Y-m-d H:i:s');
        } else {
            sendError(implode("\n", $errors), 400, 'Nem megfelelő jelszó!');
        }
    }

    public function beforeCreate()
    {
        $this->created_at = date('Y-m-d H:i:s');
        $dirty = $this->getDirty();
        if (array_key_exists('password', $dirty) && !app()->runningInConsole()) {
            $this->checkPassword(request()->password, true);
        }
    }

    public function beforeUpdate()
    {
        $dirty = $this->getDirty();
        if ($this->state_id == 4) return;
        if (array_key_exists('password', $dirty) && !app()->runningInConsole()) {
            $this->checkPassword(request()->password);
        }

        //mósosítás dátuma mikor frissüljön
        if (
            array_key_exists('state_id', $dirty) ||
            array_key_exists('password', $dirty) ||
            array_key_exists('email', $dirty) ||
            array_key_exists('name', $dirty)
        ) {
            $this->updated_at = date('Y-m-d H:i:s');
        }


        if (array_key_exists('state_id', $dirty)) {
            if ($dirty['state_id'] == 2) {
                $this->login_try = 0;
                $this->inactive_date = null;
            }
        }


        if (array_key_exists('email', $dirty) && !app()->runningInConsole()) {
            sendMessage('email_change', $this->getOriginal()['email'], [
                'name' => $this->name,
                'login' => $this->login,
                'email' => $dirty['email'],
                'user_id' => $this->id,
            ]);
            sendMessage('email_change', $this, [
                'name' => $this->name,
                'login' => $this->login,
                'email' => $dirty['email'],
                'user_id' => $this->id,
            ]);
        }
    }

    /* ->byPerms(['jog1','jog']) */
    public function scopeByPerms($q, array $perms, $modul_azon = null, $entity_id = null)
    {
        $modul_azon = $modul_azon ?: getModulAzon();
        $entity_id = $entity_id ?: getEntity();

        $q->where(function ($q2) use ($modul_azon, $entity_id, $perms) {
            $q2->orWhereHas('Rel_UserPerms_user', function ($q3) use ($modul_azon, $entity_id, $perms) {
                $q3->where('modul_azon', $modul_azon);
                // $q3->where('entity_id', $entity_id); // régi keretrendszernél nincs
                $q3->whereIn('perm', $perms);
            });

            $q2->orWhereHas('Rel_UserGroups_user', function ($q3) use ($modul_azon, $entity_id, $perms) {
                $q3->whereHas('Rel_group_perms', function ($q4) use ($modul_azon, $entity_id, $perms) {
                    $q4->where('modul_azon', $modul_azon);
                    // $q4->where('entity_id', $entity_id); // régi keretrendszernél nincs
                    $q4->whereIn('perm', $perms);
                });
            });
        });

        return $q;
    }

    /* ->byGroups([1,2]) */
    /* ->byGroups(1) */
    public function scopeByGroups($q, $groups, $modul_azon = null, $entity_id = null)
    {
        $modul_azon = $modul_azon ?: getModulAzon();
        $entity_id = $entity_id ?: getEntity();

        if (is_string($groups) && preg_match('/^\[.*\]$/', $groups)) {
            $groups = json_decode($groups, true);
        }

        if (!is_array($groups)) {
            $groups = [$groups];
        }

        $q->whereHas('Rel_UserGroups_user', function ($q2) use ($groups) {
            $q2->whereIn('group_id', $groups);
        });

        return $q;
    }

    /* ->byEntity([1,2]) */
    /* ->byEntity(1) */
    public function scopeByEntity($query, $entity_id = null)
    {
        if (!hasJustOneEntity()) {
            $entity_id = $entity_id ?: getEntity();

            if (is_string($entity_id) && preg_match('/^\[.*\]$/', $entity_id)) {
                $entity_id = json_decode($entity_id, true);
            }

            if (!is_array($entity_id)) {
                $entity_id = [$entity_id];
            }

            $query->whereExists(function ($query) use ($entity_id) {
                $query->select(DB::raw(1))
                    ->from('admin_user_entities')
                    ->whereColumn('admin_user_entities.user_id', 'admin_users.id')
                    ->whereIn('admin_user_entities.entity_id', $entity_id);
            });
        }
        return $query;
    }


    public function Rel_UserEntities_user()
    {
        return $this->hasMany(UserEntities::class, "user_id", "id");
    }

    public function Rel_UserPerms_user()
    {
        return $this->hasMany(UserPerms::class, "user_id", "id");
    }

    public function Rel_UserGroups_user()
    {
        return $this->hasMany(UserGroups::class, "user_id", "id");
    }

    public function Rel_Notifications_user()
    {
        return $this->hasMany(Notifications::class, "user_id", "id");
    }

    public function Rel_state()
    {
        return $this->belongsTo(UserStates::class, "state_id", "id");
    }
}
