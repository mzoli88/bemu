<?php

namespace mod\admin\commands;

use Illuminate\Console\Command;
use mod\admin\models\GroupPerms;
use mod\admin\models\UserPerms;
use mod\admin\models\Users;
use mod\notificationcenter\models\Notifications;

class CheckInactiveUsers extends Command
{

    protected $signature = 'CheckInactiveUsers';


    protected $description = 'Check inactive users';


    public function handle(): void
    {
        //Nem aktívált felhasználók türelmi ideje lejárt
        $users_activate_days = getParam('users_activate_days', 'admin');
        if ($users_activate_days != 0 && !empty($users_activate_days)) {
            $users_activate_date = date('Y-m-d', strtotime('-' . ($users_activate_days) . ' days'));
            Users::whereDate('created_at', '<', $users_activate_date)->where('state_id',1)->get()->each(function ($nactive) {
                sendMessage('not_activated', $nactive->email, [
                    'name' => $nactive->name,
                    'login' => $nactive->login,
                    'email' => $nactive->email,
                    'user_id' => $nactive->id,
                ], $nactive->Rel_UserEntities_user()->first()->entity_id, 'admin', true);

                sendMessage('user_not_activated', $nactive->created_by, [
                    'name' => $nactive->name,
                    'login' => $nactive->login,
                    'email' => $nactive->email,
                    'user_id' => $nactive->id,
                ], $nactive->Rel_UserEntities_user()->first()->entity_id, 'admin', true);

                $nactive->delete();

                $message =
                'Módosítás:' . get_class($nactive) .
                    "\n\nVáltozott adatok:\n" . $nactive->formatArraytoString($nactive->getChanges()) .
                    "\nEredeti adatok:\n" . $nactive->formatArraytoString($nactive->getOriginal());
                hwslog(null, 'Felhasználó ütemezett törlése', $message);
            });
        }

        // 5 év után töröljük az inaktivált felhasználókat
        $users_delete_date = date('Y-m-d', strtotime('-5 years'));
        Users::whereDate('updated_at', '<', $users_delete_date)->where('state_id',3)->get()->each(function ($nactive) {
            $nactive->delete();
            $message =
            'Módosítás:' . get_class($nactive) .
                "\n\nVáltozott adatok:\n" . $nactive->formatArraytoString($nactive->getChanges()) .
                "\nEredeti adatok:\n" . $nactive->formatArraytoString($nactive->getOriginal());
            hwslog(null, 'Felhasználó ütemezett törlése 5 év után', $message);
        });

        //40 napnál régebbi Rendszerüzenetek törlése
        $Notifications_delete_date = date('Y-m-d', strtotime('-40 days'));
        Notifications::where('date','<',$Notifications_delete_date)->each(function ($Notification) {
            $Notification->delete();
            $message = 'Törlés:' . get_class($Notification) .
            "\n\nRégi adatok:\n" . $Notification->formatArraytoString($Notification->getOriginal());
            hwslog(null, '40 napnál régebbi rendszerüzenetek törlése', $message);
        });

        //Felhasználó felfüggesztése x nap után
        $password_user_inactive_days = getParam('password_user_inactive_days', 'admin');
        if ($password_user_inactive_days != 0 && !empty($password_user_inactive_days)) {
            $users_login_date = date('Y-m-d', strtotime('-' . (getParam('password_user_inactive_days', 'admin')) . ' days'));
            Users::whereDate('last_login', '<', $users_login_date)
                ->where('state_id', 2)
                ->where('sys_admin', 'N')
                ->get()->each(function ($user) use ($password_user_inactive_days) {
                    sendMessage('user_inactivated', $user, [
                        'name' => $user->name,
                        'login' => $user->login,
                        'email' => $user->email,
                        'user_id' => $user->id,
                    ], $user->Rel_UserEntities_user()->first()->entity_id, 'admin', true);
                    $user->state_id = 7;
                    $user->inactive_date = date('Y-m-d H:i:s');
                    $user->save();

                    $message =
                    'Módosítás:' . get_class($user) .
                        "\n\nVáltozott adatok:\n" . $user->formatArraytoString($user->getChanges()) .
                        "\nEredeti adatok:\n" . $user->formatArraytoString($user->getOriginal());
                    hwslog(null, 'Felhasználó felfüggesztése '.$password_user_inactive_days.' nap után', $message);
                });
        }

        //Minden nap végén törölni az elfelejtett jelszót.
        Users::WhereNotNull('remember_token')
            ->get()->each(function ($user) {
                $user->remember_token = null;
                $user->save();
            });

        //nemlétező jogok törlése
        collect(config('mods'))->map(function($modul){
            if(array_key_exists('perms',$modul) && array_key_exists('menu',$modul)){
                return array_merge(array_keys($modul['perms']),array_keys($modul['menu']));
            }
            if(array_key_exists('perms',$modul)){
                return array_keys($modul['perms']);
            }
            return array_keys($modul['menu']);
        })->each(function($perms,$modul_azon){
            UserPerms::where('modul_azon',$modul_azon)->WhereNotIn('perm',$perms)->get()->each(function($UserPerm){
                $UserPerm->delete();
            });

            GroupPerms::where('modul_azon',$modul_azon)->WhereNotIn('perm',$perms)->get()->each(function($GroupPerms){
                $GroupPerms->delete();
            });
        });
    }
}
