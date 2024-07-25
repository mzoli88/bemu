<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;

class EmailAttachments extends Model
{
    protected $table = 'notify_email_attachments';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"notify_email_id",
        "filename",
        "filep_path",
    ];

    protected $casts = [
		"id" => "integer",
        "notify_email_id" => "integer",
        "filename" => "string",
        "filep_path" => "string",
    ];

    protected $validation = [
		"notify_email_id" => "required|integer|digits_between:1,11",
        "filename" => "required|max:255",
        "filep_path" => "required|max:255",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "notify_email_id" => "Email azonosító",
        "filename" => "Csatolás neve",
        "filep_path" => "Csatolás elérése",
    ];


	public function Rel_notify_email(){
        return $this->belongsTo(Emails::class,"notify_email_id","id");
    }
}
