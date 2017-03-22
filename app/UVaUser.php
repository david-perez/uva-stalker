<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UVaUser extends Model
{
    protected $table = 'UVaUsers';
    protected $primaryKey = 'uvaID';
    protected $fillable = ['uvaID', 'username'];
    public $timestamps = false;
}
