<?php 

namespace App\WalletFacades;

use App\Models\User;
use App\Models\AdminAuth;
use App\StaffNotifier\KycNotify;


trait HasAdminNotification 
{
    public function notifyStaffs($direction, $content, $id) {
        $staffs = AdminAuth::get();
        $groupStaff = [];
        foreach($staffs as $staff){
            $groupStaff[] = $staff->email;
        } 

        $user = User::find(Auth::user()->id);
        $kycNotification = new KycNotify();
        $kycNotification->mailDispatch(adminStaffs: $groupStaff, direction: $direction, content: $content, fromUser: $user->email, id: $id);
    }
}