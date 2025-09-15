<?php

namespace App\Http\Controllers\wbscte;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\wbscte\Token;
use App\Models\wbscte\User;
use Exception;
use Validator;
use DB;

use App\Http\Resources\wbscte\UserResource;

class UsersController extends Controller
{

    protected $auth;
    public $back_url = null;

    public function __construct()
    {
        //$this->auth = new Authentication();
    }
}
