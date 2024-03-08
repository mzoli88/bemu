<?php

namespace mod\admin\models;

use hws\rmc\Model;

class BNagycsoport extends Model
{
    protected $table = 'b_nagycsoport';

    protected $primaryKey = 'b_nagycsoport_id';

    public $timestamps = false;

    static $latin = true;

    public function defaultCollection()
    {
        return $this->toArray();
    }

    protected $fillable = [
        "nev",
        "b_nagycsoport_tipus_id",
    ];

    protected $casts = [
        "b_nagycsoport_id" => "integer",
        "nev" => "string",
        "b_nagycsoport_tipus_id" => "integer",
    ];

    protected $validation = [
        "nev" => "required|max:100",
        "b_nagycsoport_tipus_id" => "required|integer|digits_between:1,11",
    ];

    protected $labels = [
        "b_nagycsoport_id" => "Azonosító",
        "nev" => "Szervezeti egység neve",
        "modules" => "Modul azonosítók",
        "users" => "Felhasználók",
    ];

    public function scopeModul($query, $user_id = false)
    {
        $query->join('b_nagycsoport_modul', 'b_nagycsoport.b_nagycsoport_id', '=', 'b_nagycsoport_modul.b_nagycsoport_id')
            ->where('b_nagycsoport_modul.modul_azon', config('modul_azon'));
        if ($user_id) {
            $query->join('b_nagycsoport_nevek', "b_nagycsoport.b_nagycsoport_id", "=", "b_nagycsoport_nevek.b_nagycsoport_id")
                ->where('b_nagycsoport_nevek.nevek_id', $user_id);
        }
        return $query;
    }

    public function BNagycsoportTipus()
    {
        return $this->belongsTo(BNagycsoportTipus::class, 'b_nagycsoport_tipus_id', 'b_nagycsoport_tipus_id');
    }   
    
}
