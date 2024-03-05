<?php
return [
    // 1
    'user_activate_login' => [
        'name' => 'Felhasználó létrehozása',
        'chanels' => ['email'],
        'addressee' => 'Érintett, létrehozott felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ]
    ],
    'user_activate' => [
        'name' => 'Felhasználó aktiváló e-mail kiküldése',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'token' => 'Aktiváló token',
            'name' => 'Név',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
            'link' => 'Felhasználó aktiválás linkje'
        ]
    ],
    'user_activated_email' => [
        'name' => 'Felhasználó aktiváló e-mail kiküldése',
        'chanels' => ['notification'],
        'addressee' => 'Beállítást végző felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
            'link' => 'Felhasználó részleteinek linkje',
        ],
    ],
    'activated' => [
        'name' => 'Felhasználó aktiválta magát',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_activated' => [
        'name' => 'Felhasználó aktiválta magát',
        'chanels' => ['notification', 'email'],
        'addressee' => 'Beállítást végző felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
            'link' => 'Felhasználó részleteinek linkje',
        ],
    ],
    'user_state_inactivated' => [
        'name' => 'Felhasználó inaktiválása',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'forgot_password' => [
        'name' => 'Jelszó módosító link kiküldése',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
            'token' => 'Elfelejtett jelszó token',
            'link' => 'Elfelejtett jelszó megváltoztatásának linkje'
        ],
    ],
    'user_lock' => [
        'name' => 'Felhasználó zárolása',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_lift_lock' => [
        'name' => 'Zárolt felhasználó aktiválása',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_inactivated' => [
        'name' => 'Felhasználó felfüggesztése',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_lift_suspension' => [
        'name' => 'Felfüggesztett felhasználó aktiválása',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'not_activated' => [
        'name' => 'Aktiválásra váró státuszú felhasználó törlése',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_not_activated' => [
        'name' => 'Aktiválásra váró státuszú felhasználó törlése',
        'chanels' => ['notification', 'email'],
        'addressee' => 'Beállítást végző felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'password_change' => [
        'name' => 'Jelszóváltoztatás',
        'chanels' => ['notification', 'email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'email_change' => [
        'name' => 'E-mail módosítás',
        'chanels' => ['notification', 'email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_state_szunet' => [
        'name' => 'Felhasználó szüneteltetése',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_state_activated' => [
        'name' => 'Szüneteltetett felhasználó aktiválása',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
    'user_bad_login' => [
        'name' => 'Sikertelen belépési kísérlet',
        'chanels' => ['email'],
        'addressee' => 'Érintett felhasználó',
        'variables' => [
            'name' => 'Név',
            'login' => 'Felhasználónév',
            'email' => 'E-mail cím',
            'user_id' => 'Felhasználó azonosító',
        ],
    ],
];
