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

    public function UVaUser()
    {
        return $this->hasOne('App\UVaUser', 'uvaID', 'uvaID');
    }

    // We cannot name this method chat() because it enters in conflict with the column name.
    public function associatedChat()
    {
        return $this->belongsTo('App\Chat', 'chat', 'chatID');
    }
}
