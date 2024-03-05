<?php

/*
Feladat készítésére interface.
Folyamat sablon azonosító kell, és át lehet adni egy interface azonosítót amit a feladat alap adatai között elmentünk.

használat:

require_once('workflow-laravel/CreateTask.php');

$task_id = CreateTask(1,2,3,4); // template_task_id / interface_id, $user_id, $entity_id

if($task_id == 'error'){
    //részltek naplózva lettek
    throw new Exception("Feladat létrehozása sikertelen!");
}

*/


function CreateTask($template_task_id, $interface_id, $user_id, $entity_id = null, $workeres = [], $responsible = [], $next_state_interface_name = null)
{
    // $artisan = "php " . __DIR__ . "/artisan ";
    // exec($artisan . "CreateTask $template_task_id $interface_id $user_id $entity_id", $out);
    // return $out[0];
}

//meglévő folyamat módosítása (külső beavatkozás, pl lezárás)
function changeTask($task_id, $user_id, $next_state_interface_name = 'null', $workeres  = [], $responsible = [])
{
}
