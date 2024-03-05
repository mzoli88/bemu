<?php

namespace mod\admin\controllers\documents;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Documents;

class DocumentsRMC extends Controller3
{

    public $model = Documents::class;

    public $log_event_id = 'Dokumentumok';

    public $permissons = [
        'list' => true,
        'download' => true,
        'create' => 'document_admin',
        'update' => 'document_admin',
        'delete' => 'document_admin',
    ];

    public $useFileUpload = [
        'application/pdf' => ['pdf'],
    ];

    public function cols()
    {
        $mods = collect(config('mods'))->sortBy('name')->map(function ($r, $modul_azon) {
            return [
                'name' => $r['name'],
                'value' =>  $modul_azon,
            ];
        })->values()
            ->toArray();

        return [
            'modul' => [
                'title' => 'Modul',
                'cu' => false,
                'search' => false,
            ],
            'modul_azon' => [
                'list' => false,
                'cu' => [
                    'type' => 'combo',
                    'boxes' => $mods
                ],
                'search' => [
                    'type' => 'combo',
                    'boxes' => $mods
                ]
            ],
            'name' => true,
            'file_name' => true,
        ];
    }

    public $select = [
        '*',
    ];

    public function list()
    {
        $out = $this->defaultList();
        if (!hasPerm('document_admin')) {
            $perms = getUserPerms();
            $entity_id = getEntity();
            if (!array_key_exists($entity_id, $perms)) return [];
            $moduls = collect($perms[$entity_id])->keys()->toArray();
            if (isSysAdmin()) $moduls[] = 'admin';
            $out->whereIn('modul_azon', $moduls);
        }
        return $out;
    }

    public function create()
    {
        return $this->defaultCreate();
    }

    public function update(Request $request, $id)
    {
        return $this->defaultUpdate();
    }

    public function download(Request $request, $id)
    {
        return $this->defaultDownload();
    }

    public function delete(Request $request, $id)
    {
        return $this->defaultDelete();
    }
}
