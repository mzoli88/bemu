<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;
use mod\admin\models\Entities;
use mod\admin\models\Users as AdminUsers;

class Emails extends Model
{
    protected $table = 'notify_emails';

    public $timestamps = true;

    public function defaultCollection(){
        return $this->toArray();
    }

    public function listCollection()
    {
        $out = $this->toArray();

        $generalParams = getParams('notificationcenter');

        $out['modul_id'] = getModulName($out['modul_id']);

        // echo storage_path() . '/' . $generalParams['notification_email_path'] . $this->entity_id . '/error/' . $this->id . '.zip';

        $out['has_attachment'] = is_file(storage_path() . '/' . $generalParams['notification_email_path'] . $this->entity_id . '/error/' . $this->id . '.zip');
        // $out['message'] = toHtml($this->message);
        return $out;
    }

    protected $fillable = [
		"entity_id",
        "priority",
        "state",
        "user_id",
        "subject",
        "body",
        "sender_name",
        "sender_email_address",
        "is_html",
        "sender_params",
        "addressee",
        "sent_error",
        "modul_id",
        "sent_date",
        "modul_record_id",
        "callback",
        "cc_emails",
        "bcc_emails",
    ];

    protected $casts = [
		"id" => "integer",
        "entity_id" => "integer",
        "priority" => "integer",
        "state" => "integer",
        "user_id" => "integer",
        "subject" => "string",
        "body" => "string",
        "sender_name" => "string",
        "sender_email_address" => "string",
        "is_html" => "string",
        "sender_params" => "string",
        "addressee" => "string",
        "sent_error" => "string",
        "modul_id" => "string",
        "created_at" => "datetime:Y.m.d. H:i:s",
        "updated_at" => "datetime:Y.m.d. H:i:s",
        "sent_date" => "datetime:Y.m.d. H:i:s",
        "modul_record_id" => "integer",
        "callback" => "string",
        "cc_emails" => "string",
        "bcc_emails" => "string",
    ];

    protected $validation = [
		"entity_id" => "required|integer|digits_between:1,11",
        "priority" => "required|integer|digits_between:1,11",
        "state" => "required|integer|digits_between:1,11",
        "user_id" => "nullable|integer|digits_between:1,20",
        "subject" => "required|max:255",
        "body" => "required",
        "sender_name" => "nullable|max:255",
        "sender_email_address" => "nullable|max:255",
        "is_html" => "nullable|max:1",
        "sender_params" => "required",
        "addressee" => "required|max:255",
        "sent_error" => "nullable",
        "modul_id" => "required|max:255",
        "sent_date" => "nullable|date",
        "modul_record_id" => "nullable|integer|digits_between:1,11",
        "callback" => "nullable|max:255",
        "cc_emails" => "nullable",
        "bcc_emails" => "nullable",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "priority" => "Prioritás",
        "state" => "Állapot",
        "user_id" => "Létrehozó felhasználó",
        "subject" => "Levél tárgya",
        "body" => "Levél tartalma",
        "sender_name" => "Levél feladó neve",
        "sender_email_address" => "Levél feladó e-mail címe",
        "is_html" => "Html tartalom",
        "sender_params" => "E-mail továbbítás paraméterei",
        "addressee" => "Címzett e-mail címe",
        "sent_error" => "Továbbítási hiba",
        "modul_id" => "Modul azonosító",
        "created_at" => "Létrehozás ideje",
        "updated_at" => "Továbbítás ideje",
        "sent_date" => "Továbbítás ideje",
        "modul_record_id" => "Küldő modul azonosító",
        "callback" => "Eredmény visszaadás",
        "cc_emails" => "Másolat",
        "bcc_emails" => "Titkos másolat",
    ];

    public function Rel_entity(){
        return $this->belongsTo(Entities::class,"entity_id","id");
    }

    public function Rel_priority(){
        return $this->belongsTo(Priorities::class,"priority","id");
    }

    public function Rel_state(){
        return $this->belongsTo(States::class,"state","id");
    }


	public function Rel_EmailAttachments_notify_email(){
        return $this->hasMany(EmailAttachments::class,"notify_email_id","id");
    }

	public function Rel_user(){
        return $this->belongsTo(AdminUsers::class,"user_id","id");
    }
}
