<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FlarumAvatarController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $avatar = $request['data']['avatar'];

        if ($avatar) {
            $user->avatar = $avatar;
        } else {
            $user->avatar = getSchemeAndHttpHost() . '/pic/default_avatar.png';
        }
        $user->save();

        return $this->success($user);
    }

}
