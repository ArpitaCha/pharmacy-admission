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
        $state_list = State::where('active_status', 1)->orderBy('state_name', 'ASC')->get();


        if ($state_list->isNotEmpty()) {
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
    //District List
    public function allDistricts(Request $request,  $state_code = null, $user_type = null,)
    {

        if ($user_type) {
            if ($state_code == null) {
                $district_list = District::with('state:state_id_pk,state_name')->where('active_status', '1')->orderBy('district_name', 'ASC')->get();
            } else {
                $district_list = District::with('state:state_id_pk,state_name')->where('active_status', '1')->where('state_id_fk', $state_code)->orderBy('district_name', 'ASC')->get();
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
        if ($state_code == null) {
            $district_list = District::orderBy('district_name', 'ASC')->get();
        } else {
            $district_list = District::where('state_id_fk', $state_code)->orderBy('district_name', 'ASC')->get();
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

        // }
    }
    public function allBlocks(Request $request, $subdivision = null, $user_type = null)
    {
        $query = Block::with('subdivision:id,name') // use correct column from Subdivision table
            ->where('active_status', 1);

        // Apply subdivision filter if provided
        if ($subdivision && is_numeric($subdivision)) {
            $query->where('subdivision_id', $subdivision);
        }

        $block_list = $query->orderBy('name', 'ASC')->get();

        if ($user_type) {
            return response()->json([
                'error'   => false,
                'message' => $block_list->isNotEmpty() ? 'Block found' : 'No block available',
                'count'   => $block_list->count(),
                'blocks'  => BlockResource::collection($block_list)
            ], 200);
        }
        if ($block_list->isNotEmpty()) {
            return response()->json([
                'error'   => false,
                'message' => 'Block found',
                'count'   => $block_list->count(),
                'blocks'  => BlockResource::collection($block_list)
            ], 200);
        }

        return response()->json([
            'error'   => true,
            'message' => 'No block available'
        ], 200);
    }

    //Institute List
    // public function allInstList(Request $request, $type = null)
    // {

    //     if ($request->header('token')) {
    //         $now    =   date('Y-m-d H:i:s');
    //         $stream    =   $request->stream;
    //         $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
    //         if ($token_check) {  // check the token is expire or not
    //             $user_id = $token_check->t_user_id;
    //             $user_role = $request->role_id;
    //             if (!empty($user_role)) {
    //                 $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_role)->pluck('rp_url_id');
    //             } else {
    //                 $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', 2)->pluck('rp_url_id');
    //             }

    //             if (sizeof($role_url_access_id) > 0) {
    //                 $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
    //                 $url_data = array_column($urls, 'url_name');

    //                 if (in_array('institute-stream-wise', $url_data)) { //check url has permission or not
    //                     $inst_res = null;
    //                     $res = null;

    //                     $inst_list = Institute::where('is_active',  1)->orderBy('i_name', 'ASC')->get();

    //                     $res = InstituteResource::collection($inst_list);

    //                     if (sizeof($res) > 0) {
    //                         $reponse = array(
    //                             'error'     =>  false,
    //                             'message'   =>  'Institute found',
    //                             'count'     =>   sizeof($res),
    //                             'instituteList'   =>  $res
    //                         );
    //                         return response(json_encode($reponse), 200);
    //                     } else {
    //                         $reponse = array(
    //                             'error'     =>  true,
    //                             'message'   =>  'No data found'
    //                         );
    //                         return response(json_encode($reponse), 200);
    //                     }
    //                 } else {
    //                     return response()->json([
    //                         'error'     =>  true,
    //                         'message'   =>   "Oops! you don't have sufficient permission"
    //                     ], 401);
    //                 }
    //             } else {
    //                 return response()->json([
    //                     'error'     =>  true,
    //                     'message'   =>   "Oops! you don't have sufficient permission"
    //                 ], 401);
    //             }
    //         } else {
    //             return response()->json([
    //                 'error'     =>  true,
    //                 'message'   =>  'Unable to process your request due to invalid token'
    //             ], 401);
    //         }
    //     } else {
    //         return response()->json([
    //             'error'     =>  true,
    //             'message'   =>  'Unable to process your request due to non availability of token'
    //         ], 401);
    //     }
    // }


    // public function seatMatrix(Request $request)
    // {
    //     if ($request->header('token')) {
    //         $now    =   date('Y-m-d H:i:s');
    //         $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
    //         if ($token_check) {  // check the token is expire or not
    //             $user_id = $token_check->t_user_id;
    //             $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
    //             //return $user_data;
    //             $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

    //             if (sizeof($role_url_access_id) > 0) {
    //                 $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
    //                 $url_data = array_column($urls, 'url_name');

    //                 if (in_array('seat-matrix', $url_data)) { //check url has permission or not
    //                     $validated = Validator::make($request->all(), [
    //                         'trade_code' => ['required']
    //                     ]);

    //                     if ($validated->fails()) {
    //                         return response()->json([
    //                             'error' => true,
    //                             'message' => $validated->errors()
    //                         ]);
    //                     }
    //                     $trade = $request->trade_code;
    //                     $inst_code = $request->inst_code;
    //                     $seatMaster = AlotedAdmittedSeatMaster::query();
    //                     if (!empty($inst_code)) {
    //                         $allseats = $seatMaster->where('sm_inst_code', $inst_code);
    //                     } else {
    //                         $allseats = $seatMaster->where('sm_inst_code', $user_data->u_inst_code);
    //                     }
    //                     $allseats = $seatMaster->where('sm_trade_code', $trade);
    //                     $allseats = $seatMaster->clone()->first();

    //                     //dd($allseats);
    //                     $data = [];
    //                     $cast = cast();
    //                     foreach ($cast as $val) {
    //                         //$val = Str::lower($val);
    //                         $data[casteValue(Str::upper($val))] =   array(
    //                             'initial_seats'     =>    $allseats->{"m_" . $val},
    //                             'alloted_seats'     => ($allseats->{"m_" . $val}) - ($allseats->{$val}),
    //                             'not_alloted_seats'    =>     $allseats->{$val},
    //                             'admitted_seats'     =>     $allseats->{"a_" . $val},
    //                             'available_seats'     => ($allseats->{"m_" . $val}) - ($allseats->{"a_" . $val})
    //                         );
    //                     }

    //                     //return  $data;

    //                     if (sizeof($data) > 0) {
    //                         $reponse = array(
    //                             'error'     =>  false,
    //                             'message'   =>  'List found',
    //                             'count'     =>   sizeof($data),
    //                             'list'   =>  $data,
    //                             'excel_name' => 'seat_matrix_lists'
    //                         );
    //                         return response(json_encode($reponse), 200);
    //                     } else {
    //                         $reponse = array(
    //                             'error'     =>  true,
    //                             'message'   =>  'No data found'
    //                         );
    //                         return response(json_encode($reponse), 200);
    //                     }
    //                 } else {
    //                     return response()->json([
    //                         'error'     =>  true,
    //                         'message'   =>   "Oops! you don't have sufficient permission"
    //                     ], 401);
    //                 }
    //             } else {
    //                 return response()->json([
    //                     'error'     =>  true,
    //                     'message'   =>   "Oops! you don't have sufficient permission"
    //                 ], 401);
    //             }
    //         } else {
    //             return response()->json([
    //                 'error'     =>  true,
    //                 'message'   =>  'Unable to process your request due to invalid token'
    //             ], 401);
    //         }
    //     } else {
    //         return response()->json([
    //             'error'     =>  true,
    //             'message'   =>  'Unable to process your request due to non availability of token'
    //         ], 401);
    //     }
    // }

    public function allReligions(Request $request, $type = null)
    {
        $religion_list = [
            'HINDUISM',
            'ISLAM',
            'CHRISTIANITY',
            'SIKHISM',
            'BUDDHISM',
            'JAINISM',
            'OTHER',
        ];

        $religion_array = array_map(function ($religion) {
            return ['value' => $religion];
        }, $religion_list);
        if (!empty($religion_array)) {

            return response()->json([
                'error'      => false,
                'message'    => 'Religion list found',
                'count'      => count($religion_array),
                'religions'  => $religion_array
            ], 200);
        }

        return response()->json([
            'error'   => true,
            'message' => 'No religion available'
        ], 200);
    }


    //caste list
    public function allCastes(Request $request, $type = null)
    {
        $caste_list = [
            'GENERAL' => 'GENERAL',
            'SC'      => 'SC',
            'ST'      => 'ST',
            'OBC-A'   => 'OBC-A',
            'OBC-B'   => 'OBC-B',
        ];

        if (!empty($caste_list)) {
            return response()->json([
                'error'   => false,
                'message' => 'Caste list found',
                'count'   => count($caste_list),
                'castes'  => $caste_list
            ], 200);
        }

        return response()->json([
            'error'   => true,
            'message' => 'No caste available'
        ], 200);
    }

    // public function allInstAdminList(Request $request)
    // {
    //     if ($request->header('token')) {
    //         $now    =   date('Y-m-d H:i:s');
    //         $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
    //         if ($token_check) {  // check the token is expire or not
    //             $user_id = $token_check->t_user_id;
    //             $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
    //             //return $user_data;
    //             $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

    //             if (sizeof($role_url_access_id) > 0) {
    //                 $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
    //                 $url_data = array_column($urls, 'url_name');

    //                 if (in_array('inst-admin-list', $url_data)) { //check url has permission or not
    //                     $allAdminList = SuperUser::select('u_id', 'u_inst_code', 'u_inst_name', 'u_username')->where('u_role_id', 3)->where('is_active', 1)->orderBy('u_username', 'ASC')->get();


    //                     if (sizeof($allAdminList) > 0) {
    //                         $reponse = array(
    //                             'error'     =>  false,
    //                             'message'   =>  'List found',
    //                             'count'     =>   sizeof($allAdminList),
    //                             'List'   =>  InstAdminResource::collection($allAdminList)
    //                         );
    //                         return response(json_encode($reponse), 200);
    //                     } else {
    //                         $reponse = array(
    //                             'error'     =>  true,
    //                             'message'   =>  'No data found'
    //                         );
    //                         return response(json_encode($reponse), 200);
    //                     }
    //                 } else {
    //                     return response()->json([
    //                         'error'     =>  true,
    //                         'message'   =>   "Oops! you don't have sufficient permission"
    //                     ], 401);
    //                 }
    //             } else {
    //                 return response()->json([
    //                     'error'     =>  true,
    //                     'message'   =>   "Oops! you don't have sufficient permission"
    //                 ], 401);
    //             }
    //         } else {
    //             return response()->json([
    //                 'error'     =>  true,
    //                 'message'   =>  'Unable to process your request due to invalid token'
    //             ], 401);
    //         }
    //     } else {
    //         return response()->json([
    //             'error'     =>  true,
    //             'message'   =>  'Unable to process your request due to non availability of token'
    //         ], 401);
    //     }
    // }

    //Sent mail students who are not pay counselling fees 
    public function allSubdivisions(Request $request, $dist_id = null, $type = null)
    {
        $query = Subdivision::with('district:district_id_pk,district_name')
            ->where('active_status', '1');

        if ($dist_id && is_numeric($dist_id)) {
            $query->where('district_id', $dist_id);
        }

        $subdivision_list = $query->orderBy('name', 'ASC')->get();

        if ($subdivision_list->isNotEmpty()) {
            return response()->json([
                'error'        => false,
                'message'      => 'Subdivision found',
                'count'        => $subdivision_list->count(),
                'subdivisions' => SubdivisionResource::collection($subdivision_list)
            ], 200);
        }

        return response()->json([
            'error'   => $type ? false : true, // if $type is provided, keep error false like your original code
            'message' => 'No subdivision available'
        ], 200);
    }

    public function eligibilityList(Request $request, $type = null)
    {
        // Fetch eligibility data
        $eligibility_list = Eligibility::where('is_active', '1')->orderBy('elgb_exam', 'ASC')->get();

        if ($eligibility_list->isNotEmpty()) {
            return response()->json([
                'error'          => false,
                'message'        => 'Data found',
                'count'          => $eligibility_list->count(),
                'eligibilities'  => EligibilityResource::collection($eligibility_list)
            ], 200);
        }

        return response()->json([
            'error'   => $type ? false : true,  // preserve your $type logic if needed
            'message' => 'No data available'
        ], 200);
    }

    public function boardList(Request $request, $code, $type = null)
    {
        // Fetch boards for the given state code
        $board_list = Board::where('is_active', '1')
            ->where('state_id_fk', $code)
            ->orderBy('board_name', 'ASC')
            ->get();

        if ($board_list->isNotEmpty()) {
            return response()->json([
                'error'   => false,
                'message' => 'Data found',
                'count'   => $board_list->count(),
                'boards'  => EligibilityBoardResource::collection($board_list)
            ], 200);
        }

        return response()->json([
            'error'   => $type ? false : true,  // preserve your $type logic if needed
            'message' => 'No data available'
        ], 200);
    }

    public function eligibilityStateList(Request $request, $type = null)
    {
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

        if ($state_list->isNotEmpty()) {
            return response()->json([
                'error'   => false,
                'message' => 'Data found',
                'count'   => $state_list->count(),
                'list'    => $state_list
            ], 200);
        }

        return response()->json([
            'error'   => $type ? false : true,
            'message' => 'No data available'
        ], 200);
    }


    public function verifierType(Request $request)
    {
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
    }
    public function districtWiseInstitute(Request $request)
    {
        $district_code = $request->district;
        $district = District::select('district_id_pk', 'district_name')
            ->where('district_id_pk', $district_code)
            ->orderBy('district_name', 'asc')->first();
        if (!$district) {
            return response()->json([
                'error'   => true,
                'message' => 'District not found'
            ], 404);
        }

        $data = Institute::where('i_dist_code', $district->district_name)
            ->whereIn('i_type', ['GOVT', 'GOVT(S)'])
            ->where('is_active', 1)
            ->get();

        if ($data) {
            $response = [
                'error'   => false,
                'message' => 'data found',
                'list'    => $data->map(function ($item) {
                    return [
                        'institute_name'     => $item->i_name,
                        'inst_code'  => $item->i_code
                    ];
                })
            ];
        } else {
            $response = [
                'error'   => true,
                'message' => 'No data available'
            ];
        }

        return response()->json($response, 200);
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
        $board_list = Board::where('is_active', '1')->where('state_code', 'OT')->orderBy('board_name', 'ASC')->get();
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
