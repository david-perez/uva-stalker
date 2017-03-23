<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UVaUser extends Model
{
    protected $table = 'UVaUsers';
    protected $primaryKey = 'uvaID';
    protected $fillable = ['uvaID', 'username'];
    public $timestamps = false;

    public function stalks()
    {
        return $this->hasMany('App\Stalk', 'uvaID', 'uvaID')->whereNull('deletedAt');
    }

    public function submissions()
    {
        return $this->hasMany('App\Submission', 'user', 'uvaID');
    }
}
