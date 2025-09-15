<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SuperUser;
use App\Models\Token;
use App\Http\Resources\StudentResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Models\Institute;
use App\Models\Trade;
use App\Models\PaymentTransaction;
use App\Models\ApplElgbExam;
use App\Models\Role;
use App\Models\State;
use App\Models\Subdivision;
use App\Models\District;
use App\Models\AuthPermission;
use App\Models\AuthUrl;
use Illuminate\Support\Facades\Artisan;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Crypt;



class StudentController extends Controller
{

    protected $auth;
    public $back_url = null;

    public function __construct()
    {
        //$this->auth = new Authentication();
    }
    public function getStudentInfo(Request $request, $form_num)
    {
        $student = Student::where('s_appl_form_num', $form_num)->with('state:state_id_pk,state_name', 'district:district_id_pk,district_name', 'subdivision:id,name')->first();
        if ($student) {
            $reponse = array(
                'error'                 =>  false,
                'message'               =>  'Data Found',
                'student' =>   new StudentResource($student)
            );
            return response(json_encode($reponse), 200);
        } else {
            $reponse = array(
                'error'     =>  true,
                'message'   =>  'No Student available'
            );
            return response(json_encode($reponse), 200);
        }
    }
    public function studentDetails(Request $request, $form_num)
    {
        // dd($form_num);
        $now    =   date('Y-m-d H:i:s');
        $today  =   date('Y-m-d');
        $time   =   date('H:i:s');

        try {
            $random = env('ENC_KEY');
            $check_student = Student::with([
                'state:state_id_pk,state_name',
                'district:district_id_pk,district_name'
            ])->where('s_appl_form_num', $form_num)->first();

            if ($check_student && is_numeric($check_student->s_subdivision)) {
                $check_student->load('subdivision:id,name');
            }
            if ($check_student && is_numeric($check_student->s_block)) {
                // Step 2: Reload with block relation
                $check_student->load('block:id,name');
            }
            $qualification = ApplElgbExam::with(['state10th', 'state12th', 'district10th', 'district12th', 'board12th', 'board10th'])
                ->where('exam_appl_form_num', $form_num)
                ->first();
            // dd($qualification);
            $profile_save = (bool)$check_student->is_personal_save;
            $status = $msg = $message = "";
            if (!$check_student) {
                return response()->json([
                    'error'     =>  true,
                    'message'   => 'NO record Found'
                ],  200);
            }
            $student_photo_url = $check_student->s_photo
                ? URL::to("storage/{$check_student->s_photo}")
                : null;

            $student_sign_url = $check_student->s_sign
                ? URL::to("storage/{$check_student->s_sign}")
                : null;





            $student_pwd_url = ($check_student->s_pwd == 1 && $check_student->s_pwd_doc)
                ? URL::to("storage/{$check_student->s_pwd_doc}")
                : null;



            // caste doc only if caste is not general
            $student_caste_url = (!empty($check_student->s_caste)
                && strtolower($check_student->s_caste) !== 'general'
                && $check_student->s_caste_doc)
                ? URL::to("storage/{$check_student->s_caste_doc}")
                : null;

            $student_age_url = $check_student->s_age_doc
                ? URL::to("storage/{$check_student->s_age_doc}")
                : null;
            $student_10th_marksheet_url = $check_student->s_10th_marksheet_doc
                ? URL::to("storage/{$check_student->s_10th_marksheet_doc}")
                : null;
            $student_12th_marksheet_url = $check_student->s_12th_marksheet_doc
                ? URL::to("storage/{$check_student->s_12th_marksheet_doc}")
                : null;
            $student_kanyashree_url = $check_student->s_kanyashree_doc
                ? URL::to("storage/{$check_student->s_kanyashree_doc}")
                : null;

            $student_adhar_url = $check_student->s_adhar_doc
                ? URL::to("storage/{$check_student->s_adhar_doc}")
                : null;
            $student_bank_passbook_url = $check_student->s_bank_passbook_doc
                ? URL::to("storage/{$check_student->s_bank_passbook_doc}")
                : null;
            $message = "Data fetched successfully";
            $data = [
                'ageon' => env('CUTOFF_DATE', date('Y-m-d')),
                'student_appl_form_num' => $check_student->s_appl_form_num,
                'student_first_name' => $check_student->s_first_name,
                'student_middle_name' => $check_student->s_middle_name,
                'student_last_name' => $check_student->s_last_name,
                'student_father_name' => $check_student->s_father_name,
                'student_mother_name' => $check_student->s_mother_name,
                'student_dob' => $check_student->s_dob,
                'student_state' => [
                    'state_id'   => $check_student->s_state_id ?? '',
                    'state_name' => $check_student->state->state_name ?? '',
                ],

                'student_district' => [
                    'district_id'   => $check_student->s_home_district ?? '',
                    'district_name' => $check_student->district->district_name ?? '',
                ],
                'student_block' => [
                    'block_id'   => is_numeric($check_student->s_block)
                        ? $check_student->s_block
                        : null,

                    'block_name' => is_numeric($check_student->s_block)
                        ? ($check_student->block->name ?? '')
                        : $check_student->s_block,
                ],

                'student_subdivision' => [
                    'subdivision_id'   => is_numeric($check_student->s_subdivision)
                        ? $check_student->s_subdivision
                        : null,

                    'subdivision_name' => is_numeric($check_student->s_subdivision)
                        ? ($check_student->subdivision->name ?? '')
                        : $check_student->s_subdivision,
                ],

                'student_email' => $check_student->s_email,
                'student_gender' => $check_student->s_gender,
                'student_religion' => $check_student->s_religion,
                'student_caste' => $check_student->s_caste,
                'is_kanyashree' => $check_student->is_kanyashree,

                // 'student_block' => $check_student->s_block,
                'student_post_office' => $check_student->s_post_office,
                'student_police_station' => $check_student->s_police_station,
                'student_adharno' => decryptHEXFormat($check_student->s_aadhar_no),
                'student_address' => $check_student->s_address,
                'student_aadhar_document' =>     $student_adhar_url,
                'student_address2' => $check_student->s_address2,
                'student_pin_no' => $check_student->s_pin_no,
                'student_phone' => $check_student->s_phone,
                'student_guardian_name' => $check_student->s_guardian_name,
                'student_is_married' => $check_student->is_married,
                'student_pwd' => $check_student->s_pwd,
                'student_photo' => $student_photo_url,
                'student_sign' => $student_sign_url,
                'student_citizenship' => $check_student->s_citizenship,
                'student_kanyashree' => $check_student->s_kanyashree,
                'student_caste_doc' => $student_caste_url,
                'student_pwd_doc'   => $student_pwd_url,
                'student_age_doc'   => $student_age_url,
                'student_guardian_type' => $check_student->s_guardian_type,
                'student_guardian_other_type' => $check_student->s_guardian_other_type ?? null,
                'student_bank_passbook_doc'   => $student_bank_passbook_url,
                'student_10th_marksheet_doc'   => $student_10th_marksheet_url,
                'student_12th_marksheet_doc'   => $student_12th_marksheet_url,
                'student_kanyashree_doc'   => $student_kanyashree_url,
                'session_year' => $check_student->session_year,
                'is_paid' => (bool)$check_student->is_payment,
                'is_applied' => (bool)$check_student->is_personal_save,
                'is_approved' => (bool)$check_student->is_approved,
                'is_reject' => (bool)$check_student->is_reject,
                'rejected_remarks' => $check_student->s_remarks ?? null,

                'student_cast_cert_number' => (!empty($check_student->s_caste) && strtolower($check_student->s_caste) !== 'general')
                    ? $check_student->cast_cert_number
                    : null,

                'student_cast_cert_date' => (!empty($check_student->s_caste) && strtolower($check_student->s_caste) !== 'general')
                    ? $check_student->cast_cert_date
                    : null,

                'student_cast_sub_category' => (!empty($check_student->s_caste) && strtolower($check_student->s_caste) !== 'general')
                    ? ($check_student->cast_sub_category ?? '')
                    : null,


                'student_pwd_cert_number' => ($check_student->s_pwd == 1) ? $check_student->pc_cert_no : null,
                'student_pwd_cert_date'   => ($check_student->s_pwd == 1) ? $check_student->pc_cert_date : null,
                'student_bank_details' => $check_student->s_bank_details ? json_decode($check_student->s_bank_details, true) : null,

                'exam_10th_board' => [
                    'exam_state_code' =>  $qualification->board10th->state_code ?? '',
                    'exam_board_code' =>  $qualification->exam_10th_board ?? '',
                    'exam_board_name' => strtoupper($qualification->board10th->board_name ?? ''),

                ],
                'exam_12th_board' => [
                    'exam_state_code' =>  $qualification->board12th->state_code ?? '',
                    'exam_board_code' =>  $qualification->exam_12th_board ?? '',
                    'exam_board_name' => strtoupper($qualification->board12th->board_name ?? ''),

                ],
                'exam_10th_percentage' => $qualification->exam_10th_percentage ?? '',
                'exam_12th_percentage' => $qualification->exam_12th_percentage ?? '',

                'exam_10th_state'  => [
                    'exam_state_code' =>    $qualification->exam_10th_state_code ?? '',
                    'exam_state_name' =>    $qualification->state10th->state_name ?? '',
                ],
                'exam_12th_state'  => [
                    'exam_state_code' =>    $qualification->exam_12th_state_code ?? '',
                    'exam_state_name' =>    $qualification->state12th->state_name ?? '',
                ],
                'exam_10th_district'   => [
                    'exam_district_code' =>    $qualification->exam_10th_district ?? '',
                    'exam_district_name' =>    $qualification->district10th->district_name ?? '',
                ],
                'exam_12th_district'   => [
                    'exam_district_code' =>    $qualification->exam_12th_district ?? '',
                    'exam_district_name' =>    $qualification->district12th->district_name ?? '',
                ],
                // 'exam_district'      => $qualification->district->district_name ?? '',
                'exam_10th_school_name' => $qualification->exam_10th_school_name ?? '',
                'exam_12th_school_name' => $qualification->exam_12th_school_name ?? '',
                'exam_10th_pass_yr' => $qualification->exam_10th_pass_yr ?? '',
                'exam_12th_pass_yr' => $qualification->exam_12th_pass_yr ?? '',
                'exam_10th_tot_marks' => $qualification->exam_10th_tot_marks ?? '',
                'exam_12th_tot_marks' => $qualification->exam_12th_tot_marks ?? '',
                'exam_10th_ob_marks'    => $qualification->exam_10th_ob_marks ?? '',
                'exam_12th_ob_marks'    => $qualification->exam_12th_ob_marks ?? '',
                'exam_elgb_code_one'    => strtoupper($qualification->exam_elgb_code_one ?? ''),
                'exam_elgb_code_two'    => strtoupper($qualification->exam_elgb_code_two ?? ''),
                'exam_marks_type'  => $qualification->exam_marks_type ?? '',
                'exam_per_marks' => optional($qualification)->exam_per_marks
                    ? json_decode($qualification->exam_per_marks, true)
                    : [],
                'type'  => $check_student->tab_type
            ];
            return response()->json([
                'error'     =>  false,
                'message'   =>  $message,
                'is_profile_updated' =>  $profile_save,
                'data'  =>  $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error'     =>  true,
                'message'   =>  $e->getMessage()
            ], 400);
        }
    }

    public function studentInfoUpdate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'student_first_name'   => ['required'],
            // 'student_last_name'    => ['required'],
            'student_father_name'  => ['required'],
            'student_mother_name'  => ['required'],
            'student_guardian_name' => ['required'],
            'student_dob'          => ['required'],
            'student_aadhar_no'    => ['required', 'unique:register_student,s_aadhar_no'],
            'student_email'        => ['required', 'email'],
            'student_gender'       => ['required'],
            'student_religion'     => ['required'],
            'student_caste'        => ['required'],
            'student_citizenship'  => ['required'],
            'student_subdivision'  => ['nullable'],
            'is_pwd'                => ['required'],
            'student_photo'        => ['required'],
            'student_sign'         => ['required'],
            'student_home_dist'    => ['required'],
            'student_state_id'     => ['required'],
            'student_address'      => ['required'],
            'student_pin_no'       => ['required'],
            'is_married'           => ['required'],
            'student_phone' => 'required',
            'student_aadhar_document' => ['required'],
            'student_block' => ['nullable'],
            'exam_12th_board' => 'required',
            'exam_10th_board' => 'required',
            'exam_12th_pass_yr' => 'required',
            'exam_10th_pass_yr' => 'required',
            'exam_12th_tot_marks' => 'required',
            'exam_10th_tot_marks' => 'required',
            'exam_12th_ob_marks' => 'required',
            'exam_10th_ob_marks' => 'required',
            'exam_12th_elgb_code' => 'required',
            'exam_10th_elgb_code' => 'required',
            'exam_12th_school_name' => 'required',
            'exam_10th_school_name' => 'required',
            'exam_12th_district' => 'required',
            'exam_10th_district' => 'required',
            'exam_marks' => 'required',
            'exam_12th_state_code' => 'required',
            'exam_10th_state_code' => 'required',
            'exam_10th_percentage' => 'required',
            'exam_12th_percentage' => 'required',
            'student_appl_form_num' => 'required',
            'student_police_station' => 'required',
            'student_post_office' => 'required',
            'student_age_proof_document' => ['required'],
            'student_10th_marksheet_document' => ['required'],
            'student_12th_marksheet_document' => ['required'],
            // 'bank_details' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->messages()
            ], 422);
        }

        try {
            $now = now();
            $form_num = $request->student_appl_form_num;
            $student = Student::where('s_appl_form_num', $form_num)->where('is_active', 1)->first();
            $profile_save = (bool)$student->is_personal_save;

            if (!$student) {
                return response()->json([
                    'error' => true,
                    'message' => 'Student not found with this application number.'
                ], 404);
            }

            // Age validation
            $cutoffDate = env('CUTOFF_DATE', date('Y-m-d'));

            // Convert to timestamp
            $cutoffTimestamp = strtotime($cutoffDate);
            $currentTime = time();

            // Student DOB
            $studentDob = strtotime($request->student_dob);
            $studentAge = strtotime("+17 years", $studentDob);
            if ($studentAge > $cutoffTimestamp) {
                return response()->json([
                    'error'   => true,
                    'message' => "Candidate must complete 17 years on or before " . date('d-m-Y', $cutoffTimestamp) . "."
                ], 400);
            }
            $examTotalMarks = (int) $request->exam_total_marks;
            $obtainedMarks = (int) $request->obtained_marks;
            if ($obtainedMarks > $examTotalMarks) {
                return response()->json([
                    'success' => false,
                    'message' => 'Obtained marks cannot be greater than total marks.'
                ], 400);
            }

            $student_photo = $student->s_photo; // default to existing

            if ($request->hasFile('student_photo') && $request->file('student_photo')->isValid()) {
                // Unlink old photo if exists
                if (!empty($student->s_photo)) {
                    $oldPhotoPath = storage_path('app/public/' . $student->s_photo);
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                $image = $request->file('student_photo');
                $imageName = $form_num . '_image_' . $currentTime . '.' . $image->getClientOriginalExtension();
                $imagePath = 'uploads/' . $imageName;
                $image->storeAs('uploads/', $imageName, 'public');

                $student_photo = $imagePath;
            }

            // SIGNATURE
            $student_sign = $student->s_sign; // default to existing

            if ($request->hasFile('student_sign') && $request->file('student_sign')->isValid()) {
                // Unlink old signature if exists
                if (!empty($student->s_sign)) {
                    $oldSignPath = storage_path('app/public/' . $student->s_sign);
                    if (file_exists($oldSignPath)) {
                        unlink($oldSignPath);
                    }
                }

                $signature = $request->file('student_sign');
                $signatureName = $form_num . '_sign_' . $currentTime . '.' . $signature->getClientOriginalExtension();
                $signaturePath = 'uploads/' . $signatureName;
                $signature->storeAs('uploads/', $signatureName, 'public');

                $student_sign = $signaturePath;
            }

            $student_kanyashree_document_path = null;
            $student_kanyashree_no = null;
            if ($request->is_kanyashree === '1') {
                if ($request->hasFile('student_kanyashree_document')) {
                    $document = $request->file('student_kanyashree_document');
                    $documentName = $form_num . '_kanyashree_document.' . $document->getClientOriginalExtension();
                    $document->storeAs('uploads/', $documentName, 'public');
                    $student_kanyashree_document_path = 'uploads/' . $documentName;
                } elseif (is_string($request->student_kanyashree_document)) {
                    $student_kanyashree_document_path =  $student->s_kanyashree_doc  ? $student->s_kanyashree_doc
                        : null;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kanyashree document is mandatory when Kanyashree is selected.'
                    ], 400);
                }
                $student_kanyashree_no = $request->student_kanyashree_no ?? '';
            }



            if ($request->is_pwd === '1') {
                if ($request->hasFile('student_pwd_document') && $request->file('student_pwd_document')->isValid()) {
                    $document = $request->file('student_pwd_document');
                    $documentName = $form_num . '_pwd_document.' . $document->getClientOriginalExtension();
                    $document->storeAs('uploads/', $documentName, 'public');
                    $student_pwd_document_path = 'uploads/' . $documentName;
                } elseif (is_string($request->student_pwd_document)) {
                    $student_pwd_document_path = $student->s_pwd_doc ?? null;
                }
                $pwd_certificate_number = trim($request->pc_cert_no ?? '') !== ''
                    ? $request->pc_cert_no
                    : ($student->pc_cert_no ?? null);

                $pwd_certificate_issue_date = trim($request->pc_cert_date ?? '') !== ''
                    ? $request->pc_cert_date
                    : ($student->pc_cert_date ?? null);
                if (empty($student_pwd_document_path) || empty($pwd_certificate_number) || empty($pwd_certificate_issue_date)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'PWD certificate number, issue date, and document are required when PWD is selected.'
                    ], 400);
                }
            } else {
                $student_pwd_document_path = null;
                $pwd_certificate_number = '';
                $pwd_certificate_issue_date = '';
            }


            $student_caste_document_path = $student->s_caste_doc;
            $certificate_number = null;
            $certificate_issue_date = null;
            $sub_caste_category = null;
            if (strtoupper(trim($request->student_caste)) !== 'GENERAL') {
                if ($request->hasFile('student_caste_document') && $request->file('student_caste_document')->isValid()) {
                    $document = $request->file('student_caste_document');
                    $documentName = $form_num . '_caste_document.' . $document->getClientOriginalExtension();
                    $document->storeAs('uploads/', $documentName, 'public');
                    $student_caste_document_path = 'uploads/' . $documentName;
                } elseif (is_string($request->student_caste_document)) {
                    $student_caste_document_path =  $student->s_caste_doc  ? $student->s_caste_doc
                        : null;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Caste certificate is mandatory when caste is not GENERAL.'
                    ], 400);
                }
                $certificate_number = $request->cert_number;
                $sub_caste_category = $request->sub_caste_category;
                $certificate_issue_date = $request->cert_issue_date;
            }

            if ($request->hasFile('student_aadhar_document') && $request->file('student_aadhar_document')->isValid()) {
                $document = $request->file('student_aadhar_document');
                $documentName = $form_num . '_aadhar_document.' . $document->getClientOriginalExtension();
                $document->storeAs('uploads/', $documentName, 'public');
                $student_aadhar_document_path = 'uploads/' . $documentName;
            } elseif (is_string($request->student_aadhar_document)) {
                $student_aadhar_document_path =  $student->s_adhar_doc  ? $student->s_adhar_doc : null;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'adhar document is mandatory '
                ], 400);
            }

            if ($request->hasFile('bank_passbook_document') && $request->file('bank_passbook_document')->isValid()) {
                $document = $request->file('bank_passbook_document');
                $documentName = $form_num . '_bank_passbook_document.' . $document->getClientOriginalExtension();
                $document->storeAs('uploads/', $documentName, 'public');
                $student_bank_passbook_document_path = 'uploads/' . $documentName;
            } elseif (is_string($request->bank_passbook_document)) {
                $student_bank_passbook_document_path =  $student->s_bank_passbook_doc  ? $student->s_bank_passbook_doc : null;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'bank passbook document is mandatory '
                ], 400);
            }
            if ($request->hasFile('student_age_proof_document')) {
                $document = $request->file('student_age_proof_document');
                $documentName = $form_num . '_age_proof_document.' . $document->getClientOriginalExtension();
                $document->storeAs('uploads/', $documentName, 'public');
                $student_age_proof_document_path = 'uploads/' . $documentName;
            } elseif (is_string($request->student_age_proof_document)) {
                $student_age_proof_document_path = $student->s_age_doc  ?  $student->s_age_doc
                    : null;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'this document is mandatory'
                ], 400);
            }
            if ($request->hasFile('student_12th_marksheet_document')) {
                $document = $request->file('student_12th_marksheet_document');
                $documentName = $form_num . '_12th_marksheet_document.' . $document->getClientOriginalExtension();
                $document->storeAs('uploads/', $documentName, 'public');
                $student_12th_marksheet_document_path = 'uploads/' . $documentName;
            } elseif (is_string($request->student_12th_marksheet_document)) {
                $student_12th_marksheet_document_path = $student->s_12th_marksheet_doc  ?  $student->s_12th_marksheet_doc
                    : null;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'this 10th marksheet document is mandatory'
                ], 400);
            }
            if ($request->hasFile('student_10th_marksheet_document')) {
                $document = $request->file('student_10th_marksheet_document');
                $documentName = $form_num . '_10th_marksheet_document.' . $document->getClientOriginalExtension();
                $document->storeAs('uploads/', $documentName, 'public');
                $student_10th_marksheet_document_path = 'uploads/' . $documentName;
            } elseif (is_string($request->student_10th_marksheet_document)) {
                $student_10th_marksheet_document_path = $student->s_10th_marksheet_doc  ?  $student->s_10th_marksheet_doc
                    : null;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'this 12th marksheet document is mandatory'
                ], 400);
            }
            //
            $enc_aadhaar_num = encryptHEXFormat($request->student_aadhar_no);


            DB::beginTransaction();
            $bank_details = json_decode($request->bank_details, true);
            // dd($certificate_number);
            $student->update([
                's_first_name'        => trim($request->student_first_name),
                's_middle_name'       => trim($request->student_middle_name),
                's_last_name'         => trim($request->student_last_name),
                's_candidate_name'    => trim($request->student_first_name)
                    . (
                        (isset($request->student_middle_name) &&
                            !empty(trim($request->student_middle_name)) &&
                            strtolower(trim($request->student_middle_name)) !== 'null')
                        ? ' ' . trim($request->student_middle_name)
                        : ''
                    )
                    . ' ' . trim($request->student_last_name),
                's_father_name'       => trim($request->student_father_name),
                's_mother_name'       => trim($request->student_mother_name),
                's_dob'               => $request->student_dob,
                's_aadhar_no'         => $enc_aadhaar_num,
                's_email'             => trim($request->student_email),
                's_gender'            => $request->student_gender,
                's_religion'          => $request->student_religion,
                's_caste'             => $request->student_caste,
                's_subdivision' => (
                    isset($request->student_subdivision) &&
                    trim(strtolower($request->student_subdivision)) !== '' &&
                    trim(strtolower($request->student_subdivision)) !== 'null'
                ) ? $request->student_subdivision : null,
                's_address2'          => $request->student_address2,
                'is_kanyashree'    => $request->is_kanyashree,
                's_pwd'               => $request->is_pwd,
                's_photo'             => $student_photo,
                's_sign'              => $student_sign,
                's_guardian_name'     => $request->student_guardian_name,
                's_citizenship'       => $request->student_citizenship,
                's_pin_no'            => $request->student_pin_no,
                's_police_station'         => $request->student_police_station,
                's_post_office'         => $request->student_post_office,
                's_home_district'     => trim($request->student_home_dist),
                's_state_id'          => $request->student_state_id,
                's_address'           => trim($request->student_address),
                's_trade_code'        => $request->trade_code,
                'is_married'          => $request->is_married,
                's_kanyashree'        => $student_kanyashree_no,

                's_caste_doc' => $student_caste_document_path,
                's_pwd_doc'   => $student_pwd_document_path,
                's_bank_passbook_doc'   => $student_bank_passbook_document_path,
                's_age_doc'   => $student_age_proof_document_path,
                's_10th_marksheet_doc' => $student_10th_marksheet_document_path,
                's_12th_marksheet_doc' => $student_12th_marksheet_document_path,
                'cast_cert_number' =>  $certificate_number,
                'cast_cert_date' =>  $certificate_issue_date,
                'cast_sub_category' => $sub_caste_category,
                's_block' => $request->student_block,
                's_adhar_doc' => $student_aadhar_document_path,
                's_bank_details' => json_encode($bank_details),
                'pc_cert_no' => $pwd_certificate_number,
                'pc_cert_date' => $pwd_certificate_issue_date,
                's_kanyashree_doc'   => $student_kanyashree_document_path,

            ]);



            $exam_per_marks = $request->exam_marks
                ? json_decode($request->exam_marks, true)
                : null;

            ApplElgbExam::updateOrCreate(
                [
                    'exam_appl_form_num' => $form_num,
                ],
                [
                    'exam_elgb_code_one'    => $request->exam_10th_elgb_code,
                    'exam_elgb_code_two'    => $request->exam_12th_elgb_code,
                    'exam_12th_board'         => $request->exam_12th_board,
                    'exam_10th_board'         => $request->exam_10th_board,
                    'exam_12th_pass_yr'       => $request->exam_12th_pass_yr,
                    'exam_10th_pass_yr'       => $request->exam_10th_pass_yr,
                    'exam_12th_tot_marks'     => $request->exam_12th_tot_marks,
                    'exam_10th_tot_marks'     => $request->exam_10th_tot_marks,
                    'exam_12th_ob_marks'      => $request->exam_12th_ob_marks,
                    'exam_10th_ob_marks'      => $request->exam_10th_ob_marks,
                    'exam_12th_school_name'   => $request->exam_12th_school_name,
                    'exam_10th_school_name'   => $request->exam_10th_school_name,
                    'exam_per_marks'        => $exam_per_marks ? json_encode($exam_per_marks) : null,
                    'exam_12th_state_code'         => $request->exam_12th_state_code,
                    'exam_10th_state_code'         => $request->exam_10th_state_code,
                    'exam_12th_district'      => $request->exam_12th_district,
                    'exam_10th_district'      => $request->exam_10th_district,
                    'exam_10th_percentage'    => $request->exam_10th_percentage,
                    'exam_12th_percentage'    => $request->exam_12th_percentage,
                    'updated_at'         => now(),
                ]
            );

            $authUserId = $request->auth_user_id ?? null;

            $logMessage = $authUserId
                ? "{$student->s_candidate_name} has successfully updated profile by User ID {$authUserId} on {$now}."
                : "{$student->s_candidate_name} has successfully updated profile at {$student->s_phone} on {$now}.";

            auditTrail($form_num, $logMessage, 'update');

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Application updated successfully',
                'is_profile_updated' => $profile_save
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function downloadAdmissionFees(Request $request, $form_num, $type)
    {

        $students = Student::with([
            'state:state_id_pk,state_name',
            'district:district_id_pk,district_name'
        ])->where('s_appl_form_num', $form_num)->first();

        if ($students && is_numeric($students->s_subdivision)) {
            $students->load('subdivision:id,name');
        }
        if ($students && is_numeric($students->s_block)) {
            // Step 2: Reload with block relation
            $students->load('block:id,name');
        }
        $dob = \Carbon\Carbon::parse($students->s_dob);
        $asOnDate = \Carbon\Carbon::create(2025, 12, 31);
        $age = $dob->diff($asOnDate);
        $student_data = [
            'block' => [
                'id'   => is_numeric($students->s_block) ? $students->s_block : null,
                'name' => is_numeric($students->s_block)
                    ? ($students->block->name ?? '')
                    : $students->s_block,
            ],

            'subdivision' => [
                'id'   => is_numeric($students->s_subdivision) ? $students->s_subdivision : null,
                'name' => is_numeric($students->s_subdivision)
                    ? ($students->subdivision->name ?? '')
                    : $students->s_subdivision,
            ],
        ];

        $education = ApplElgbExam::with(['state10th', 'state12th', 'district10th', 'district12th', 'board12th', 'board10th'])
            ->where('exam_appl_form_num', $form_num)
            ->first();
        $bank_details = $students->s_bank_details ? json_decode($students->s_bank_details, true) : null;
        $subjects  =  $education->exam_per_marks ? json_decode($education->exam_per_marks, true) : null;
        try {
            $aadhaar = decryptHEXFormat($students->s_aadhar_no);

            // Keep only last 4 digits visible
            $students->s_aadhar_no = str_repeat('X', strlen($aadhaar) - 4) . substr($aadhaar, -4);
        } catch (\Exception $e) {
            // fallback if decrypt fails
            $students->s_aadhar_no = '[Invalid Aadhaar]';
        }
        $payment = PaymentTransaction::where([
            'pmnt_modified_by' => $form_num,
            'pmnt_pay_type' => 'APPLICATION'
        ])->first();

        $qrContent = [
            $students->s_appl_form_num,
            $students->s_candidate_name,
            $students->s_dob,
            $students->s_phone,
            $students->s_email,
        ];

        $qr_text = implode(',', $qrContent);

        // dd($qr_text);

        $qrcode = QrCode::format('svg')
            ->size(60)
            ->backgroundColor(255, 255, 255)
            ->color(0, 0, 0)
            ->margin(1)
            ->generate(
                $qr_text,
            );
        $pdf = Pdf::loadView('exports.application-fees', [
            'students' => $students,
            'education' => $education,
            'subjects' => $subjects,
            'bank_details' => $bank_details,
            'payment' => $payment,
            'qr_code' => base64_encode($qrcode),
            'student_data' => $student_data,
            'age' => $age,
            'type' => $type

        ]);
        $pdf->setOption(['defaultFont' => 'sans-serif'])
            ->setPaper('a4', 'portrait')
            ->output(); // render first

        $pdf->getDomPDF()->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $text = "Page $pageNumber of $pageCount";
            $font = $fontMetrics->get_font("Arial", "normal");
            $size = 9;
            $width = $fontMetrics->get_text_width($text, $font, $size);

            // Position → bottom-right
            $x = $canvas->get_width() - $width - 40;
            $y = $canvas->get_height() - 40;

            $canvas->text($x, $y, $text, $font, $size);
        });
        return $pdf->stream('poly1styr-' . $form_num . '.pdf');
    }

    public function downloadAdmissionFeesExcel(Request $request)
    {
        $query = Student::with([
            'state:state_id_pk,state_name',
            'district:district_id_pk,district_name'
        ])->orderBy('s_appl_form_num', 'asc');


        if ($request->type == 'payment') {
            $query->where('is_payment', 1);
        }

        if ($request->type == 'application') {
            $query->where('is_personal_save', 1);
        }
        $studentsData = $query->get()->map(function ($student) {
            if (is_numeric($student->s_subdivision)) {
                $student->loadMissing('subdivision:id,name');
            }
            if (is_numeric($student->s_block)) {
                $student->loadMissing('block:id,name');
            }

            $education = ApplElgbExam::with('board')
                ->where('exam_appl_form_num', $student->s_appl_form_num)
                ->first();

            $bank_details = $student->s_bank_details ? json_decode($student->s_bank_details, true) : [];
            $subjects     = $education && $education->exam_per_marks ? json_decode($education->exam_per_marks, true) : [];
            // dd($subjects);

            $payment = PaymentTransaction::where([
                'pmnt_modified_by' => $student->s_appl_form_num,
                'pmnt_pay_type'    => 'APPLICATION'
            ])->first();
            $subjectMarks = [];

            foreach ($subjects as $subj) {
                if (!empty($subj['subject'])) {
                    $key = str_replace(' ', '_', strtolower($subj['subject']));
                    $subjectMarks[$key . '_total_marks'] = $subj['total'] ?? '';
                    $subjectMarks[$key . '_obtained_marks'] = $subj['obtained'] ?? '';
                }
            }


            return array_merge([
                'Application Number' => $student->s_appl_form_num,
                'Candidate Name'     => $student->s_candidate_name,
                'Father Name'        => $student->s_father_name,
                'Mother Name'        => $student->s_mother_name,
                'DOB'                => $student->s_dob,
                'Gender'             => $student->s_gender,
                'Email'              => $student->s_email,
                'Phone'              => $student->s_phone,
                'State'              => $student->state->state_name ?? '',
                'District'           => $student->district->district_name ?? '',
                'Subdivision'        => is_numeric($student->s_subdivision) ? optional($student->subdivision)->name : $student->s_subdivision,
                'Block'              => is_numeric($student->s_block) ? optional($student->block)->name : $student->s_block,
                'PIN'                => $student->s_pin_no,
                'Religion'           => $student->s_religion,
                'Caste'              => $student->s_caste,
                'TFW'                => $student->s_tfw == 1 ? 'Yes' : 'No',
                'LLQ'                => $student->s_llq == 1 ? 'Yes' : 'No',
                'EWS'                => $student->s_ews == 1 ? 'Yes' : 'No',
                'EXSM'               => $student->s_exsm == 1 ? 'Yes' : 'No',
                'Post_Office'        => $student->s_post_office,
                'Police_Station'     => $student->s_police_station,
                'Aadharno'           => decryptHEXFormat($student->s_aadhar_no),
                'Married'            => $student->is_married == 1 ? 'Yes' : 'No',
                'Citizenship'        => $student->s_citizenship,
                'Caste_cert_no'      => $student->cast_cert_number,
                'Caste_cert_date'    => $student->cast_cert_date,
                'Cast_sub_category'  => $student->cast_sub_category,
                'EWS_cert_no'        => $student->ews_cert_number,
                'EWS_cert_date'      => $student->ews_cert_date,
                'Board'              => $education->board->board_name ?? '',
                'Exam Name'          => $education->exam_elgb_code ?? '',
                'Exam Pass Year'     => $education->exam_pass_yr ?? '',
                'Exam School Name'   => $education->exam_school_name ?? '',
                'Bank Name'          => $bank_details['bankName'] ?? '',
                'Account No'         => $bank_details['accNumber'] ?? '',
                'IFSC Code'          => $bank_details['IFSC'] ?? '',
                'Subjects'           => collect($subjects)->pluck('subject')->implode(', '),
                'Total Marks'        => $education->exam_tot_marks ?? '',
                'Obtained Marks'     => $education->exam_ob_marks ?? '',
                'Payment Amount'     => $payment->trans_amount ?? '',
                'Payment Date'       => $payment?->trans_time ? date('Y-m-d', strtotime($payment->trans_time)) : '',
                'Payment Status'     => $payment->trans_status ?? '',
            ], $subjectMarks);
        });

        return response()->json([
            'error' => false,
            'data'  => $studentsData,
            'count' => $studentsData->count()
        ], 200);
    }
}
