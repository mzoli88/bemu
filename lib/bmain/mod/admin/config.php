<?php
return [

    'name' => 'Rendszer',
    'icon' => 'f013',
    'sort' => 999,

    'version' => '8.0.0',

    'db_prefix' => ['admin_'],

    'perms' => [
        'badmin' => 'Border admin',
    ],

    // 'permsInfo' => [
    //     'document_admin' => 'Dokumentumok menüponton belül rögzíthet, módosíthat, törölhet és látja az összes dokumentumot.',
    //     'users_admin' => 'Felhasználó kezelés menüponton belül rögzíthet, módosíthat, szerepköröket és jogosultságokat nem állíthat be, a felhasználó e-mail címét, nevét, státuszát módosíthatja.',
    //     'group_admin' => 'Szerepkör kezelés menüponton belül rögzíthet, módosíthat, felhasználókat és jogosultságokat állíthat be. Felhasználó kezelés menüponton belül szerepköröket állíthat be',
    //     'entity_admin' => 'Entitás kezelés menüponton belül felhaszálókat állíthat be.',
    //     'user_perms' => 'Jogosultságot állíthat be a felhasználóhoz.',
    // ],

    'menu' => [
        'passwordchange' => [
            'icon' => 'f084',
            'name' => 'Jelszó módosítás',
        ],
        'documents' => [
            'icon' => 'f1c1',
            'name' => 'Dokumentumok',
        ],
        'useredit' => [
            'icon' => 'f007',
            'name' => 'Felhasználó kezelés',
        ],
        'permissions' => [
            'icon' => 'f2bb',
            'name' => 'Jogosultságok',
        ],
        'groupedit' => [
            'icon' => 'f5fd',
            'name' => 'Szerepkör kezelés',
        ],
        'entityedit' => [
            'icon' => 'f247',
            'name' => 'Entitás kezelés',
        ],
        'systemparam' => [
            'icon' => 'f7d9',
            'name' => 'Rendszer beállítások',
        ],

    ]
];
