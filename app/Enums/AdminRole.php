<?php

namespace App\Enums;

enum AdminRole: string 
{
    case Administrator = "administrator";
    case Developer = "developer";
    case Security = "security";
    case SuperAdmin = "super-administrator";
    case HumanResource = "human-resource";
}