<?php

namespace mod\admin\controllers\systemparam;

use hws\rmc\Controller3;
use Illuminate\Support\Facades\Storage;
use mod\admin\models\Params;

class DeleteLogoRMC extends Controller3
{
    public $log_event_id = 'Belépő képernyő logó kép';

    public function delete()
    {

        Storage::delete('public/'.getParam('login_logo'));

        setParams([
            'login_logo' => null
        ]);
        
        Params::cacheBorderConfig();

        return [];
    }
}
