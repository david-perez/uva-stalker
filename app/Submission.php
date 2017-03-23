<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'Submissions';
    protected $primaryKey = 'id';
    protected $guarded = []; // Every attribute is mass assignable.
    protected $dates = ['time'];
    public $timestamps = false;

    public function uvaUser()
    {
        return $this->belongsTo('App\UVaUser', 'user', 'uvaID');
    }
}
