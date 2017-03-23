<?php

namespace App\Repositories;


use App\UVaUser;

class UVaUsersRepository
{

    public static function createUser($uvaID, $username): UVaUser
    {
        return UVaUser::firstOrCreate([
            'uvaID' => $uvaID,
            'username' => $username
        ]);
    }

    // Retrieve all UVaUsers being stalked by at least someone.
    public static function stalkedUsers()
    {
        return UVaUser::whereHas('stalks', function ($query) {
            $query->whereNull('deletedAt');
        })->get();
    }
}