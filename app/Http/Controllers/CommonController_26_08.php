<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\SuperUser;
use App\Models\StudentChoice;
use App\Models\Token;
use Illuminate\Http\Request;
use App\Models\Trade;
use App\Models\District;
use App\Models\Board;
use App\Models\Role;
use App\Models\State;
use App\Models\Institute;
use App\Models\Eligibility;
use App\Models\AuthPermission;
use App\Models\AuthUrl;
use App\Models\AlotedAdmittedSeatMaster;
use App\Models\AlotedAdmittedPvtSeatMaster;
use App\Models\SpotSeatMaster;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TradeResource;
use App\Http\Resources\EligibilityResource;
use App\Http\Resources\EligibilityBoardResource;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\InstituteResource;
use App\Http\Resources\AllotmentStudentResource;
use App\Http\Resources\SubdivisionResource;
use App\Http\Resources\StateResource;
use App\Http\Resources\InstAdminResource;
use App\Http\Resources\BlockResource;
use Illuminate\Support\Str;
use App\Models\Schedule;
use App\Models\Block;
use App\Models\Subdivision;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Mail;
use App\Mail\ChoiceLockedEmail;
use App\Mail\ChoiceEmail;


class CommonController extends Controller
{

    public function __construct()
    {
        //$this->auth = new Authentication();
    }
    public function allStates(Request $request, $type = null)
    {
        if ($type) {
            $state_list = State::where('active_status', 1)->orderBy('state_name', 'ASC')->get();
            if (sizeof($state_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'State found',
                    'count'     =>   sizeof($state_list),
                    'states'  =>  StateResource::collection($state_list)
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No State available'
                );
                return response(json_encode($reponse), 200);
            }
        }
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', 2)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');
                    if (in_array('district-list', $url_data)) { //check url has permission or not

                        $state_list = State::where('active_status', 1)->orderBy('state_name', 'ASC')->get();


                        if (sizeof($state_list) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'State found',
                                'count'     =>   sizeof($state_list),
                                'states'  =>  StateResource::collection($state_list)
                            );
                            return response(json_encode($reponse), 200);
                        } else {
                            $reponse = array(
                                'error'     =>  true,
                                'message'   =>  'No State available'
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }
    //District List
    public function allDistricts(Request $request,  $state_code = null, $user_type = null,)
    {

        if ($user_type) {
            if ($state_code == null) {
                $district_list = District::with('state:state_id_pk,state_name')->where('active_status', '1')->orderBy('district_id_pk', 'DESC')->get();
            } else {
                $district_list = District::with('state:state_id_pk,state_name')->where('active_status', '1')->where('state_id_fk', $state_code)->orderBy('district_id_pk', 'DESC')->get();
            }
            if (sizeof($district_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'District found',
                    'count'     =>   sizeof($district_list),
                    'districts'  =>  DistrictResource::collection($district_list)
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No district available'
                );
                return response(json_encode($reponse), 200);
            }
        }
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = Student::where('s_id', $user_id)->first();
                if ($user_data) {
                    $user_role_id = $user_data->u_role_id;
                } else {
                    $admin_user = SuperUser::where('u_id', $user_id)->first();
                    $user_role_id = $admin_user->u_role_id;
                }
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');
                    if (in_array('district-list', $url_data)) {
                        if ($state_code == null) {
                            $district_list = District::orderBy('district_id_pk', 'ASC')->get();
                        } else {
                            $district_list = District::where('state_id_fk', $state_code)->orderBy('district_id_pk', 'ASC')->get();
                        }


                        if (sizeof($district_list) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'District found',
                                'count'     =>   sizeof($district_list),
                                'districts'  =>  DistrictResource::collection($district_list)
                            );
                            return response(json_encode($reponse), 200);
                        } else {
                            $reponse = array(
                                'error'     =>  true,
                                'message'   =>  'No district available'
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
        // }
    }
    public function allBlocks(Request $request,  $subdivision = null, $user_type = null,)
    {

        if ($user_type) {
            if ($subdivision == null) {
                $block_list = Block::with('subdivision:id,name')->where('active_status', '1')->orderBy('id', 'DESC')->get();
            } else {
                $block_list = Block::with('subdivision:id,name')->where('active_status', '1')->where('subdivision_id', $subdivision)->orderBy('id', 'DESC')->get();
            }
            if (sizeof($block_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'Block found',
                    'count'     =>   sizeof($block_list),
                    'blocks'    =>  BlockResource::collection($block_list)
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No block available'
                );
                return response(json_encode($reponse), 200);
            }
        }
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = Student::where('s_id', $user_id)->first();
                if ($user_data) {
                    $user_role_id = $user_data->u_role_id;
                } else {
                    $admin_user = SuperUser::where('u_id', $user_id)->first();
                    $user_role_id = $admin_user->u_role_id;
                }
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');
                    if (in_array('district-list', $url_data)) {
                        if ($subdivision == null) {
                            $block_list = Block::with('subdivision:id,subdivision_name')->where('active_status', '1')->orderBy('block_id_pk', 'DESC')->get();
                        } else {
                            $block_list = Block::with('subdivision:id,subdivision_name')->where('active_status', '1')->where('subdivision_id', $subdivision)->orderBy('block_id_pk', 'DESC')->get();
                        }
                        if (sizeof($block_list) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'Block found',
                                'count'     =>   sizeof($block_list),
                                'blocks'    =>  BlockResource::collection($block_list)
                            );
                            return response(json_encode($reponse), 200);
                        } else {
                            $reponse = array(
                                'error'     =>  true,
                                'message'   =>  'No block available'
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
        // }
    }

    //Institute List
    public function allInstList(Request $request, $type = null)
    {

        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $stream    =   $request->stream;
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_role = $request->role_id;
                if (!empty($user_role)) {
                    $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_role)->pluck('rp_url_id');
                } else {
                    $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', 2)->pluck('rp_url_id');
                }

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('institute-stream-wise', $url_data)) { //check url has permission or not
                        $inst_res = null;
                        $res = null;

                        $inst_list = Institute::where('is_active',  1)->orderBy('i_name', 'ASC')->get();

                        $res = InstituteResource::collection($inst_list);

                        if (sizeof($res) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'Institute found',
                                'count'     =>   sizeof($res),
                                'instituteList'   =>  $res
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //Stream List


    //Stream List Inst wise

    //Inst or Admin wise alloted students

    //Student Allotment Details


    //Student allotment/take admission accept or reject

    //All admitted students lists with filter

    //Seat matrix with filter
    public function seatMatrix(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                //return $user_data;
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('seat-matrix', $url_data)) { //check url has permission or not
                        $validated = Validator::make($request->all(), [
                            'trade_code' => ['required']
                        ]);

                        if ($validated->fails()) {
                            return response()->json([
                                'error' => true,
                                'message' => $validated->errors()
                            ]);
                        }
                        $trade = $request->trade_code;
                        $inst_code = $request->inst_code;
                        $seatMaster = AlotedAdmittedSeatMaster::query();
                        if (!empty($inst_code)) {
                            $allseats = $seatMaster->where('sm_inst_code', $inst_code);
                        } else {
                            $allseats = $seatMaster->where('sm_inst_code', $user_data->u_inst_code);
                        }
                        $allseats = $seatMaster->where('sm_trade_code', $trade);
                        $allseats = $seatMaster->clone()->first();

                        //dd($allseats);
                        $data = [];
                        $cast = cast();
                        foreach ($cast as $val) {
                            //$val = Str::lower($val);
                            $data[casteValue(Str::upper($val))] =   array(
                                'initial_seats'     =>    $allseats->{"m_" . $val},
                                'alloted_seats'     => ($allseats->{"m_" . $val}) - ($allseats->{$val}),
                                'not_alloted_seats'    =>     $allseats->{$val},
                                'admitted_seats'     =>     $allseats->{"a_" . $val},
                                'available_seats'     => ($allseats->{"m_" . $val}) - ($allseats->{"a_" . $val})
                            );
                        }

                        //return  $data;

                        if (sizeof($data) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'List found',
                                'count'     =>   sizeof($data),
                                'list'   =>  $data,
                                'excel_name' => 'seat_matrix_lists'
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //State List


    //religion list
    public function allReligions(Request $request, $type = null)
    {
        if ($type) {
            $religion_list = array(
                'HINDUISM'  =>  'HINDUISM',
                'ISLAM'  =>  'ISLAM',
                'CHRISTIANITY'  =>  'CHRISTIANITY',
                'SIKHISM'  =>  'SIKHISM',
                'BUDDHISM'  =>  'BUDDHISM',
                'JAINISM'  =>  'JAINISM',
                'OTHER'  =>  'OTHER',
            );


            if (sizeof($religion_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'State found',
                    'count'     =>   sizeof($religion_list),
                    'religions'  =>  $religion_list
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No Religion available'
                );
                return response(json_encode($reponse), 200);
            }
        }
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', 2)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');
                    if (in_array('district-list', $url_data)) { //check url has permission or not

                        $religion_list = array(
                            'HINDUISM'  =>  'HINDUISM',
                            'ISLAM'  =>  'ISLAM',
                            'CHRISTIANITY'  =>  'CHRISTIANITY',
                            'SIKHISM'  =>  'SIKHISM',
                            'BUDDHISM'  =>  'BUDDHISM',
                            'JAINISM'  =>  'JAINISM',
                            'OTHER'  =>  'OTHER',
                        );


                        if (sizeof($religion_list) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'State found',
                                'count'     =>   sizeof($religion_list),
                                'religions'  =>  $religion_list
                            );
                            return response(json_encode($reponse), 200);
                        } else {
                            $reponse = array(
                                'error'     =>  true,
                                'message'   =>  'No Religion available'
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //caste list
    public function allCastes(Request $request, $type = null)
    {
        if ($type) {
            $caste_list = array(
                'GENERAL'  =>  'GENERAL',
                'SC'  =>  'SC',
                'ST'  =>  'ST',
                'OBC-A'  =>  'OBC-A',
                'OBC-B'  =>  'OBC-B'
            );

            if (sizeof($caste_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'State found',
                    'count'     =>   sizeof($caste_list),
                    'castes'  =>  $caste_list
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No Caste available'
                );
                return response(json_encode($reponse), 200);
            }
        }
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', 2)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');
                    if (in_array('district-list', $url_data)) { //check url has permission or not

                        $caste_list = array(
                            'GENERAL'  =>  'GENERAL',
                            'SC'  =>  'SC',
                            'ST'  =>  'ST',
                            'OBC-A'  =>  'OBC-A',
                            'OBC-B'  =>  'OBC-B'
                        );

                        if (sizeof($caste_list) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'State found',
                                'count'     =>   sizeof($caste_list),
                                'castes'  =>  $caste_list
                            );
                            return response(json_encode($reponse), 200);
                        } else {
                            $reponse = array(
                                'error'     =>  true,
                                'message'   =>  'No Caste available'
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //Count round wise Alloted, Admitted, Rejected


    //All Inst Admin list
    public function allInstAdminList(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                //return $user_data;
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('inst-admin-list', $url_data)) { //check url has permission or not
                        $allAdminList = SuperUser::select('u_id', 'u_inst_code', 'u_inst_name', 'u_username')->where('u_role_id', 3)->where('is_active', 1)->orderBy('u_username', 'ASC')->get();


                        if (sizeof($allAdminList) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'List found',
                                'count'     =>   sizeof($allAdminList),
                                'List'   =>  InstAdminResource::collection($allAdminList)
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
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //Sent mail students who are not pay counselling fees 
    public function allSubdivisions(Request $request, $dist_id = null, $type = null)
    {
        if ($type = null) {
            if ($request->header('token')) {
                $now    =   date('Y-m-d H:i:s');
                $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
                if ($token_check) {  // check the token is expire or not
                    $user_id = $token_check->t_user_id;
                    $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();
                    $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                    if (sizeof($role_url_access_id) > 0) {
                        $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                        $url_data = array_column($urls, 'url_name');
                        if (in_array('subdivision-list', $url_data)) { //check url has permission or not
                            if ($dist_id) {
                                $subdivision_list = Subdivision::with('district:district_id_pk,district_name')->where('active_status', '1')->where('district_id', $dist_id)->orderBy('id', 'DESC')->get();
                            } else {
                                $subdivision_list = Subdivision::with('district:district_id_pk,district_name')->where('active_status', '1')->orderBy('id', 'DESC')->get();
                            }
                            if (sizeof($subdivision_list) > 0) {
                                $reponse = array(
                                    'error'     =>  false,
                                    'message'   =>  'subdivision found',
                                    'count'     =>   sizeof($subdivision_list),
                                    'subdivisions'  =>  SubdivisionResource::collection($subdivision_list)
                                );
                                return response(json_encode($reponse), 200);
                            } else {
                                $reponse = array(
                                    'error'     =>  false,
                                    'message'   =>  'No subdivision available'
                                );
                                return response(json_encode($reponse), 200);
                            }
                        } else {
                            return response()->json([
                                'error'     =>  true,
                                'message'   =>   "Oops! you don't have sufficient permission"
                            ], 403);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>  'Unable to process your request due to invalid token'
                    ], 401);
                }
            } else {
                return response()->json([
                    'error'     =>  true,
                    'message'   =>  'Unable to process your request due to non availability of token'
                ], 401);
            }
        } else {
            // dd('hi');
            if ($dist_id) {

                $subdivision_list = Subdivision::with('district:district_id_pk,district_name')->where('active_status', '1')->where('district_id', $dist_id)->orderBy('id', 'DESC')->get();
                // dd($subdivision_list);
            } else {

                $subdivision_list = Subdivision::with('district:district_id_pk,district_name')->where('active_status', '1')->orderBy('id', 'DESC')->get();
            }
            if (sizeof($subdivision_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'subdivision found',
                    'count'     =>   sizeof($subdivision_list),
                    'subdivisions'  =>  SubdivisionResource::collection($subdivision_list)
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No subdivision available'
                );
                return response(json_encode($reponse), 200);
            }
        }
    }
    public function eligibilityList(Request $request, $type = null)
    {
        if ($type) {
            $eligibility_list = Eligibility::where('is_active', '1')->orderBy('id', 'ASC')->get();
            if (sizeof($eligibility_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'Data found',
                    'count'     =>   sizeof($eligibility_list),
                    'eligibilities'    =>  EligibilityResource::collection($eligibility_list)
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No data available'
                );
                return response(json_encode($reponse), 200);
            }
        }
    }
    public function boardList(Request $request, $code, $type = null)
    {
        if ($type) {
            $board_list = Board::where('is_active', '1')->where('state_id_fk', $code)->orderBy('id', 'ASC')->get();
            if (sizeof($board_list) > 0) {
                $reponse = array(
                    'error'     =>  false,
                    'message'   =>  'Data found',
                    'count'     =>   sizeof($board_list),
                    'boards'    =>  EligibilityBoardResource::collection($board_list)
                );
                return response(json_encode($reponse), 200);
            } else {
                $reponse = array(
                    'error'     =>  true,
                    'message'   =>  'No data available'
                );
                return response(json_encode($reponse), 200);
            }
        }
    }
    public function eligibilityStateList(Request $request, $type = null)
    {
        if ($type) {

            $state_list = Board::where('is_active', '1')
                ->orderBy('id', 'ASC')
                ->get()
                ->groupBy('state_name')
                ->map(function ($group, $state_name) {
                    return [
                        'name' => $state_name,
                        'code' => $group->first()->state_code, // pick first code for the state name
                    ];
                })
                ->values();
            if ($state_list->count()) {
                return response()->json([
                    'error'   => false,
                    'message' => 'Data found',
                    'count'   => $state_list->count(),
                    'list'    => $state_list
                ], 200);
            } else {
                return response()->json([
                    'error'   => true,
                    'message' => 'No data available'
                ], 200);
            }
        }
    }

    public function verifierType(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;

                $user_data = SuperUser::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();
                $role_url_access_id = AuthPermission::where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');
                // dd($role_url_access_id);

                if (sizeof($role_url_access_id) > 0) {
                    $urls = AuthUrl::where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();

                    $url_data = array_column($urls, 'url_name');
                    // dd($url_data);
                    if (in_array('verifier-type', $url_data)) {


                        $list = Role::where('is_active', 1)
                            ->where('role_name', '!=', 'COUNCIL')
                            ->where('role_name', '!=', 'STUDENT')
                            ->orderBy('role_id', 'ASC')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'id' => $item->role_id,
                                    'name' => $item->role_name
                                ];
                            });


                        if (sizeof($list) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'data found',
                                'count'     =>   sizeof($list),
                                'list'  =>  $list
                            );
                            return response(json_encode($reponse), 200);
                        } else {
                            $reponse = array(
                                'error'     =>  true,
                                'message'   =>  'No Religion available'
                            );
                            return response(json_encode($reponse), 200);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "Oops! you don't have sufficient permission"
                    ], 403);
                }
            } else {
                return response()->json([
                    'error'     =>  true,
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        }
    }
    public function InstituteWiseDistrict(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;

                $user_data = SuperUser::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();
                $role_url_access_id = AuthPermission::where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');
                // dd($role_url_access_id);

                if (sizeof($role_url_access_id) > 0) {
                    $urls = AuthUrl::where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();

                    $url_data = array_column($urls, 'url_name');
                    // dd($url_data);
                    if (in_array('inst-wise-district', $url_data)) {
                        $institute = $request->inst_code;

                        $data = Institute::with('district')
                            ->where('i_code', $institute)
                            ->where('is_active', 1)
                            ->first();

                        if ($data) {
                            $response = [
                                'error'   => false,
                                'message' => 'data found',
                                'list'    => [
                                    'i_dist_code'     => $data->i_dist_code,
                                    'district_id'  => $data->district->district_id_pk ?? null
                                ]
                            ];
                        } else {
                            $response = [
                                'error'   => true,
                                'message' => 'No data available'
                            ];
                        }

                        return response()->json($response, 200);
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "Oops! you don't have sufficient permission"
                    ], 403);
                }
            } else {
                return response()->json([
                    'error'     =>  true,
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        }
    }
    public function allRoles(Request $request)
    {

        $list = Role::where('is_active', 1)
            ->orderBy('role_id', 'ASC')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->role_name,
                    'label' => $item->role_description
                ];
            });

        if (sizeof($list) > 0) {
            $reponse = array(
                'error'     =>  false,
                'message'   =>  'data found',
                'count'     =>   sizeof($list),
                'list'  =>  $list
            );
            return response(json_encode($reponse), 200);
        } else {
            $reponse = array(
                'error'     =>  true,
                'message'   =>  'No data available'
            );
            return response(json_encode($reponse), 200);
        }
    }
    public function OtherBoard(Request $request)
    {
        $board_list = Board::where('is_active', '1')->where('state_code', 'OT')->orderBy('id', 'ASC')->get();
        if (sizeof($board_list) > 0) {
            $reponse = array(
                'error'     =>  false,
                'message'   =>  'Data found',
                'count'     =>   sizeof($board_list),
                'boards'    =>  EligibilityBoardResource::collection($board_list)
            );
            return response(json_encode($reponse), 200);
        } else {
            $reponse = array(
                'error'     =>  true,
                'message'   =>  'No data available'
            );
            return response(json_encode($reponse), 200);
        }
    }
}
