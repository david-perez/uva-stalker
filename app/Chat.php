<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'Chats';
    protected $primaryKey = 'chatID';
    protected $dates = ['createdAt'];
    protected $fillable = ['chatID'];
    public $timestamps = false;

    public function stalks()
    {
        return $this->hasMany('App\Stalk', 'chat', 'chatID')->notDeleted();
    }
}
