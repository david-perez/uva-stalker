<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stalk extends Model
{
    protected $table = 'Stalks';
    protected $primaryKey = 'stalkID';
    protected $dates = ['createdAt', 'deletedAt'];
    protected $fillable = ['chat', 'uvaID'];
    public $timestamps = false;

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deletedAt');
    }
}
