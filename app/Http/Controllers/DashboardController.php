<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\SuperUser;
use App\Models\Token;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\StudentChoice;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\StudentActivityResource;
use Illuminate\Support\Str;

class DashboardController extends Controller
{

    protected $auth;
    public $back_url = null;

    public function __construct()
    {
        //$this->auth = new Authentication();
    }

    public function countDashboardCards(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name', 'u_inst_district')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('dashboard-count', $url_data)) {
                        if ($user_data->u_role_id == 1) {

                            $admissionCount = Student::where('is_personal_save', 1)->count();

                            $paymentCount = Student::where('is_personal_save', 1)->where('is_payment', 1)->count();
                        } else {
                            $admissionCount = Student::where('is_personal_save', 1)->where('s_home_district', $user_data->u_inst_district)->count();
                            $paymentCount = Student::where('is_personal_save', 1)->where('s_home_district', $user_data->u_inst_district)->where('is_payment', 1)->count();
                        }

                        $data = [
                            'admission_count' => $admissionCount,
                            'payment_count' => $paymentCount,

                        ];
                        //dd($data);
                        if (sizeof($data) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'Count found',
                                'countList'   =>  $data
                            );
                            return response(json_encode($reponse), 200);
                        } else {
                            $reponse = array(
                                'error'     =>  true,
                                'message'   =>  'No data found'
                            );
                            return response(json_encode($reponse), 200);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "Oops! you don't have sufficient permission"
                    ], 401);
                }
            } else {
                return response()->json([
                    'error'     =>  true,
                    'message'   =>   "Oops! you don't have sufficient permission"
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to invalid token'
            ], 401);
        }
    }
}
