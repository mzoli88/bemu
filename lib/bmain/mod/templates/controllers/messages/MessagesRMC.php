<?php

namespace mod\templates\controllers\messages;

use hws\rmc\Controller3;
use hws\xlsx\XlsxReader;
use Illuminate\Http\Request;
use mod\admin\models\UserGroups;
use mod\templates\models\Messages;
use mod\templates\models\ModulGroups;
use mod\templates\models\ModulUsers;

class MessagesRMC extends Controller3
{

    public $model = Messages::class;

    public $log_event_id = 'Esemény sablonok';

    public $select = [
        '*',
    ];

    public $permissons = [
        'list' => true,
        'view' => true,
        'export' => true,
        'update' => 'edit_events',
        'import' => 'edit_events',
    ];


    public $metaNoUpdate = true;


    public function cols()
    {
        $messageTypes = config('messageTypes');
        collect($messageTypes)->each(function ($value, $key) {
            collect($value)->each(function ($v, $k) use ($key) {
                $message = Messages::query()->where('modul_azon', $key)->where('type', $k)->entity()->first();
                if (!$message) {
                    Messages::create([
                        'entity_id' => getEntity(),
                        'modul_azon' => $key,
                        'modul_name' => getModulName($key),
                        'type' => $k,
                        'message_name' => $v['name'],
                        'addressee' => array_key_exists('addressee', $v) ? $v['addressee'] : null,
                        'email' => (in_array('email', $v['chanels'])) ? 'I' : 'N',
                        'push' => (in_array('push', $v['chanels'])) ? 'I' : 'N',
                        'notification' => (in_array('notification', $v['chanels'])) ? 'I' : 'N',
                    ]);
                } else {
                    $message->modul_name = getModulName($key);
                    $message->message_name = $v['name'];
                    $message->addressee = array_key_exists('addressee', $v) ? $v['addressee'] : null;
                    $message->email = (in_array('email', $v['chanels'])) ? 'I' : 'N';
                    $message->push = (in_array('push', $v['chanels'])) ? 'I' : 'N';
                    $message->notification = (in_array('notification', $v['chanels'])) ? 'I' : 'N';
                    if (!empty($message->getDirty())) $message->save();
                }
            });
        });

        return [
            'modul_name' => [
                'title' => 'Küldő modul',
                'search' => false,
                'update' => false,
            ],
            'modul_azon' => [
                'title' => 'Küldő modul',
                'search' => [
                    'type' => 'combo',
                    'store' => 'moduls',
                    'nosearch' => true,
                ],
                'list' => false,
                'update' => false,
            ],
            'message_name' => [
                'title' => 'Esemény',
                'search' => false,
                'update' => false,
            ],
            'type' => [
                'title' => 'Esemény',
                'list' => false,
                'search' => [
                    'type' => 'combo',
                    'store' => 'notificationtypes',
                    'parent' => 'modul_azon',
                    'nosearch' => true,
                ],
                'update' => false,
            ],
            'addressee' => [
                'list' => true,
                'search' => true,
                'update' => false
            ],
            'variables' => [
                'list' => false,
                'search' => false,
                'update' => false,
                'title' => 'Sablonban megadható változók'
            ],
            "notification_text" => [
                'list' => false,
                'search' => false,
                'update' => false
            ],
            "email_subj" => [
                'list' => false,
                'search' => false,
                'title' => 'E-mail tárgy',
                'update' => false
            ],
            "email_ct" => [
                'list' => false,
                'search' => false,
                'title' => 'E-mail szöveg',
                'update' => false
            ],
            "push_subj" => [
                'list' => false,
                'search' => false,
                'update' => false
            ],
            "push_text" => [
                'list' => false,
                'search' => false,
                'update' => false
            ],
            'email' => [
                'list' => true,
                'search' => false,
                'update' => false,
            ],
            'push' => [
                'list' => true,
                'search' => false,
                'update' => false,
            ],
            'notification' => [
                'list' => true,
                'search' => false,
                'update' => false,
            ],

        ];
    }

    public function list()
    {
        $out = $this->defaultList()->entity();
        if (!config('isBorder')) {
            $moduls = [];
            $groups = UserGroups::where('user_id', getUserId())->join('Rel_group')->where('Rel_group.status', 'I')->get()->pluck('group_id');
            ModulGroups::entity()->whereIn('group_id', $groups)->pluck('modul_azon')->each(function ($v) use (&$moduls) {
                if (!in_array($v, $moduls)) $moduls[] = $v;
            });
            ModulUsers::entity()->where('user_id', getUserId())->pluck('modul_azon')->each(function ($v) use (&$moduls) {
                if (!in_array($v, $moduls)) $moduls[] = $v;
            });

            if (isSysAdmin() && !in_array('admin', $moduls)) $moduls[] = 'admin';
            $out->whereIn('modul_azon', $moduls);
        }

        return $out;
    }

    public function view()
    {
        return $this->defaultView()->collect('view');
    }

    public function update(Request $request, $id)
    {
        return $this->defaultUpdate();
    }

    public function export()
    {
        return $this->defaultExport('sablonok_' . getEntityName(), [
            'modul_azon' => 'Modul azonosító',
            'type' => 'Esemény',
            'notification_text' => 'Rendszer üzenet',
            'email_subj' => 'E-mail tárgy',
            'email_ct' => 'E-mail szöveg',
            'push_text' => 'Push szöveg',
            'push_subj' => 'Push tárgy',
        ]);
    }

    public function import(Request $request)
    {
        (new XlsxReader())->import(
            function ($rowdata) {
                $model = Messages::where('entity_id', getEntity())
                    ->where('modul_azon', $rowdata['modul_azon'])
                    ->where('type', $rowdata['type'])
                    ->one();

                if (!$model) return;

                if (!empty($rowdata['notification_text'])) $model->notification_text = $rowdata['notification_text'];
                if (!empty($rowdata['email_subj'])) $model->email_subj = $rowdata['email_subj'];
                if (!empty($rowdata['email_ct'])) $model->email_ct = $rowdata['email_ct'];
                if (!empty($rowdata['push_text'])) $model->push_text = $rowdata['push_text'];
                if (!empty($rowdata['push_subj'])) $model->push_subj = $rowdata['push_subj'];

                $model->save();
            },
            [
                'modul_azon' => 'Modul azonosító',
                'type' => 'Esemény',
                'notification_text' => 'Rendszer üzenet',
                'email_subj' => 'E-mail tárgy',
                'email_ct' => 'E-mail szöveg',
                'push_text' => 'Push szöveg',
                'push_subj' => 'Push tárgy',
            ]
        );
    }
}
