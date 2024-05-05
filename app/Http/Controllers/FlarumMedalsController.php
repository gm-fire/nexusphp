<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\MedalRepository;
use App\Http\Resources\MedalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlarumMedalsController extends Controller
{

    public function index(Request $request)
    {
        $userinfo = User::query()->where('id', $request->uid)->first();

        if ($userinfo) {
            $resource = $userinfo->medals;
        } else {
            $resource = new JsonResource(null);
        }

        return $this->success($resource);
    }

}
