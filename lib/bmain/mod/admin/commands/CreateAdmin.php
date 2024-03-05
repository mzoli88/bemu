<?php

namespace mod\admin\commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Hash;
use mod\admin\models\Entities;
use mod\admin\models\UserEntities;
use mod\admin\models\Users;
use App\Models\User;


class CreateAdmin extends Command
{

    protected $signature = 'createAdmin';


    protected $description = 'Create admin user';


    public function handle()
    {
        Users::$allStates = true;
        $user = new Users();

        $login = strtolower($this->ask('User name (login)'));

        if (!preg_match('/^[a-zA-Z0-9\.\_\-\@]{5,}$/', $login)) {
            return $this->error('Bad login name format!');
        }

        $has_user = User::where('login', $login)->first();
        if ($has_user) return $this->error('Login name already exists!');

        $user->login = $login;

        $user->email = $this->ask('e-mail');
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Bad e-mail format!');
        }
        $user->name = $this->ask('Full name');
        $user->password = Hash::make($this->secret('Password:'));
        $user->sys_admin = 'I';
        $user->created_at = date('Y-m-d H:i:s');

        $entity = Entities::one();

        if (!$entity) {
            $entity = Entities::create([
                'name' => 'Entitás',
                'status' => 'I',
            ]);
        }

        $user->save();

        UserEntities::create([
            'user_id' => $user->id,
            'entity_id' => $entity->id,
        ]);
        $this->info('User created!');
    }
}
