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
}