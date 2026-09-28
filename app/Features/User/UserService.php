<?php

namespace App\Features\User;

class UserService {
    //==================================================================================================================
    // Rôles
    //==================================================================================================================
    const string role_super_admin = 'super_admin';
    const string role_admin       = 'admin';
    const string role_enseignant  = 'enseignant';

    const array roles_administration = [
        self::role_super_admin,
        self::role_admin,
    ];
}
