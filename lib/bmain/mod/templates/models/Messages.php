<?php

namespace mod\templates\models;

use hws\rmc\Model;

class Messages extends Model
{
    protected $table = 'template_messages';

    public $timestamps = false;

    public function defaultCollection()
    {
        $out = $this->toArray();
        if ($out['email'] == 'I') {
            if (($out['email_subj'] == null || $out['email_subj'] == '') && ($out['email_ct'] == null || $out['email_ct'] == '')) {
                $out['email'] = 'I'; // Kell, de még nincs kitöltve
            } else {
                $out['email'] = 'X';  // Kell, és ki is van töltve
            }
        }
        if ($out['push'] == 'I') {
            if (($out['push_text'] == null || $out['push_text'] == '') && ($out['push_subj'] == null || $out['push_subj'] == '')) {
                $out['push'] = 'I'; // Kell, de még nincs kitöltve
            } else {
                $out['push'] = 'X';  // Kell, és ki is van töltve
            }
        }
        if ($out['notification'] == 'I') {
            if ($out['notification_text'] == null || $out['notification_text'] == '') {
                $out['notification'] = 'I'; // Kell, de még nincs kitöltve
            } else {
                $out['notification'] = 'X';  // Kell, és ki is van töltve
            }
        }
        return $out;
    }

    public function viewCollection()
    {
        $out = $this->toArray();
        $out['notification_text'] = toHtml($this->notification_text);
        $out['email_ct'] = toHtml($this->email_ct);
        $out['variables'] = '';
        $messageTypes = config('messageTypes');
        if (array_key_exists('variables', $messageTypes[$this->modul_azon][$this->type])) {
            foreach ($messageTypes[$this->modul_azon][$this->type]['variables'] as $key => $value) {
                $out['variables'] .= '{' . $key . '} - ' . $value . '<br>';
            }
        }
        // $
        // dd($this->modul_azon, $this->type, $messageTypes);
        return $out;
    }

    public function findType()
    {
        $messageTypes = config('messageTypes');
        if (
            !array_key_exists($this->modul_azon, $messageTypes) ||
            !array_key_exists($this->type, $messageTypes[$this->modul_azon])
        ) return '';
        return  $messageTypes[$this->modul_azon][$this->type]['name'];
    }

    public function findChanels()
    {
        $messageTypes = config('messageTypes');
        if (
            !array_key_exists($this->modul_azon, $messageTypes) ||
            !array_key_exists($this->type, $messageTypes[$this->modul_azon])
        ) return '';
        return  $messageTypes[$this->modul_azon][$this->type]['chanels'];
    }


    static $template_cache = [];
    static function getTemplate($type, $entity_id, $modul_azon)
    {
        $key = $modul_azon . '_' . $entity_id . '_' . $type;
        if (array_key_exists($key, self::$template_cache)) {
            return self::$template_cache[$key];
        } else {
            $out = self::query()
                ->where('type', $type)
                ->where('entity_id', $entity_id)
                ->where('modul_azon', $modul_azon)
                ->one();

            self::$template_cache[$key] = $out;
            return $out;
        }
    }

    public function findModul()
    {
        $mods = config('mods');
        if (
            !array_key_exists($this->modul_azon, $mods)
        ) return '';
        return  $mods[$this->modul_azon]['name'];
    }

    protected $fillable = [
        "entity_id",
        "modul_azon",
        "type",
        "notification_text",
        "email_subj",
        "email_ct",
        "email_from",
        "push_text",
        "push_subj",
        "modul_name",
        "message_name",
        "email",
        "notification",
        "addressee",
        "push",
    ];

    protected $casts = [
        "id" => "integer",
        "entity_id" => "integer",
        "modul_azon" => "string",
        "type" => "string",
        "notification_text" => "string",
        "email_subj" => "string",
        "email_ct" => "string",
        "email_from" => "string",
        "push_text" => "string",
        "push_subj" => "string",
        "modul_name" => "string",
        "message_name" => "string",
        "email" => "string",
        "notification" => "string",
        "addressee" => "string",
        "push" => "string",
    ];

    protected $validation = [
        "entity_id" => "required|integer|digits_between:1,11",
        "modul_azon" => "required|max:100",
        "type" => "required|max:100",
        "notification_text" => "nullable",
        "email_subj" => "nullable",
        "email_ct" => "nullable",
        "email_from" => "nullable|max:255",
        "push_text" => "nullable",
        "push_subj" => "nullable",
        "modul_name" => "nullable|max:255",
        "message_name" => "nullable|max:255",
        "email" => "nullable|max:1",
        "notification" => "nullable|max:1",
        "addressee" => "nullable|max:255",
        "push" => "nullable|max:1",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "modul_azon" => "Modul azonosító",
        "type" => "Típus",
        "notification_text" => "Rendszer üzenet",
        "email_subj" => "E-mail tárgy",
        "email_ct" => "E-mail szöveg",
        "email_from" => "Feladó e-mail cím",
        "push_text" => "Push szöveg",
        "push_subj" => "Push tárgy",
        "modul_name" => "Modul neve",
        "message_name" => "Esemény neve",
        "email" => "E-mail",
        "notification" => "Rendszerüzenet",
        "addressee" => "Kinek küldi",
        "push" => "Push",
    ];

    public function Rel_entity()
    {
        return $this->belongsTo(AdminEntities::class, "entity_id", "id");
    }
}
