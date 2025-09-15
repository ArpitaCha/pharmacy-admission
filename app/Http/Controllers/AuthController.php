<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Http\Resources\SuperUserResource;
use App\Models\SuperUser;
use Illuminate\Support\Str;
use App\Models\Token;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Role;
use App\Models\ApplElgbExam;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $auth;
    public $back_url = null;

    public function __construct()
    {
        //$this->auth = new Authentication();
    }

    //site maintenance
    //site maintenance
    public function maintenance(Request $request)
    {
        $now = date('Y-m-d H:i:s');

        $data = DB::table('jexpo_notifications')
            ->where('n_type', 1)
            ->where('is_active', 1)
            ->where('n_published_on', '<', $now)
            ->where('n_expired_on', '>', $now)->first();

        if ($data != null) {
            return response()->json([
                'under_maintenance' => true,
                'message'           => ($data->n_title != null) ? $data->n_title : null
            ]);
        } else {
            return response()->json([
                'under_maintenance' => false,
                'message'           =>  null
            ]);
        }
    }

    //authentication
    public function authenticate(Request $request)
    {
        $now        =   date('Y-m-d H:i:s');
        $today        =    date('Y-m-d');
        $u_phone = $request->user_phone;
        $user_type = $request->user_type;

        try {
            if ($user_type === 'STUDENT') {
                $user = Student::select('u_role_id')->where('s_phone', $u_phone)->first();

                $status = $user ? 'update_profile' : 'new_user';
            } else {
                $user = SuperUser::select('u_role_id')->where('u_phone', $u_phone)->first();

                $status = $user ? 'update_profile' : 'new_user';
            }
            $this->sendOtp($u_phone);

            return response()->json([
                'error'  => false,
                'status' => $status,
                'message' => 'OTP sent successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function sendOtp($to_phone)
    {
        $now        =   date('Y-m-d H:i:s');
        $today        =    date('Y-m-d');
        $otp = env('APP_ENV') === 'local' ? 123456 : rand(111111, 999999);
        $to_phone = $to_phone;
        try {
            if (Otp::where('username', $to_phone)->exists()) {

                $otp_res = Otp::where('username', $to_phone)->first();
                $last_otp_date = substr(trim($otp_res->otp_created_on), 0, 10);
                if ($last_otp_date == $today) {

                    $minutes = getTimeDiffInMinute($now, $otp_res->otp_created_on);
                    if ($otp_res->otp_count < 9) {
                        if ($minutes > 2) {
                            $sms_message_user = "{$otp} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";
                            $send_sms_user = send_sms($to_phone, $sms_message_user);
                            Otp::where('username', $to_phone)->delete();
                            Otp::insert(
                                [
                                    'username' => $to_phone,
                                    'otp' => $otp,
                                    'otp_created_on' => $now,
                                    'otp_count' => intval($otp_res->otp_count) + 1
                                ]
                            );
                            $otp_send = true;
                        } else {

                            $error_message = "Your previous OTP was generated in last 2 minutes";
                            return response()->json([
                                'error'     =>  true,
                                'message'   =>   $error_message
                            ], 200);
                        }
                    } else {

                        $error_type = "otp_exceed";
                        $error_message = "You exceed the OTP generation limit for today. Try again tomorrow.";
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   $error_message
                        ], 200);
                    }
                } else {

                    $sms_message_user = "{$otp} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";
                    $send_sms_user = send_sms($to_phone, $sms_message_user);
                    Otp::where('username', $to_phone)->delete();
                    Otp::insert(
                        [
                            'username' => $to_phone,
                            'otp' => $otp,
                            'otp_created_on' => $now,
                            'otp_count' => 1
                        ]
                    );
                    $otp_send = true;
                }
            } else {
                // dd("hi");
                $otp_send = true;
                $sms_message_user = "{$otp} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";
                $send_sms_user = send_sms($to_phone, $sms_message_user);

                Otp::insert(['username' => $to_phone, 'otp' => $otp, 'otp_created_on' => $now, 'otp_count' => 1]);
            }

            if ($otp_send) {
                $otp_exp_time  = date('Y-m-d H:i:s', strtotime('+120 seconds', strtotime($now)));
                $otp_expire_time = formatDate($otp_exp_time, 'Y-m-d H:i:s', 'M j, Y H:i:s');
                $reponse = array(
                    'error'         =>  false,
                    'message'       =>  'Otp sent successfully',
                    'otp_expire_time' => $otp_expire_time,
                    //'otp' => $otp
                );
                return response(json_encode($reponse), 200);
                //return $response;
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ], 400);
        }
    }
    // public function validateSecurityCode(Request $request)
    // {
    //     $now = date('Y-m-d H:i:s');

    //     $validated = Validator::make($request->all(), [
    //         'user_phone'     => ['required'],
    //         'security_code'  => ['required'],
    //         // 'user_type'      => ['required']
    //     ]);

    //     if ($validated->fails()) {
    //         return response()->json([
    //             'error' => true,
    //             'message' => $validated->errors()
    //         ]);
    //     }

    //     $u_phone = $request->user_phone;
    //     $u_otp = $request->security_code;
    //     $user_type = strtoupper($request->user_type);

    //     // OTP validation
    //     $otp = Otp::where('username', $u_phone)
    //         ->where('otp', $u_otp)
    //         ->first();

    //     if (!$otp) {
    //         return response()->json([
    //             'error'   => true,
    //             'message' => 'Either Phone number and/or security code does not match'
    //         ], 400);
    //     }

    //     try {
    //         $user = null;
    //         $users = [];
    //         $is_student_updated = true;
    //         $s_appl_form_num = null;
    //         $student_inserted_id = null;
    //         $role_name = null;

    //         if ($user_type === 'STUDENT') {
    //             $user = Student::where('s_phone', $u_phone)->first();

    //             if ($user) {
    //                 $is_student_updated = true;
    //                 $student_inserted_id = $user->s_id;
    //                 $s_appl_form_num = $user->s_appl_form_num;
    //                 $role_id = $user->u_role_id;
    //                 $name = $user->s_candidate_name ?? '';
    //             } else {
    //                 // Generate new application number
    //                 $year = date('Y');
    //                 $lastStudent = Student::latest('s_id')->first();
    //                 if ($lastStudent && preg_match('/JEXPO' . $year . '(\d+)/', $lastStudent->s_appl_form_num, $matches)) {
    //                     $nextNumber = (int)$matches[1] + 1;
    //                 } else {
    //                     $nextNumber = 1;
    //                 }

    //                 $s_appl_form_num = 'JEXPO' . $year . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    //                 $defaultRoleId = 2; // or fetch dynamically if needed

    //                 $created = Student::create([
    //                     's_appl_form_num' => $s_appl_form_num,
    //                     's_phone'         => $u_phone,
    //                     'u_role_id'       => $defaultRoleId,
    //                     'created_at'      => $now,

    //                 ]);
    //                 $name = '';
    //                 $student_inserted_id = $created->s_id;
    //                 $role_id = $created->u_role_id;
    //                 $is_student_updated = false;
    //             }

    //             $role_name = Role::where('role_id', $role_id)->value('role_name') ?? null;

    //             $users = [
    //                 's_phone' => $u_phone,
    //                 's_id' => $student_inserted_id,
    //                 'role_id' => $role_name,
    //                 's_appl_form_num' => $s_appl_form_num,
    //                 'u_name' => $name
    //             ];

    //             if (!$is_student_updated && $s_appl_form_num) {
    //                 $users['s_appl_form_num'] = $s_appl_form_num;
    //             }
    //         } else {
    //             $user = SuperUser::where('u_phone', $u_phone)->first();

    //             if (!$user) {
    //                 return response()->json([
    //                     'error'   => true,
    //                     'message' => 'SuperUser not found',
    //                 ], 404);
    //             }

    //             $student_inserted_id = $user->u_id;
    //             $role_id = $user->u_role_id;
    //             $role_name = Role::where('role_id', $role_id)->value('role_name') ?? null;

    //             $users = [
    //                 's_phone' => $u_phone,
    //                 's_id' => $student_inserted_id,
    //                 'role_id' => $role_name,
    //                 'u_inst_code' => $user->u_inst_code ?? null,
    //                 'district' => $user->u_inst_district ?? null,
    //             ];
    //         }

    //         // Generate token
    //         $token = md5($now . rand(10000000, 99999999));
    //         $expiry = date("Y-m-d H:i:s", strtotime('+4 hours', strtotime($now)));

    //         Token::where('t_user_id', $student_inserted_id)->delete();

    //         Token::create([
    //             't_token'         => $token,
    //             't_generated_on'  => $now,
    //             't_expired_on'    => $expiry,
    //             't_user_id'       => $student_inserted_id,
    //             't_user_type'     => $user_type,
    //             't_user_category' => null
    //         ]);

    //         Otp::where('username', $u_phone)
    //             ->where('otp', $u_otp)
    //             ->delete();
    //         $responseData = [
    //             'error'            => false,
    //             'token'            => $token,
    //             'token_expired_on' => $expiry,
    //             'user'             => $users
    //         ];

    //         if ($user_type === 'STUDENT') {
    //             $responseData['is_profile_updated'] = (bool)$is_student_updated;
    //         }

    //         return response()->json($responseData, 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         generateLaravelLog($e);

    //         return response()->json([
    //             'error'   => true,
    //             'code'    => 'INT_00001',
    //             'message' => $e->getMessage()
    //         ]);
    //     }
    // }
    public function validateSecurityCode(Request $request)
    {
        $now = date('Y-m-d H:i:s');

        $validated = Validator::make($request->all(), [
            'user_phone'     => ['required'],
            'security_code'  => ['required'],
            // 'user_type'      => ['required']
        ]);

        if ($validated->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validated->errors()
            ]);
        }

        $u_phone = $request->user_phone;
        $u_otp = $request->security_code;
        $user_type = strtoupper($request->user_type);

        // 🔹 OTP validation
        $otp = Otp::where('username', $u_phone)
            ->where('otp', $u_otp)
            ->first();

        if (!$otp) {
            return response()->json([
                'error'   => true,
                'message' => 'Either Phone number and/or security code does not match'
            ], 400);
        }

        try {
            $user = null;
            $users = [];
            $is_student_updated = false;
            $s_appl_form_num = null;
            $student_inserted_id = null;
            $role_name = null;
            $tab_type   = getStep('');
            if ($user_type === 'STUDENT') {
                $user = Student::where('s_phone', $u_phone)->first();

                if ($user) {
                    // dd("hyt");
                    $student_inserted_id = $user->s_id;
                    $s_appl_form_num = $user->s_appl_form_num;
                    $role_id = $user->u_role_id;
                    $name = $user->s_candidate_name ?? '';
                    $is_student_updated = ($user->is_personal_save == 1);
                } else {
                    //    / dd("else");
                    // Generate new application number
                    $year = date('Y');
                    /* $lastStudent = Student::latest('s_id')->first();
                    if ($lastStudent && preg_match('/PHARM' . $year . '(\d+)/', $lastStudent->s_appl_form_num, $matches)) {
                        $nextNumber = (int)$matches[1] + 1;
                    } else {
                        $nextNumber = 1;
                    }

                    $s_appl_form_num = 'PHARM' . $year . str_pad($nextNumber, 6, '0', STR_PAD_LEFT); */
                    $defaultRoleId = 2; // or fetch dynamically if needed



                    $created = Student::create([
                        # 's_appl_form_num' => $s_appl_form_num,
                        's_phone'         => $u_phone,
                        'u_role_id'       => $defaultRoleId,
                        's_citizenship'     => 'INDIAN',
                        'created_at'      => $now,
                        'is_personal_save' => 0, // new student = profile not updated
                        'tab_type'  =>  $tab_type

                    ]);

                    $lastid = $created->s_id;
                    $s_appl_form_num = 'PHARM' . $year . str_pad($lastid, 6, '0', STR_PAD_LEFT);
                    $created->update([
                        's_appl_form_num' => $s_appl_form_num
                    ]);


                    $elgbExam = ApplElgbExam::create([
                        'exam_appl_form_num' => $s_appl_form_num,
                        'exam_elgb_code_one' => 'MADHYAMIK OR 10TH STANDARD OR EQUIVALENT',
                        'exam_elgb_code_two' => 'HEIGHER SECONDARY',
                    ]);


                    $name = '';
                    $student_inserted_id = $created->s_id;
                    $role_id = $created->u_role_id;
                    $is_student_updated = false;
                }

                $role_name = Role::where('role_id', $role_id)->value('role_name') ?? null;
                // dd("KIO");
                $users = [
                    's_phone' => $u_phone,
                    's_id' => $student_inserted_id,
                    'role_id' => $role_name,
                    's_appl_form_num' => $s_appl_form_num,
                    'u_name' => $name,
                    'tab_type' =>  $tab_type,

                ];

                $responseData['is_profile_updated'] = (bool)$is_student_updated;
            } else {
                // 🔹 SuperUser Login
                $user = SuperUser::where('u_phone', $u_phone)->first();

                if (!$user) {
                    return response()->json([
                        'error'   => true,
                        'message' => 'SuperUser not found',
                    ], 404);
                }

                $student_inserted_id = $user->u_id;
                $role_id = $user->u_role_id;
                $role_name = Role::where('role_id', $role_id)->value('role_name') ?? null;

                $users = [
                    's_phone' => $u_phone,
                    's_id' => $student_inserted_id,
                    'role_id' => $role_name,
                    'u_inst_code' => $user->u_inst_code ?? null,
                    'district' => $user->u_inst_district ?? null,
                ];
            }

            // 🔹 Generate token
            $token = md5($now . rand(10000000, 99999999));
            $expiry = date("Y-m-d H:i:s", strtotime('+4 hours', strtotime($now)));

            Token::where('t_user_id', $student_inserted_id)->delete();

            Token::create([
                't_token'         => $token,
                't_generated_on'  => $now,
                't_expired_on'    => $expiry,
                't_user_id'       => $student_inserted_id,
                't_user_type'     => $user_type,
                't_user_category' => null
            ]);

            // 🔹 Delete OTP after successful validation
            Otp::where('username', $u_phone)
                ->where('otp', $u_otp)
                ->delete();

            $responseData = [
                'error'            => false,
                'token'            => $token,
                'token_expired_on' => $expiry,
                'user'             => $users
            ];

            if ($user_type === 'STUDENT') {
                $responseData['is_profile_updated'] = (bool)$is_student_updated;
            }

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            generateLaravelLog($e);

            return response()->json([
                'error'   => true,
                'code'    => 'INT_00001',
                'message' => $e->getMessage()
            ]);
        }
    }

    //INST or Council change password
    public function changePassword(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user = SuperUser::with(['role:role_id,role_name,role_description'])->where('u_id', $token_check->t_user_id)->first();

                if ($user) {
                    if ($user->u_password == hash("sha512", $request->old_password)) {
                        SuperUser::where('u_id', $user->u_id)
                            ->update(
                                array(
                                    'u_password'        =>  hash("sha512", $request->new_password),
                                    'is_default_password'    =>  0
                                )
                            );

                        $reponse = array(
                            'error'     =>  false,
                            'message'   =>  'Password changed successfully'
                        );
                        return response(json_encode($reponse), 200);
                    } else {
                        $reponse = array(
                            'error'     =>  true,
                            'message'   =>  'Old password is wrong'
                        );
                        return response(json_encode($reponse), 200);
                    }
                } else {
                    $reponse = array(
                        'error'     =>  true,
                        'message'   =>  'No user available'
                    );
                    return response(json_encode($reponse), 404);
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

    //Reset password from council admin for collage admin
    public function resetPassword(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {

                $validated = Validator::make($request->all(), [
                    'user_name' => ['required']
                ]);

                if ($validated->fails()) {
                    return response()->json([
                        'error' => true,
                        'message' => $validated->errors()
                    ]);
                }
                $user_name = $request->user_name;
                $user = SuperUser::with(['role:role_id,role_name,role_description'])->where('u_username', $user_name)->where('is_active', 1)->first();
                if ($user) {
                    SuperUser::where('u_id', $user->u_id)
                        ->update(
                            array(
                                'u_password'        =>  hash("sha512", $request->user_name),
                                'is_default_password'    =>  1
                            )
                        );

                    $reponse = array(
                        'error'     =>  false,
                        'message'   =>  'Password reset successfully'
                    );
                    return response(json_encode($reponse), 200);
                } else {
                    $reponse = array(
                        'error'     =>  true,
                        'message'   =>  'No user available'
                    );
                    return response(json_encode($reponse), 404);
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
}
