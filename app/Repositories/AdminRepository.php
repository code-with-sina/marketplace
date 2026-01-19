<?php

namespace App\Repositories;

use App\Models\AdminAuth;
use Illuminate\Support\Collection;

class AdminRepository
{
    public function all(): Collection
    {
        return AdminAuth::get();
    }
}