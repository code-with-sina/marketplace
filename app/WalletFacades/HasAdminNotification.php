<?php 

namespace App\WalletFacades;

use App\Models\User;
use App\StaffNotifier\KycNotify;


trait HasAdminNotification 
{
    public function notifyStaffs($direction, $content, $id) {
        $staffing = Http::get('https://staffbased.ratefy.co/api/admin-staff');
        $staffs = $staffing->object();
        $groupStaff = [];
        foreach($staffs as $staff){
            $groupStaff[] = $staff->email;
        } 

        $user = User::find(Auth::user()->id);
        $kycNotification = new KycNotify();
        $kycNotification->mailDispatch(adminStaffs: $groupStaff, direction: $direction, content: $content, fromUser: $user->email, id: $id);
    }
}