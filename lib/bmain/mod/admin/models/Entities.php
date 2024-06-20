<?php

namespace mod\admin\models;

use hws\rmc\Model;

class Entities extends Model
{
    protected $table = 'admin_entities';

    static $useStatus = true;

    public $timestamps = false;

    public function defaultCollection()
    {
        return $this->toArray();
    }

    protected $fillable = [
        "name",
        "status",
    ];

    protected $casts = [
        "id" => "integer",
        "name" => "string",
        "status" => "string",
    ];

    protected $validation = [
        "name" => "required|max:255",
        "status" => "required|max:1",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "name" => "Entitás",
        "status" => "Státusz",
    ];

    /* ->byPerms(['jog1','jog']) */
    public function scopeByPerms($q, $perms, $user_id = null, $modul_azon = null)
    {
        if (config('isBorder')) return $q;
        $modul_azon = $modul_azon ?: getModulAzon();
        $user_id = $user_id ?: getUserId();

        $q->whereIn('admin_entities.id', function ($q) use ($modul_azon, $user_id, $perms) {
            $q->select('entity_id')
                ->from('admin_view_perms');

            if (is_array($perms)) $q->whereIn('perm', $perms);
            else $q->where('perm', $perms);

            if (is_array($modul_azon)) $q->whereIn('modul_azon', $modul_azon);
            else $q->where('modul_azon', $modul_azon);

            if (is_array($user_id)) $q->whereIn('user_id', $user_id);
            else $q->where('user_id', $user_id);
        });

        return $q;
    }

    public function Rel_Groups_entity()
    {
        return $this->hasMany(Groups::class, "entity_id", "id");
    }

    public function Rel_GroupPerms_entity()
    {
        return $this->hasMany(GroupPerms::class, "entity_id", "id");
    }

    public function Rel_UserEntities_entity()
    {
        return $this->hasMany(UserEntities::class, "entity_id", "id");
    }

    public function Rel_UserPerms_entity()
    {
        return $this->hasMany(UserPerms::class, "entity_id", "id");
    }


    public function Rel_Emails_entity()
    {
        return $this->hasMany(Emails::class, "entity_id", "id");
    }

    public function Rel_Notifications_entity()
    {
        return $this->hasMany(Notifications::class, "entity_id", "id");
    }

    public function Rel_TemplateMessages_entity()
    {
        return $this->hasMany(TemplateMessages::class, "entity_id", "id");
    }

    public function Rel_NotifyEmails_entity()
    {
        return $this->hasMany(NotifyEmails::class, "entity_id", "id");
    }

    public function Rel_NotifyPushs_entity()
    {
        return $this->hasMany(NotifyPushs::class, "entity_id", "id");
    }
}
