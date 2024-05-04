<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\BonusRepository;
use App\Models\User;

class FlarumSeedBonusController extends Controller
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
        $bonus = $request['data']['seedbonus'];
        $toUserinfo = User::query()->where('id', $request['data']['username'])->first();
        if (!$user || !$toUserinfo) {
            return $this->fail(null, "用户不存在");
        }
        if ($user['id'] == $toUserinfo['id']) {
            return $this->fail(null, "不能给自己转账");
        }
        if ($user['seedbonus'] < $bonus) {
            return $this->fail(null, "魔力不足");
        }
        $bonusRepo = new BonusRepository();
        $response = json_decode($bonusRepo->bonusTransfer($user, $toUserinfo, $bonus, false)); // 免税

        $message = [
            'receiver' => $toUserinfo['id'],
            'added'    => now(),
            'subject'  => "收到礼物",
            'msg'      => "你收到{$bonus}".(($response->tax > 0)?"（扣取手续费后为{$response->reception}）":"")."个魔力值的礼物。祝福来自{$user->username}"
        ];
        Message::add($message);

        return $this->success($response);
    }

}
