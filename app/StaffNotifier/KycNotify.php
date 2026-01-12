<?php

namespace App\StaffNotifier;

use App\Mail\AdminNotify;
use App\Models\StaffNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class KycNotify
{

    public function mailDispatch($adminStaffs, $direction, $content, $fromUser, $id)
    {

        StaffNotification::create([
            'user'      => $fromUser,
            'type'      => $direction,
            'type_id'   => $id,
            'readline'  => 'unread',
            'content'   => $content
        ]);
        foreach ($adminStaffs as $staff) {
            Log::info(['email in kycNotify' => $staff]);
            Mail::to($staff)->send(new AdminNotify(direction: $direction, content: $content, fromUser: $fromUser));
        }

        return [
            'staffs'        => $adminStaffs,
            'direction'     => $direction,
            'content'       => $content,
        ];
    }
}
