<?php

namespace App\Http\Controllers\wbscte;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


use App\Http\Controllers\Controller;

use App\Models\wbscte\StudentChoice;

use App\Models\wbscte\User;
use App\Models\wbscte\SuperUser;
use App\Models\wbscte\Token;


class ReportController extends Controller
{

    protected $auth;
    public $back_url = null;

    public function __construct()
    {
        //$this->auth = new Authentication();
    }

    //get profile registered
    public function getProfileRegister(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $timestamp = date('YmdHis');

            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();

            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('reports/profile-register', $url_data)) { //check url has permission or not
                        try {
                            $fileName = 'profile_register_' . $timestamp . '.csv';
                            $filePath = 'exports/' . $fileName;

                            // Ensure the 'exports' directory exists
                            Storage::disk('public')->makeDirectory('exports');

                            $file = Storage::disk('public')->path($filePath);
                            $handle = fopen($file, 'w+');

                            // Add your column headers
                            fputcsv($handle, ['Application Number', 'Candidate Name', 'Father Name', 'Mother Name', 'DoB', 'Aadhaar Number', 'Phone', 'Email', 'Gender', 'Religion', 'Caste', 'TFW Status', 'EWS Status', 'LLQ Status', 'ExSM Status', 'PWD Status', 'Gen Rank', 'SC Rank', 'ST Rank', 'OBC-A Rank', 'OBC-B Rank', 'TFW Rank', 'EWS Rank', 'LLQ Rank', 'ExSM Rank', 'PWD Rank', 'Photo', 'Home District', 'Schooling District', 'State', 'Lock Manual Status', 'Counselling Payment Status', 'Allotment Status', 'Allotment Accept Status', 'Allotment Upgrade Status', 'Upgrade Payment Status', 'Admitted Status', 'Remarks', 'Choice Count']);

                            DB::table('jexpo_register_student_mv')->orderBy('gen_rank', 'ASC')->chunk(1000, function ($rows) use ($handle) {
                                foreach ($rows as $row) {
                                    fputcsv($handle, [
                                        $row->application_number,
                                        $row->candidate_name,
                                        $row->father_name,
                                        $row->mother_name,
                                        formatDate($row->dob),
                                        decryptHEXFormat($row->aadhaar_num),
                                        $row->phone,
                                        $row->email,
                                        $row->gender,
                                        $row->religion,
                                        $row->caste,
                                        $row->tfw_status,
                                        $row->ews_status,
                                        $row->llq_status,
                                        $row->exsm_status,
                                        $row->pwd_status,
                                        $row->gen_rank,
                                        $row->sc_rank,
                                        $row->st_rank,
                                        $row->obca_rank,
                                        $row->obcb_rank,
                                        $row->tfw_rank,
                                        $row->ews_rank,
                                        $row->llq_rank,
                                        $row->exsm_rank,
                                        $row->pwd_rank,
                                        $row->photo,
                                        $row->home_district,
                                        $row->schooling_district,
                                        $row->state,
                                        $row->lock_manual_status,
                                        $row->counselling_payment_status,
                                        $row->allotment_status,
                                        $row->allotment_accept_status,
                                        $row->allotment_upgrade_status,
                                        $row->upgrade_payment_status,
                                        $row->admitted_status,
                                        $row->remarks,
                                        $row->choice_count,
                                    ]);
                                }
                            });

                            fclose($handle);
                            $fileUrl = Storage::url($filePath);

                            $reponse = array(
                                'error'         =>  false,
                                'message'       =>  'Data found',
                                //'count'         =>  sizeof($candidates),
                                'file_url'      =>  $fileUrl
                            );
                            return response(json_encode($reponse), 200);
                        } catch (Exception $e) {
                            generateLaravelLog($e);
                            return response()->json(
                                array(
                                    'error' => true,
                                    'code' =>    'INT_00001',
                                    'message' => $e->getMessage()
                                )
                            );
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "1 Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "2 Oops! you don't have sufficient permission"
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

    //get profile update or not
    public function getProfileUpdate(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $timestamp = date('YmdHis');

            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();

            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('reports/profile-update', $url_data)) { //check url has permission or not
                        try {
                            $fileName = 'profile_update_' . $timestamp . '.csv';
                            $filePath = 'exports/' . $fileName;

                            // Ensure the 'exports' directory exists
                            Storage::disk('public')->makeDirectory('exports');

                            $file = Storage::disk('public')->path($filePath);
                            $handle = fopen($file, 'w+');

                            // Add your column headers
                            fputcsv($handle, ['Application Number', 'Candidate Name', 'Father Name', 'Mother Name', 'DoB', 'Aadhaar Number', 'Phone', 'Email', 'Gender', 'Religion', 'Caste', 'TFW Status', 'EWS Status', 'LLQ Status', 'ExSM Status', 'PWD Status', 'Gen Rank', 'SC Rank', 'ST Rank', 'OBC-A Rank', 'OBC-B Rank', 'TFW Rank', 'EWS Rank', 'LLQ Rank', 'ExSM Rank', 'PWD Rank', 'Photo', 'Home District', 'Schooling District', 'State', 'Lock Manual Status', 'Counselling Payment Status', 'Allotment Status', 'Allotment Accept Status', 'Allotment Upgrade Status', 'Upgrade Payment Status', 'Admitted Status', 'Remarks', 'Choice Count']);

                            DB::table('jexpo_register_student_mv')->where('home_district', '<>', '')->orderBy('gen_rank', 'ASC')->chunk(1000, function ($rows) use ($handle) {
                                foreach ($rows as $row) {
                                    fputcsv($handle, [
                                        $row->application_number,
                                        $row->candidate_name,
                                        $row->father_name,
                                        $row->mother_name,
                                        formatDate($row->dob),
                                        decryptHEXFormat($row->aadhaar_num),
                                        $row->phone,
                                        $row->email,
                                        $row->gender,
                                        $row->religion,
                                        $row->caste,
                                        $row->tfw_status,
                                        $row->ews_status,
                                        $row->llq_status,
                                        $row->exsm_status,
                                        $row->pwd_status,
                                        $row->gen_rank,
                                        $row->sc_rank,
                                        $row->st_rank,
                                        $row->obca_rank,
                                        $row->obcb_rank,
                                        $row->tfw_rank,
                                        $row->ews_rank,
                                        $row->llq_rank,
                                        $row->exsm_rank,
                                        $row->pwd_rank,
                                        $row->photo,
                                        $row->home_district,
                                        $row->schooling_district,
                                        $row->state,
                                        $row->lock_manual_status,
                                        $row->counselling_payment_status,
                                        $row->allotment_status,
                                        $row->allotment_accept_status,
                                        $row->allotment_upgrade_status,
                                        $row->upgrade_payment_status,
                                        $row->admitted_status,
                                        $row->remarks,
                                        $row->choice_count,
                                    ]);
                                }
                            });

                            fclose($handle);
                            $fileUrl = Storage::url($filePath);

                            $reponse = array(
                                'error'         =>  false,
                                'message'       =>  'Data found',
                                'file_url'      =>  $fileUrl
                            );
                            return response(json_encode($reponse), 200);
                        } catch (Exception $e) {
                            generateLaravelLog($e);
                            return response()->json(
                                array(
                                    'error' => true,
                                    'code' =>    'INT_00001',
                                    'message' => $e->getMessage()
                                )
                            );
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "1 Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "2 Oops! you don't have sufficient permission"
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

    //get choice fillup
    public function getProfileChoiceFillup(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $timestamp = date('YmdHis');

            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();

            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('reports/choice-fillup', $url_data)) { //check url has permission or not
                        try {
                            $fileName = 'choice_fillup_' . $timestamp . '.csv';
                            $filePath = 'exports/' . $fileName;

                            // Ensure the 'exports' directory exists
                            Storage::disk('public')->makeDirectory('exports');

                            $file = Storage::disk('public')->path($filePath);
                            $handle = fopen($file, 'w+');

                            // Add your column headers
                            fputcsv($handle, ['Application Number', 'Candidate Name', 'Father Name', 'Mother Name', 'DoB', 'Aadhaar Number', 'Phone', 'Email', 'Gender', 'Religion', 'Caste', 'TFW Status', 'EWS Status', 'LLQ Status', 'ExSM Status', 'PWD Status', 'Gen Rank', 'SC Rank', 'ST Rank', 'OBC-A Rank', 'OBC-B Rank', 'TFW Rank', 'EWS Rank', 'LLQ Rank', 'ExSM Rank', 'PWD Rank', 'Photo', 'Home District', 'Schooling District', 'State', 'Lock Manual Status', 'Counselling Payment Status', 'Allotment Status', 'Allotment Accept Status', 'Allotment Upgrade Status', 'Upgrade Payment Status', 'Admitted Status', 'Remarks', 'Choice Count']);

                            DB::table('jexpo_register_student_mv')->where('choice_count', '>', 0)->orderBy('gen_rank', 'ASC')->chunk(1000, function ($rows) use ($handle) {
                                foreach ($rows as $row) {
                                    fputcsv($handle, [
                                        $row->application_number,
                                        $row->candidate_name,
                                        $row->father_name,
                                        $row->mother_name,
                                        formatDate($row->dob),
                                        decryptHEXFormat($row->aadhaar_num),
                                        $row->phone,
                                        $row->email,
                                        $row->gender,
                                        $row->religion,
                                        $row->caste,
                                        $row->tfw_status,
                                        $row->ews_status,
                                        $row->llq_status,
                                        $row->exsm_status,
                                        $row->pwd_status,
                                        $row->gen_rank,
                                        $row->sc_rank,
                                        $row->st_rank,
                                        $row->obca_rank,
                                        $row->obcb_rank,
                                        $row->tfw_rank,
                                        $row->ews_rank,
                                        $row->llq_rank,
                                        $row->exsm_rank,
                                        $row->pwd_rank,
                                        $row->photo,
                                        $row->home_district,
                                        $row->schooling_district,
                                        $row->state,
                                        $row->lock_manual_status,
                                        $row->counselling_payment_status,
                                        $row->allotment_status,
                                        $row->allotment_accept_status,
                                        $row->allotment_upgrade_status,
                                        $row->upgrade_payment_status,
                                        $row->admitted_status,
                                        $row->remarks,
                                        $row->choice_count,
                                    ]);
                                }
                            });

                            fclose($handle);
                            $fileUrl = Storage::url($filePath);

                            $reponse = array(
                                'error'         =>  false,
                                'message'       =>  'Data found',
                                'file_url'      =>  $fileUrl
                            );
                            return response(json_encode($reponse), 200);
                        } catch (Exception $e) {
                            generateLaravelLog($e);
                            return response()->json(
                                array(
                                    'error' => true,
                                    'code' =>    'INT_00001',
                                    'message' => $e->getMessage()
                                )
                            );
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "1 Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "2 Oops! you don't have sufficient permission"
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

    //get choice fillup lock
    public function getProfileChoiceFillupLock(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $timestamp = date('YmdHis');

            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();

            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('reports/choice-fillup-lock', $url_data)) { //check url has permission or not
                        try {
                            $fileName = 'choice_lock_' . $timestamp . '.csv';
                            $filePath = 'exports/' . $fileName;

                            // Ensure the 'exports' directory exists
                            Storage::disk('public')->makeDirectory('exports');

                            $file = Storage::disk('public')->path($filePath);
                            $handle = fopen($file, 'w+');

                            // Add your column headers
                            fputcsv($handle, ['Application Number', 'Candidate Name', 'Father Name', 'Mother Name', 'DoB', 'Aadhaar Number', 'Phone', 'Email', 'Gender', 'Religion', 'Caste', 'TFW Status', 'EWS Status', 'LLQ Status', 'ExSM Status', 'PWD Status', 'Gen Rank', 'SC Rank', 'ST Rank', 'OBC-A Rank', 'OBC-B Rank', 'TFW Rank', 'EWS Rank', 'LLQ Rank', 'ExSM Rank', 'PWD Rank', 'Photo', 'Home District', 'Schooling District', 'State', 'Lock Manual Status', 'Counselling Payment Status', 'Allotment Status', 'Allotment Accept Status', 'Allotment Upgrade Status', 'Upgrade Payment Status', 'Admitted Status', 'Remarks', 'Choice Count']);

                            DB::table('jexpo_register_student_mv')->where('choice_count', '>', 0)->whereIn('lock_manual_status', array('YES', 'NO'))->orderBy('lock_manual_status', 'DESC')->orderBy('gen_rank', 'ASC')->chunk(1000, function ($rows) use ($handle) {
                                foreach ($rows as $row) {
                                    fputcsv($handle, [
                                        $row->application_number,
                                        $row->candidate_name,
                                        $row->father_name,
                                        $row->mother_name,
                                        formatDate($row->dob),
                                        decryptHEXFormat($row->aadhaar_num),
                                        $row->phone,
                                        $row->email,
                                        $row->gender,
                                        $row->religion,
                                        $row->caste,
                                        $row->tfw_status,
                                        $row->ews_status,
                                        $row->llq_status,
                                        $row->exsm_status,
                                        $row->pwd_status,
                                        $row->gen_rank,
                                        $row->sc_rank,
                                        $row->st_rank,
                                        $row->obca_rank,
                                        $row->obcb_rank,
                                        $row->tfw_rank,
                                        $row->ews_rank,
                                        $row->llq_rank,
                                        $row->exsm_rank,
                                        $row->pwd_rank,
                                        $row->photo,
                                        $row->home_district,
                                        $row->schooling_district,
                                        $row->state,
                                        $row->lock_manual_status,
                                        $row->counselling_payment_status,
                                        $row->allotment_status,
                                        $row->allotment_accept_status,
                                        $row->allotment_upgrade_status,
                                        $row->upgrade_payment_status,
                                        $row->admitted_status,
                                        $row->remarks,
                                        $row->choice_count,
                                    ]);
                                }
                            });

                            fclose($handle);
                            $fileUrl = Storage::url($filePath);

                            $reponse = array(
                                'error'         =>  false,
                                'message'       =>  'Data found',
                                'file_url'      =>  $fileUrl
                            );
                            return response(json_encode($reponse), 200);
                        } catch (Exception $e) {
                            generateLaravelLog($e);
                            return response()->json(
                                array(
                                    'error' => true,
                                    'code' =>    'INT_00001',
                                    'message' => $e->getMessage()
                                )
                            );
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "1 Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "2 Oops! you don't have sufficient permission"
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

    //get choice fillup payment
    public function getProfileChoiceFillupPayment(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $timestamp = date('YmdHis');

            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();

            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('reports/choice-fillup-payment', $url_data)) { //check url has permission or not
                        try {
                            $fileName = 'choice_payment_' . $timestamp . '.csv';
                            $filePath = 'exports/' . $fileName;

                            // Ensure the 'exports' directory exists
                            Storage::disk('public')->makeDirectory('exports');

                            $file = Storage::disk('public')->path($filePath);
                            $handle = fopen($file, 'w+');

                            // Add your column headers
                            fputcsv($handle, ['Application Number', 'Candidate Name', 'Father Name', 'Mother Name', 'DoB', 'Aadhaar Number', 'Phone', 'Email', 'Gender', 'Religion', 'Caste', 'TFW Status', 'EWS Status', 'LLQ Status', 'ExSM Status', 'PWD Status', 'Gen Rank', 'SC Rank', 'ST Rank', 'OBC-A Rank', 'OBC-B Rank', 'TFW Rank', 'EWS Rank', 'LLQ Rank', 'ExSM Rank', 'PWD Rank', 'Photo', 'Home District', 'Schooling District', 'State', 'Lock Manual Status', 'Counselling Payment Status', 'Allotment Status', 'Allotment Accept Status', 'Allotment Upgrade Status', 'Upgrade Payment Status', 'Admitted Status', 'Remarks', 'Choice Count']);

                            DB::table('jexpo_register_student_mv')->where('choice_count', '>', 0)->where('lock_manual_status', 'YES')
                                ->whereIn('counselling_payment_status', array('YES', 'NO'))
                                ->orderBy('counselling_payment_status', 'DESC')->orderBy('gen_rank', 'ASC')->chunk(1000, function ($rows) use ($handle) {
                                    foreach ($rows as $row) {
                                        fputcsv($handle, [
                                            $row->application_number,
                                            $row->candidate_name,
                                            $row->father_name,
                                            $row->mother_name,
                                            formatDate($row->dob),
                                            decryptHEXFormat($row->aadhaar_num),
                                            $row->phone,
                                            $row->email,
                                            $row->gender,
                                            $row->religion,
                                            $row->caste,
                                            $row->tfw_status,
                                            $row->ews_status,
                                            $row->llq_status,
                                            $row->exsm_status,
                                            $row->pwd_status,
                                            $row->gen_rank,
                                            $row->sc_rank,
                                            $row->st_rank,
                                            $row->obca_rank,
                                            $row->obcb_rank,
                                            $row->tfw_rank,
                                            $row->ews_rank,
                                            $row->llq_rank,
                                            $row->exsm_rank,
                                            $row->pwd_rank,
                                            $row->photo,
                                            $row->home_district,
                                            $row->schooling_district,
                                            $row->state,
                                            $row->lock_manual_status,
                                            $row->counselling_payment_status,
                                            $row->allotment_status,
                                            $row->allotment_accept_status,
                                            $row->allotment_upgrade_status,
                                            $row->upgrade_payment_status,
                                            $row->admitted_status,
                                            $row->remarks,
                                            $row->choice_count,
                                        ]);
                                    }
                                });

                            fclose($handle);
                            $fileUrl = Storage::url($filePath);

                            $reponse = array(
                                'error'         =>  false,
                                'message'       =>  'Data found',
                                'file_url'      =>  str_replace("exports", "app/public/exports", $fileUrl)
                            );
                            return response(json_encode($reponse), 200);
                        } catch (Exception $e) {
                            generateLaravelLog($e);
                            return response()->json(
                                array(
                                    'error' => true,
                                    'code' =>    'INT_00001',
                                    'message' => $e->getMessage()
                                )
                            );
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "1 Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "2 Oops! you don't have sufficient permission"
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

    //get allotment received
    public function getProfileAllotment(Request $request, $college_code = null)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('reports/allotment', $url_data)) { //check url has permission or not

                        /* // First query
                        $query1 = DB::table('jexpo_register_student')
                            ->select('s_id')
                            ->where('is_alloted', 1)
                            ->where('last_round_adm_status', 1);
                            //->where('s_inst_code', 'APC');

                        // Second query
                        $query2 = DB::table('jexpo_register_student AS rs')
                            ->join('jexpo_choice_student AS cs', 'cs.ch_stu_id', '=', 'rs.s_id')
                            ->select('rs.s_id')
                            ->where('rs.is_alloted', 1)
                            ->where('rs.last_round_adm_status', 0)
                            ->where('cs.is_alloted', 1);
                            //->where('cs.ch_inst_code', 'APC');

                        // Combine queries with UNION
                        $combinedQuery = $query1->union($query2)
                            ->orderBy('s_id', 'asc')
                            ->get();

                        $id_arr = $combinedQuery->pluck('s_id')->toArray();
                        //print_r($id_arr); exit(); */

                        $results = DB::table('jexpo_register_student as rs')
                            ->select([
                                'rs.s_id as student_id',
                                'rs.s_appl_form_num as appl_num',
                                'rs.s_candidate_name as candidate_name',
                                'rs.s_father_name as father_name',
                                'rs.s_mother_name as mother_name',
                                'rs.s_phone as phone',
                                'rs.s_email as email',
                                'rs.s_aadhar_no as aadhaar',
                                'rs.s_dob as dob',
                                'rs.s_gender as gender',
                                'rs.s_religion as religion',
                                'rs.s_caste as caste',
                                DB::raw("CASE WHEN rs.s_tfw = 1 THEN 'YES' ELSE 'NO' END as tfw_status"),
                                DB::raw("CASE WHEN rs.s_ews = 1 THEN 'YES' ELSE 'NO' END as ews_status"),
                                DB::raw("CASE WHEN rs.s_llq = 1 THEN 'YES' ELSE 'NO' END as llq_status"),
                                DB::raw("CASE WHEN rs.s_exsm = 1 THEN 'YES' ELSE 'NO' END as exsm_status"),
                                DB::raw("CASE WHEN rs.s_pwd = 1 THEN 'YES' ELSE 'NO' END as pwd_status"),
                                'rs.s_gen_rank as gen_rank',
                                'hd.d_name as home_district',
                                'sd.d_name as schooling_district',
                                'sm.state_name as state',
                                DB::raw("CASE WHEN rs.is_lock_manual = 1 THEN 'YES' ELSE 'NO' END as lock_manual_status"),
                                DB::raw("CASE WHEN rs.is_payment = 1 THEN 'YES' ELSE 'NO' END as counselling_payment_status"),
                                DB::raw("CASE WHEN rs.is_alloted = 1 THEN 'YES' ELSE 'NO' END as allotment_status"),
                                DB::raw("CASE WHEN rs.is_allotment_accept = 1 THEN 'YES' ELSE 'NO' END as allotment_accept_status"),
                                DB::raw("CASE WHEN rs.is_upgrade = 1 THEN 'YES' ELSE 'NO' END as allotment_upgrade_status"),
                                DB::raw("CASE WHEN rs.is_upgrade_payment = 1 THEN 'YES' ELSE 'NO' END as upgrade_payment_status"),
                                DB::raw("CASE
                                        WHEN rs.s_admited_status = 1 THEN 'YES'
                                        WHEN rs.s_admited_status = 2 THEN 'NO'
                                    ELSE ''
                                    END as admitted_status"),
                                'rs.s_inst_code as institute_code',
                                'institute_master.i_name as institute_name',
                                'rs.s_trade_code as trade_code',
                                'trade_master.t_name as trade_name',
                                'rs.s_alloted_category as allotment_category',
                                'rs.s_alloted_round as allotment_round'
                            ])
                            ->leftJoin('district_master as hd', 'hd.d_id', '=', 'rs.s_home_district')
                            ->leftJoin('district_master as sd', 'sd.d_id', '=', 'rs.s_home_district')
                            ->leftJoin('jexpo_state_master as sm', 'sm.state_id_pk', '=', 'rs.s_state_id')
                            ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                            ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                            ->where('rs.is_alloted', 1)
                            ->where('last_round_adm_status', 1);

                        $current_round_ids = DB::table('jexpo_register_student')->select('s_id')->where('last_round_adm_status', 0)->where('is_alloted', 1)->orderBy('s_id')->get()->pluck('s_id')->toArray();

                        $results_new = DB::table('jexpo_register_student as rs')
                            ->select([
                                'rs.s_id as student_id',
                                'rs.s_appl_form_num as appl_num',
                                'rs.s_candidate_name as candidate_name',
                                'rs.s_father_name as father_name',
                                'rs.s_mother_name as mother_name',
                                'rs.s_phone as phone',
                                'rs.s_email as email',
                                'rs.s_aadhar_no as aadhaar',
                                'rs.s_dob as dob',
                                'rs.s_gender as gender',
                                'rs.s_religion as religion',
                                'rs.s_caste as caste',
                                DB::raw("CASE WHEN rs.s_tfw = 1 THEN 'YES' ELSE 'NO' END as tfw_status"),
                                DB::raw("CASE WHEN rs.s_ews = 1 THEN 'YES' ELSE 'NO' END as ews_status"),
                                DB::raw("CASE WHEN rs.s_llq = 1 THEN 'YES' ELSE 'NO' END as llq_status"),
                                DB::raw("CASE WHEN rs.s_exsm = 1 THEN 'YES' ELSE 'NO' END as exsm_status"),
                                DB::raw("CASE WHEN rs.s_pwd = 1 THEN 'YES' ELSE 'NO' END as pwd_status"),
                                'rs.s_gen_rank as gen_rank',
                                'hd.d_name as home_district',
                                'sd.d_name as schooling_district',
                                'sm.state_name as state',
                                DB::raw("CASE WHEN rs.is_lock_manual = 1 THEN 'YES' ELSE 'NO' END as lock_manual_status"),
                                DB::raw("CASE WHEN rs.is_payment = 1 THEN 'YES' ELSE 'NO' END as counselling_payment_status"),
                                DB::raw("CASE WHEN rs.is_alloted = 1 THEN 'YES' ELSE 'NO' END as allotment_status"),
                                DB::raw("CASE WHEN rs.is_allotment_accept = 1 THEN 'YES' ELSE 'NO' END as allotment_accept_status"),
                                DB::raw("CASE WHEN rs.is_upgrade = 1 THEN 'YES' ELSE 'NO' END as allotment_upgrade_status"),
                                DB::raw("CASE WHEN rs.is_upgrade_payment = 1 THEN 'YES' ELSE 'NO' END as upgrade_payment_status"),
                                DB::raw("CASE
                                        WHEN rs.s_admited_status = 1 THEN 'YES'
                                        WHEN rs.s_admited_status = 2 THEN 'NO'
                                    ELSE ''
                                    END as admitted_status"),
                                'cs.ch_inst_code as institute_code',
                                'institute_master.i_name as institute_name',
                                'cs.ch_trade_code as trade_code',
                                'trade_master.t_name as trade_name',
                                'cs.ch_alloted_category as allotment_category',
                                'cs.ch_alloted_round as allotment_round'
                            ])
                            ->join('jexpo_choice_student AS cs', 'cs.ch_stu_id', '=', 'rs.s_id')
                            ->leftJoin('district_master as hd', 'hd.d_id', '=', 'rs.s_home_district')
                            ->leftJoin('district_master as sd', 'sd.d_id', '=', 'rs.s_home_district')
                            ->leftJoin('jexpo_state_master as sm', 'sm.state_id_pk', '=', 'rs.s_state_id')
                            ->leftJoin('institute_master', 'institute_master.i_code', '=', 'cs.ch_inst_code')
                            ->leftJoin('trade_master', 'trade_master.t_code', '=', 'cs.ch_trade_code')
                            ->whereIn('cs.ch_stu_id', $current_round_ids)
                            ->where('cs.is_alloted', 1)
                            ->where('ch_fillup_time', '>', '2024-07-14 10:00:00');


                        if ($college_code == null) {
                            if (isset($request->inst_code) && ($request->inst_code != "")) {
                                $results->where('s_inst_code', $request->inst_code);
                                $results_new->where('ch_inst_code', $request->inst_code);
                            }
                        } else {
                            $results->where('s_inst_code', $college_code);
                            $results_new->where('ch_inst_code', $college_code);
                        }

                        if (isset($request->trade_code) && ($request->trade_code != "")) {
                            $results->where('s_trade_code', $request->trade_code);
                            $results_new->where('ch_trade_code', $request->trade_code);
                        }
                        if (isset($request->student_name) && ($request->student_name != "")) {
                            $results->where('s_candidate_name', 'LIKE', '%' . $request->student_name . '%');
                            $results_new->where('s_candidate_name', 'LIKE', '%' . $request->student_name . '%');
                        }

                        if (isset($request->student_phone) && ($request->student_phone != "")) {
                            $results->where('s_phone', $request->student_phone);
                            $results_new->where('s_phone', $request->student_phone);
                        }

                        $results = $results->get();
                        $results_new = $results_new->get();

                        $data = $results_new->merge($results);

                        $reponse = array(
                            'error'         =>  false,
                            'total'         =>  count($data),
                            'message'       =>  'Data found',
                            'candidates'    =>  $data
                        );
                        return response(json_encode($reponse), 200);
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "1 Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "2 Oops! you don't have sufficient permission"
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
    //Csv report
    public function getCounsellingReport(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $timestamp = date('YmdHis');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('reports/allotment', $url_data)) { //check url has permission or not
                        $type = $request->type;
                        $sub_type = $request->sub_type;
                        $results = [];
                        try {
                            if ($type == 'counselling' && ($sub_type == 'govt' || $sub_type == 'pvt')) { //Govt & pvt counselling through
                                $results = DB::table('jexpo_register_student as rs')
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'rs.s_trade_code',
                                        'rs.s_candidate_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'rs.s_alloted_category',
                                        'rs.s_admited_status',
                                        'rs.s_pwd',
                                        'rs.s_tfw',
                                        'rs.s_llq',
                                        'rs.s_aadhar_no'
                                    ])
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                    ->where('rs.s_admited_status', 1);
                                if ($type == 'counselling' && $sub_type == 'govt') {
                                    $results->where('institute_master.i_type', '!=', 'PVT');
                                    $results->orderBy('institute_master.i_name', 'ASC');
                                } else if ($type == 'counselling' && $sub_type != 'govt') {
                                    $results->where('institute_master.i_type', '=', 'PVT');
                                    $results->orderBy('institute_master.i_name', 'ASC');
                                }
                                $results = $results->get()->map(function ($user, $key) {
                                    return [
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Trade Name' => $user->trade_name,
                                        'Trade Code' => $user->s_trade_code,
                                        'Candidate Name' => $user->s_candidate_name,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Email' => $user->s_email,
                                        'Gender' => $user->s_gender,
                                        'PWD' => ($user->s_pwd == '0') ? 'NO' : 'Yes',
                                        'TFW' => ($user->s_tfw == '0') ? 'NO' : 'Yes',
                                        'LLQ' => ($user->s_llq == '0') ? 'NO' : 'Yes',
                                        'Caste' => $user->s_caste,
                                        'Alloted Category' => $user->s_alloted_category,
                                        'Admitted Status' => 'Admited'
                                    ];
                                });
                            } else if ($type == 'direct' && $sub_type == 'govt') { //Govt Spot Direct
                                $results = DB::table('jexpo_spot_student_master as rs')
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'jexpo_spot_student_allotment.alloted_trade',
                                        'rs.s_candidate_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'jexpo_spot_student_allotment.alloted_category',
                                        'rs.s_final_allotment',
                                        'rs.s_pwd',
                                        'rs.s_tfw',
                                        'rs.s_aadhar_no'
                                    ])
                                    ->leftJoin('jexpo_spot_student_allotment', 'jexpo_spot_student_allotment.stu_id', '=', 'rs.s_id')
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'jexpo_spot_student_allotment.alloted_trade')
                                    ->where('jexpo_spot_student_allotment.alloted_status', 1)
                                    ->where('rs.s_final_allotment', 1);
                                if ($type == 'counselling' && $sub_type == 'govt') {
                                    $results->where('institute_master.i_type', '!=', 'PVT');
                                    $results->orderBy('institute_master.i_name', 'ASC');
                                }
                                $results = $results->get()->map(function ($user, $key) {
                                    return [
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Trade Name' => $user->trade_name,
                                        'Trade Code' => $user->alloted_trade,
                                        'Candidate Name' => $user->s_candidate_name,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Email' => $user->s_email,
                                        'Gender' => $user->s_gender,
                                        'PWD' => ($user->s_pwd == '0') ? 'NO' : 'Yes',
                                        'TFW' => ($user->s_tfw == '0') ? 'NO' : 'Yes',
                                        'LLQ' => 'NO',
                                        'Caste' => $user->s_caste,
                                        'Alloted Category' => $user->alloted_category,
                                        'Admitted Status' => 'Admited'
                                    ];
                                });
                            } else if ($type == 'direct' && $sub_type == 'pvt') { //Management qouta direct counselling admission for pvt

                                $council_admission = DB::table('jexpo_management_register_student_pvt as rs')
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'rs.s_trade_code',
                                        'rs.s_candidate_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'rs.s_alloted_category',
                                        'rs.s_admitted_status',
                                        'rs.s_aadhar_no'
                                    ])
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                    ->where('rs.s_admitted_status', 1);
                                if ($type == 'direct' && $sub_type != 'govt') {
                                    $council_admission->where('institute_master.i_type', '=', 'PVT');
                                    $council_admission->orderBy('institute_master.i_name', 'ASC');
                                }
                                $results = $council_admission->get()->map(function ($user, $key) {
                                    return [
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Trade Name' => $user->trade_name,
                                        'Trade Code' => $user->s_trade_code,
                                        'Candidate Name' => $user->s_candidate_name,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Email' => $user->s_email,
                                        'Gender' => $user->s_gender,
                                        'PWD' => 'NO',
                                        'TFW' => 'NO',
                                        'LLQ' => 'NO',
                                        'Caste' => $user->s_caste,
                                        'Alloted Category' => $user->s_alloted_category,
                                        'Admitted Status' => 'Admited'
                                    ];
                                });
                            } else if ($type == 'direct' && $sub_type == 'management') { //Management qouta self admission for pvt
                                $self_admission = DB::table('jexpo_management_register_student as rs')
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'rs.s_trade_code',
                                        'rs.s_candidate_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'rs.s_alloted_category',
                                        'rs.s_admitted_status',
                                        'rs.s_aadhar_no'
                                    ])
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                    ->where('rs.s_admitted_status', 1);
                                if ($type == 'direct' && $sub_type != 'govt') {
                                    $self_admission->where('institute_master.i_type', '=', 'PVT');
                                    $self_admission->orderBy('institute_master.i_name', 'ASC');
                                }
                                $results  = $self_admission->get()->map(function ($user, $key) {
                                    return [
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Trade Name' => $user->trade_name,
                                        'Trade Code' => $user->s_trade_code,
                                        'Candidate Name' => $user->s_candidate_name,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Email' => $user->s_email,
                                        'Gender' => $user->s_gender,
                                        'PWD' => 'NO',
                                        'TFW' => 'NO',
                                        'LLQ' => 'NO',
                                        'Caste' => $user->s_caste,
                                        'Alloted Category' => $user->s_alloted_category,
                                        'Admitted Status' => 'Admited'
                                    ];
                                });
                            } else if ($type == 'direct' && $sub_type == 'hill') { //Govt hill admission (DAR,KLM,MIR)
                                $results = DB::table('jexpo_hill_register_student as rs')
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'rs.s_trade_code',
                                        'rs.s_candidate_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'rs.s_alloted_category',
                                        'rs.s_admitted_status',
                                        'rs.s_aadhar_no'
                                    ])
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                    ->where('rs.s_admitted_status', 1)
                                    ->where('institute_master.i_type', '!=', 'PVT')
                                    ->orderBy('institute_master.i_name', 'ASC');

                                $results = $results->get()->map(function ($user, $key) {
                                    return [
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Trade Name' => $user->trade_name,
                                        'Trade Code' => $user->s_trade_code,
                                        'Candidate Name' => $user->s_candidate_name,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Email' => $user->s_email,
                                        'Gender' => $user->s_gender,
                                        'PWD' => 'NO',
                                        'TFW' => 'NO',
                                        'LLQ' => 'NO',
                                        'Caste' => $user->s_caste,
                                        'Alloted Category' => $user->s_alloted_category,
                                        'Admitted Status' => 'Admited'
                                    ];
                                });
                            }else if($type == 'all' && $sub_type == 'all'){//All reports in one
                                $allotment = DB::table('jexpo_register_student as rs')//Allotment GOVT & PVT
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'rs.s_trade_code',
                                        'rs.s_candidate_name',
                                        'rs.s_first_name',
                                        'rs.s_middle_name',
                                        'rs.s_last_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'rs.s_alloted_category',
                                        'rs.s_admited_status',
                                        'rs.s_pwd',
                                        'rs.s_tfw',
                                        'rs.s_llq',
                                        'rs.s_aadhar_no',
                                        'institute_master.i_type as inst_type'
                                    ])
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                    ->where('rs.s_admited_status', 1)
                                    ->orderBy('institute_master.i_name', 'ASC');
                                $allotment = $allotment->get()->map(function ($user, $key) {
                                    $first_name = $middle_name = $last_name = NULL;
                                    if($user->s_candidate_name){
                                        $fullname = explode(" ",$user->s_candidate_name);
                                        if(sizeof($fullname) == 3){
                                            $first_name = $fullname[0];
                                            $middle_name = $fullname[1];
                                            $last_name = $fullname[2];
                                        }else if(sizeof($fullname) == 2){
                                            $first_name = $fullname[0];
                                            $last_name = $fullname[1];
                                        }else if(sizeof($fullname) == 1){
                                            $first_name = $fullname[0];
                                        }
                                    }
                                    return [
                                        'First Name' => $first_name,
                                        'Middle Name' => $middle_name,
                                        'Last Name' => $last_name,
                                        //'Candidate Name' => $user->s_candidate_name,
                                        'Email' => $user->s_email,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Inst Type' => NULL,
                                        'Inst category' => $user->inst_type,
                                        'Course Name' => $user->trade_name,
                                        'Course Code' => $user->s_trade_code,
                                        'Exam Type Name' => 'Jexpo',
                                        'Exam Type Id' => NULL,
                                        'Academic Year' => '2024-25'
                                    ];
                                });

                                $spot = DB::table('jexpo_spot_student_master as rs')//Spot GOVT
                                ->select([
                                    'institute_master.i_name as institute_name',
                                    'rs.s_inst_code as inst_code',
                                    'trade_master.t_name as trade_name',
                                    'jexpo_spot_student_allotment.alloted_trade',
                                    'rs.s_candidate_name',
                                    'rs.s_phone',
                                    'rs.s_email',
                                    'rs.s_gender',
                                    'rs.s_caste',
                                    'jexpo_spot_student_allotment.alloted_category',
                                    'rs.s_final_allotment',
                                    'rs.s_pwd',
                                    'rs.s_tfw',
                                    'rs.s_aadhar_no',
                                    'rs.s_first_name',
                                    'rs.s_middle_name',
                                    'rs.s_last_name',
                                    'institute_master.i_type as inst_type'
                                ])
                                ->leftJoin('jexpo_spot_student_allotment', 'jexpo_spot_student_allotment.stu_id', '=', 'rs.s_id')
                                ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                ->leftJoin('trade_master', 'trade_master.t_code', '=', 'jexpo_spot_student_allotment.alloted_trade')
                                ->where('jexpo_spot_student_allotment.alloted_status', 1)
                                ->where('rs.s_final_allotment', 1)
                                ->where('institute_master.i_type', '!=', 'PVT')
                                ->orderBy('institute_master.i_name', 'ASC');
                            
                                $spot = $spot->get()->map(function ($user, $key) {
                                    return [
                                        'First Name' => $user->s_first_name,
                                        'Middle Name' => $user->s_middle_name,
                                        'Last Name' => $user->s_last_name,
                                        //'Candidate Name' => $user->s_candidate_name,
                                        'Email' => $user->s_email,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Inst Type' => NULL,
                                        'Inst category' => $user->inst_type,
                                        'Course Name' => $user->trade_name,
                                        'Course Code' => $user->alloted_trade,
                                        'Exam Type Name' => 'Jexpo',
                                        'Exam Type Id' => NULL,
                                        'Academic Year' => '2024-25'
                                    ];
                                });
                                $council_admission = DB::table('jexpo_management_register_student_pvt as rs')//Management Quota Direct
                                ->select([
                                    'institute_master.i_name as institute_name',
                                    'rs.s_inst_code as inst_code',
                                    'trade_master.t_name as trade_name',
                                    'rs.s_trade_code',
                                    'rs.s_candidate_name',
                                    'rs.s_phone',
                                    'rs.s_email',
                                    'rs.s_gender',
                                    'rs.s_caste',
                                    'rs.s_alloted_category',
                                    'rs.s_admitted_status',
                                    'rs.s_aadhar_no',
                                    'institute_master.i_type as inst_type'
                                ])
                                ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                ->where('rs.s_admitted_status', 1)
                                ->where('institute_master.i_type', '=', 'PVT')
                                ->orderBy('institute_master.i_name', 'ASC');
                            
                                $council_admission = $council_admission->get()->map(function ($user, $key) {
                                    $first_name = $middle_name = $last_name = NULL;
                                    if($user->s_candidate_name){
                                            $fullname = explode(" ",$user->s_candidate_name);
                                            if(sizeof($fullname) == 3){
                                                $first_name = $fullname[0];
                                                $middle_name = $fullname[1];
                                                $last_name = $fullname[2];
                                            }else if(sizeof($fullname) == 2){
                                                $first_name = $fullname[0];
                                                $last_name = $fullname[1];
                                            }else if(sizeof($fullname) == 1){
                                                $first_name = $fullname[0];
                                            }
                                    }
                                    return [
                                        'First Name' => $first_name,
                                        'Middle Name' => $middle_name,
                                        'Last Name' => $last_name,
                                        //'Candidate Name' => $user->s_candidate_name,
                                        'Email' => $user->s_email,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Inst Type' => NULL,
                                        'Inst category' => $user->inst_type,
                                        'Course Name' => $user->trade_name,
                                        'Course Code' => $user->s_trade_code,
                                        'Exam Type Name' => 'Jexpo',
                                        'Exam Type Id' => NULL,
                                        'Academic Year' => '2024-25'
                                    ];
                                });

                                $self_admission = DB::table('jexpo_management_register_student as rs')//Management Quota self admission
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'rs.s_trade_code',
                                        'rs.s_candidate_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'rs.s_alloted_category',
                                        'rs.s_admitted_status',
                                        'rs.s_aadhar_no',
                                        'institute_master.i_type as inst_type'
                                    ])
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                    ->where('rs.s_admitted_status', 1)
                                    ->where('institute_master.i_type', '=', 'PVT')
                                    ->orderBy('institute_master.i_name', 'ASC');
                                
                                $self_admission  = $self_admission->get()->map(function ($user, $key) {
                                    $first_name = $middle_name = $last_name = NULL;
                                    if($user->s_candidate_name){
                                            $fullname = explode(" ",$user->s_candidate_name);
                                            if(sizeof($fullname) == 3){
                                                $first_name = $fullname[0];
                                                $middle_name = $fullname[1];
                                                $last_name = $fullname[2];
                                            }else if(sizeof($fullname) == 2){
                                                $first_name = $fullname[0];
                                                $last_name = $fullname[1];
                                            }else if(sizeof($fullname) == 1){
                                                $first_name = $fullname[0];
                                            }
                                    }
                                    return [
                                        'First Name' => $first_name,
                                        'Middle Name' => $middle_name,
                                        'Last Name' => $last_name,
                                        //'Candidate Name' => $user->s_candidate_name,
                                        'Email' => $user->s_email,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Inst Type' => NULL,
                                        'Inst category' => $user->inst_type,
                                        'Course Name' => $user->trade_name,
                                        'Course Code' => $user->s_trade_code,
                                        'Exam Type Name' => 'Jexpo',
                                        'Exam Type Id' => NULL,
                                        'Academic Year' => '2024-25'
                                    ];
                                });

                                $hill = DB::table('jexpo_hill_register_student as rs')//Hill Admission
                                    ->select([
                                        'institute_master.i_name as institute_name',
                                        'rs.s_inst_code as inst_code',
                                        'trade_master.t_name as trade_name',
                                        'rs.s_trade_code',
                                        'rs.s_candidate_name',
                                        'rs.s_phone',
                                        'rs.s_email',
                                        'rs.s_gender',
                                        'rs.s_caste',
                                        'rs.s_alloted_category',
                                        'rs.s_admitted_status',
                                        'rs.s_aadhar_no',
                                        'institute_master.i_type as inst_type'
                                    ])
                                    ->leftJoin('institute_master', 'institute_master.i_code', '=', 'rs.s_inst_code')
                                    ->leftJoin('trade_master', 'trade_master.t_code', '=', 'rs.s_trade_code')
                                    ->where('rs.s_admitted_status', 1)
                                    ->where('institute_master.i_type', '!=', 'PVT')
                                    ->orderBy('institute_master.i_name', 'ASC');

                                $hill = $hill->get()->map(function ($user, $key) {
                                    $first_name = $middle_name = $last_name = NULL;
                                    if($user->s_candidate_name){
                                            $fullname = explode(" ",$user->s_candidate_name);
                                            if(sizeof($fullname) == 3){
                                                $first_name = $fullname[0];
                                                $middle_name = $fullname[1];
                                                $last_name = $fullname[2];
                                            }else if(sizeof($fullname) == 2){
                                                $first_name = $fullname[0];
                                                $last_name = $fullname[1];
                                            }else if(sizeof($fullname) == 1){
                                                $first_name = $fullname[0];
                                            }
                                    }
                                    return [
                                        'First Name' => $first_name,
                                        'Middle Name' => $middle_name,
                                        'Last Name' => $last_name,
                                        //'Candidate Name' => $user->s_candidate_name,
                                        'Email' => $user->s_email,
                                        'Phone' => $user->s_phone,
                                        'Aadhar No' => decryptHEXFormat($user->s_aadhar_no),
                                        'Institute Name' => $user->institute_name,
                                        'Inst Code' => $user->inst_code,
                                        'Inst Type' => NULL,
                                        'Inst category' => $user->inst_type,
                                        'Course Name' => $user->trade_name,
                                        'Course Code' => $user->s_trade_code,
                                        'Exam Type Name' => 'Jexpo',
                                        'Exam Type Id' => NULL,
                                        'Academic Year' => '2024-25'
                                    ];
                                });

                                if(sizeof($allotment) > 0 && sizeof($spot) > 0 && sizeof($council_admission) > 0 && sizeof($self_admission) > 0 && sizeof($hill) > 0){
                                    $results = $allotment->merge($spot)->merge($council_admission)->merge($self_admission)->merge($hill);
                                }else{
                                    $results = [];
                                }


                            }
                            //return $results;

                            if (sizeof($results) > 0) {
                                $reponse = array(
                                    'error'         =>  false,
                                    'message'       =>  'Data found',
                                    'count'         =>  sizeof($results),
                                    'data'      =>  $results
                                );
                                return response(json_encode($reponse), 200);
                            } else {
                                $reponse = array(
                                    'error'         =>  false,
                                    'message'       =>  'No Data found',
                                    'count'         =>  0,
                                    'data'      =>  []
                                );
                                return response(json_encode($reponse), 200);
                            }
                        } catch (Exception $e) {
                            generateLaravelLog($e);
                            return response()->json(
                                array(
                                    'error' => true,
                                    'code' =>    '500',
                                    'message' => $e->getMessage()
                                )
                            );
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "1 Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "2 Oops! you don't have sufficient permission"
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















    //Student choice list
    public function profileNotUpdated(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
                $user_data = SuperUser::select('u_id', 'u_role_id', 'u_inst_code', 'u_inst_name')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('jexpo_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('jexpo_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('choice-list', $url_data)) { //check url has permission or not
                        $choice_res = null;
                        $choice_list = StudentChoice::where('ch_stu_id',  $user_id)->with('student')->orderBy('ch_pref_no', 'ASC')->get();

                        if (sizeof($choice_list) > 0) {
                            $reponse = array(
                                'error'     =>  false,
                                'message'   =>  'Student choice found',
                                'count'     =>   sizeof($choice_list),
                                'choiceList'   =>  StudentChoiceResource::collection($choice_list)
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
}
