<?php

namespace mod\admin\models;

use hws\rmc\Model;

class Nevek extends Model
{
    protected $table = 'nevek';

    public $timestamps = false;

    static $latin = true;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "nev",
        "teljesnev",
        "jelszo",
        "valtoztass",
        "email",
        "telefon",
        "beosztas",
        "csopid",
        "belephet",
        "alias",
        "ldap",
        "noleiras",
        "tavoliip",
        "sip",
        "email_pw",
        "uazon",
        "sikertelen",
        "extfelulet",
        "rss",
        "upin",
        "tavolinev",
        "parhuzam",
        "partner_id",
        "internetrol",
        "hash",
        "telefon2",
        "b_nevek_statusz_id",
        "torolheto",
    ];

    protected $casts = [
        "id" => "integer",
        "nev" => "string",
        "teljesnev" => "string",
        "jelszo" => "string",
        "valtoztass" => "boolean",
        "email" => "string",
        "telefon" => "string",
        "beosztas" => "string",
        "csopid" => "integer",
        "belephet" => "boolean",
        "alias" => "string",
        "ldap" => "string",
        "noleiras" => "boolean",
        "tavoliip" => "string",
        "sip" => "string",
        "email_pw" => "string",
        "uazon" => "string",
        "sikertelen" => "integer",
        "extfelulet" => "boolean",
        "rss" => "boolean",
        "upin" => "string",
        "tavolinev" => "string",
        "parhuzam" => "boolean",
        "partner_id" => "integer",
        "internetrol" => "boolean",
        "hash" => "boolean",
        "telefon2" => "string",
        "b_nevek_statusz_id" => "integer",
        "torolheto" => "boolean",
    ];

    protected $validation = [
        "nev" => "required|max:150",
        "teljesnev" => "required|max:150",
        "jelszo" => "required|max:100",
        "valtoztass" => "nullable",
        "email" => "nullable|max:150",
        "telefon" => "nullable|max:11",
        "beosztas" => "nullable|max:100",
        "csopid" => "nullable|integer|digits_between:1,11",
        "belephet" => "required",
        "alias" => "nullable|max:150",
        "ldap" => "nullable|max:150",
        "noleiras" => "nullable",
        "tavoliip" => "nullable|max:50",
        "sip" => "nullable|max:10",
        "email_pw" => "nullable|max:50",
        "uazon" => "nullable|max:8",
        "sikertelen" => "nullable|integer|digits_between:1,11",
        "extfelulet" => "nullable",
        "rss" => "nullable",
        "upin" => "nullable|max:60",
        "tavolinev" => "nullable|max:150",
        "parhuzam" => "nullable",
        "partner_id" => "nullable|integer|digits_between:1,11",
        "internetrol" => "nullable",
        "hash" => "nullable",
        "telefon2" => "nullable|max:50",
        "b_nevek_statusz_id" => "nullable|integer|digits_between:1,11",
        "torolheto" => "nullable",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "nev" => "Név",
        "teljesnev" => "Teljes név",
        "email" => "E-mail",
        "telefon" => "Telefon",
    ];
}
