<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;

class EmailsRetries extends Model
{
    protected $table = 'notify_emails_retries';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "notify_email_id",
        "attempt",
    ];

    protected $casts = [
        "id" => "integer",
        "notify_email_id" => "integer",
        "attempt" => "integer",
    ];

    protected $validation = [
        "notify_email_id" => "required|integer|digits_between:1,20",
        "attempt" => "required|integer|digits_between:1,11",
    ];

    protected $labels = [
        "id" => "Azonosító",
    ];

}
