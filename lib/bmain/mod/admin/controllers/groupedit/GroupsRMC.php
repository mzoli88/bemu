<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Groups;
use mod\admin\models\UserGroups;
use Illuminate\Support\Facades\Cache;

class GroupsRMC extends Controller3
{

    public $log_event_id = 'Szerepkörök';


    public $model = Groups::class;

    public $permissons = [
        'list' => true,
        'create' => ['SysAdmin', 'group_admin'],
        'update' => ['SysAdmin', 'group_admin'],
    ];

    public function list()
    {
        Groups::$showAll = true;
        return $this->defaultList()->entity();
    }

    public function create(Request $request)
    {
        $has = Groups::where('name',$request->name)->entity()->one();
        if($has) return response()->json([
            'name' => 'Ezzel a megnevezéssel már lett rögzíve szerepkör!'
        ], 422); 
        return $this->defaultCreate();
    }

    public function update(Request $request,$id)
    {
        $has = Groups::where('name',$request->name)->where('id','!=',$id)->entity()->one();
        if($has) return response()->json([
            'name' => 'Ezzel a megnevezéssel már lett rögzíve szerepkör!'
        ], 422); 

        if(!isSysAdmin()){
            $isInGroup =  UserGroups::where('group_id', $id)
            ->where('user_id', getUserId())
            ->one();
            if($isInGroup)sendError('Nem lehet a szerepkört módosítani, ha a saját felhasználónk szerepel benne!');
        }

        UserGroups::where('group_id',$id)->get()->each(function($UserGroup){
            Cache::forget('user_perms_' . $UserGroup->user_id);
        });

        return $this->defaultUpdate();
    }
}
