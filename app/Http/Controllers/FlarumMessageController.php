<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FlarumMessageController extends Controller
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
        $message = [
            'receiver' => $user->id,
            'added'    => now(),
            'subject'  => htmlspecialchars($request->data['subject']),
            'msg'      => htmlspecialchars($request->data['body'])
        ];

        $resource = Message::add($message);
        return $this->success($resource);
    }

}
