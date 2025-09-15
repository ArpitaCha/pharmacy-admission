<?php

namespace App\Http\Controllers;

use Exception;
use DateTime;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Token;
use App\Models\Trade;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use App\Models\District;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Institute;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ApplElgbExam;
use App\Models\BusinessAddressDetails;
use Illuminate\Support\Facades\Validator;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SuperUser;
use App\Models\AuditTrail;
use App\Models\AuthPermission;
use App\Models\HeadVerifierStudentAssign;
use App\Models\VerifierStudentAssign;
use App\Models\AuthUrl;
use Illuminate\Support\Facades\Http;




class AdmissionController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | STEP VALIDATION RULES
    |--------------------------------------------------------------------------
    */
    private function rulesStep1($request)
    {
        $rules = [
            'student_first_name'     => ['required'],
            'student_father_name'    => ['required'],
            'student_mother_name'    => ['required'],
            'guardian_type'          => ['required'],
            'student_guardian_name'  => ['required'],
            'student_dob'            => ['required', 'date'],
            'student_gender'         => ['required'],
            'student_religion'       => ['required'],
            'student_citizenship'    => ['required'],
            'is_married'             => ['required'],
            'student_phone'          => ['required', 'unique:register_student,s_phone'],
            'student_email'          => ['required', 'email', 'unique:register_student,s_email'],
            'student_aadhar_no'      => ['required'],
        ];


        $validator = Validator::make($request->all(), $rules);


        $validator->after(function ($validator) use ($request) {
            // Aadhaar encryption uniqueness
            $enc_aadhaar_num = encryptHEXFormat($request->student_aadhar_no);
            if (Student::where('s_aadhar_no', $enc_aadhaar_num)->exists()) {
                $validator->errors()->add('student_aadhar_no', 'This Aadhaar number already exists.');
            }


            if ($request->guardian_type == 'OTHER' && trim($request->student_guardian_name) === '') {
                $validator->errors()->add('student_guardian_name', 'Guardian name is required when guardian type is OTHER.');
            }

            $cutoffDate = env('CUTOFF_DATE', date('Y-m-d'));
            $cutoffTimestamp = strtotime($cutoffDate);
            $studentDob = strtotime($request->student_dob);
            $studentAge = strtotime("+18 years", $studentDob);


            if ($studentAge > $cutoffTimestamp) {
                $validator->errors()->add(
                    'student_dob',
                    "Candidate must complete 18 years on or before " . date('d-m-Y', $cutoffTimestamp) . "."
                );
            }
        });

        return $validator;
    }

    private function rulesStep2($request)
    {
        $rules =  [
            'student_address'       => ['required', 'string'],
            'student_address2'      => ['nullable', 'string'],
            'student_pin_no'        => ['required', 'digits:6'],
            'student_state_id'      => ['required', 'integer'],
            'student_post_office'   => ['required', 'string'],
            'student_police_station' => ['required', 'string'],
            'student_home_dist'     => ['required', 'string'],
            'student_block'         => ['nullable', 'string'],
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        return $validator;
    }

    private function rulesStep3($request)
    {
        $rules = [
            'exam_12th_board'       => ['required'],
            'exam_10th_board'       => ['required'],
            'exam_12th_pass_yr'     => ['required', 'digits:4'],
            'exam_10th_pass_yr'     => ['required', 'digits:4'],
            'exam_12th_tot_marks'   => ['required', 'numeric', 'min:1'],
            'exam_10th_tot_marks'   => ['required', 'numeric', 'min:1'],
            'exam_12th_ob_marks'    => ['required', 'numeric', 'min:0'],
            'exam_10th_ob_marks'    => ['required', 'numeric', 'min:0'],
            'exam_12th_elgb_code'   => ['required'],
            'exam_10th_elgb_code'   => ['required'],
            'exam_12th_school_name' => ['required'],
            'exam_10th_school_name' => ['required'],
            'exam_12th_district'    => ['required'],
            'exam_10th_district'    => ['required'],
            'exam_marks'            => ['required', 'json'],
            'exam_12th_state_code'  => ['required'],
            'exam_10th_state_code'  => ['required'],
            'exam_10th_percentage'  => ['required', 'numeric', 'between:0,100'],
            'exam_12th_percentage'  => ['required', 'numeric', 'between:0,100'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        return $validator;
    }

    private function rulesStep4($request)
    {
        $rules = [
            'bank_details' => ['required', 'json'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        return $validator;
    }

    private function rulesStep5($request)
    {
        $rules = [
            'is_pwd'                        => ['required'],
            'student_photo'                 => ['required', 'file'],
            'student_sign'                  => ['required', 'file'],
            'student_aadhar_document'       => ['required', 'file'],
            'student_age_proof_document'    => ['required', 'file'],
            'student_10th_marksheet_document' => ['required', 'file'],
            'student_12th_marksheet_document' => ['required', 'file'],
            // conditional validations will be added later
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validator->after(function ($v) use ($request) {
            if ($request->is_pwd == 1) {
                if (!$request->hasFile('student_pwd_document') || !$request->pc_cert_no || !$request->pc_cert_date) {
                    $v->errors()->add('is_pwd', 'PWD certificate number, date & document required when PWD=1');
                }
            }

            // Caste conditional
            if ($request->filled('student_caste') && strtoupper($request->student_caste) !== 'GENERAL') {
                if (!$request->hasFile('student_caste_document')) {
                    $v->errors()->add('student_caste', 'Caste certificate required for non-GENERAL caste');
                }
            }

            // Kanyashree conditional
            if ($request->is_kanyashree == 1) {
                if (!$request->hasFile('student_kanyashree_document')) {
                    $v->errors()->add('is_kanyashree', 'Kanyashree document required');
                }
            }
        });
        return  $validator;
    }

    /*
    |--------------------------------------------------------------------------
    | SUBMIT STUDENTS FUNCTION
    |--------------------------------------------------------------------------
    */
    public function submitStudents(Request $request)
    {
        try {
            // Step type
            $step = $request->type; // personalDetails, addressDetails, etc.

            // Pick rules
            switch ($step) {
                case 'personalDetails':
                    $rules = $this->rulesStep1($request);
                    break;
                case 'addressDetails':
                    $rules = $this->rulesStep2($request);
                    break;
                case 'educationDetails':
                    $rules = $this->rulesStep3($request);
                    break;
                case 'bankDetails':
                    $rules = $this->rulesStep4($request);
                    break;
                /* case 'documentDetails':
                    $rules = $this->rulesStep5();
                    break; */
                case 'submit':
                    $rules = $this->rulesStep5($request);
                    break;
            }

            $now = now();
            $schedule = Schedule::where('sch_event', 'APPLICATION')
                ->where('sch_round', 1)
                ->where('sch_start_dt', '<=', $now)
                ->where('sch_end_dt', '>=', $now)
                ->exists();

            if (!$schedule) {
                return response()->json(['error' => true, 'message' => 'Application time expired'], 400);
            }

            // Student record
            $student = Student::where('s_phone', $request->student_phone)->where('is_active', 1)->first();
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Active student not found'], 404);
            }

            $s_appl_form_num = $student->s_appl_form_num;
            $sessionYear = sessionYear(date('Y'));

            // Handle uploads
            $uploads = $this->handleUploads($request, $s_appl_form_num);

            // Update student
            $student->update(array_merge([
                's_first_name'   => $request->student_first_name,
                's_middle_name'  => $request->student_middle_name,
                's_last_name'    => $request->student_last_name,
                's_candidate_name' => trim($request->student_first_name . ' ' . $request->student_middle_name . ' ' . $request->student_last_name),
                's_father_name'  => $request->student_father_name,
                's_mother_name'  => $request->student_mother_name,
                's_dob'          => $request->student_dob,
                's_aadhar_no'    => encryptHEXFormat($request->student_aadhar_no),
                's_email'        => $request->student_email,
                's_gender'       => $request->student_gender,
                's_religion'     => $request->student_religion,
                's_caste'        => $request->student_caste,
                's_citizenship'  => $request->student_citizenship,
                's_subdivision'  => $request->student_subdivision,
                's_pwd'          => $request->is_pwd,
                's_home_district' => $request->student_home_dist,
                's_state_id'     => $request->student_state_id,
                's_address'      => $request->student_address,
                's_address2'     => $request->student_address2,
                'is_married'     => $request->is_married,
                'is_kanyashree' => $request->is_kanyashree,
                's_kanyashree'   => $request->student_kanyashree_no,
                's_pin_no'       => $request->student_pin_no,
                's_police_station' => $request->student_police_station,
                's_post_office'  => $request->student_post_office,
                's_guardian_name' => $request->student_guardian_name,
                's_guardian_type' => $request->guardian_type,
                's_guardian_other_type' => $request->get('student_guardian_other_type') ?? null,
                'is_active'      => 1,
                'session_year'   => $sessionYear,
                's_block' => $request->student_block,
                'is_business' => $request->is_business,

                's_bank_details' => $request->bank_details,
                'pc_cert_no'     => $request->pc_cert_no,
                'pc_cert_date'   => $request->pc_cert_date,
                'cast_cert_number' => $request->cert_number,
                'cast_cert_date'  => $request->cert_issue_date,
                'cast_sub_category' => $request->sub_caste_category,
            ], $uploads));

            // Save exam details
            ApplElgbExam::updateOrCreate(
                ['exam_appl_form_num' => $s_appl_form_num],
                [
                    'exam_elgb_code_one'    => $request->exam_10th_elgb_code,
                    'exam_elgb_code_two'    => $request->exam_12th_elgb_code,
                    'exam_12th_board'       => $request->exam_12th_board,
                    'exam_10th_board'       => $request->exam_10th_board,
                    'exam_12th_pass_yr'     => $request->exam_12th_pass_yr,
                    'exam_10th_pass_yr'     => $request->exam_10th_pass_yr,
                    'exam_12th_tot_marks'   => $request->exam_12th_tot_marks,
                    'exam_10th_tot_marks'   => $request->exam_10th_tot_marks,
                    'exam_12th_ob_marks'    => $request->exam_12th_ob_marks,
                    'exam_10th_ob_marks'    => $request->exam_10th_ob_marks,
                    'exam_12th_school_name' => $request->exam_12th_school_name,
                    'exam_10th_school_name' => $request->exam_10th_school_name,
                    'exam_per_marks'        => $request->exam_marks,
                    'exam_12th_state_code'  => $request->exam_12th_state_code,
                    'exam_10th_state_code'  => $request->exam_10th_state_code,
                    'exam_12th_district'    => $request->exam_12th_district,
                    'exam_10th_district'    => $request->exam_10th_district,
                    'exam_10th_percentage'  => $request->exam_10th_percentage,
                    'exam_12th_percentage'  => $request->exam_12th_percentage,
                ]
            );
            if ($request->is_business == '1') {
                BusinessAddressDetails::updateOrCreate(
                    ['s_appl_form_num' => $s_appl_form_num],
                    [
                        'business_state_id'    => $request->business_state_id,
                        'business_district_id'    => $request->business_district_id,
                        'business_subdivision'       => $request->business_subdivision,
                        'business_block'       => $request->business_block,
                        'business_post_office'     => $request->business_post_office,
                        'business_police_station'     => $request->business_police_station,
                        'business_pin_code' => $request->business_pin_code,
                        'business_address' => $request->business_address,
                        'business_address_2' => $request->business_address_2

                    ]
                );
            } else {
                // is_business == 0 → delete existing record
                BusinessAddressDetails::where('s_appl_form_num', $s_appl_form_num)->delete();
            }

            if ($student->tab_type != 'submit') {
                $tab_step   =    getStep($step);
                $student->tab_type = $tab_step;
            } else {
                $tab_step   =  getStep($step);
            }

            if ($step == 'submit') {
                $student->is_personal_save = 1;
            }
            $student->save();
            auditTrail($s_appl_form_num, "{$request->student_first_name} {$request->student_last_name} submitted profile.", 'insert');

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'is_profile_updated' => (bool)$student->is_personal_save,
                'tab_type'  =>  $tab_step
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILE UPLOAD HANDLER
    |--------------------------------------------------------------------------
    */
    private function handleUploads(Request $request, $formNum): array
    {
        $map = [
            'student_photo'                 => 's_photo',
            'student_sign'                  => 's_sign',
            'student_aadhar_document'       => 's_adhar_doc',
            'student_age_proof_document'    => 's_age_doc',
            'student_10th_marksheet_document' => 's_10th_marksheet_doc',
            'student_12th_marksheet_document' => 's_12th_marksheet_doc',
            'bank_passbook_document'        => 's_bank_passbook_doc',
            'student_caste_document'        => 's_caste_doc',
            'student_pwd_document'          => 's_pwd_doc',
            'student_kanyashree_document'   => 's_kanyashree_doc',
        ];

        $uploads = [];
        foreach ($map as $input => $column) {
            if ($request->hasFile($input)) {
                $file = $request->file($input);
                $name = $formNum . '_' . $input . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads', $name, 'public');
                $uploads[$column] = $path;
            }
        }
        return $uploads;
    }





    public function admissionList(Request $request)
    {
        $roleId = $request->auth_role_id ?? null;
        $userData = $request->auth_user ?? null;
        if ($roleId == 1) {
            $students = Student::where('is_personal_save', 1)
                ->orderBy('s_id', 'desc')
                ->get();
        } elseif (in_array($roleId, [3, 4])) {
            $students = Student::where('is_personal_save', 1)
                ->where('s_home_district', $userData->u_inst_district ?? null)
                ->orderBy('s_id', 'desc')
                ->get();
        } else {
            $students = collect(); // empty collection for other roles
        }

        $student_adm_list = $students->map(function ($data) {
            return [
                'form_num'      => $data->s_appl_form_num,
                'name'          => $data->s_candidate_name,
                'guardian_name' => $data->s_guardian_name,
                'phone_no'      => $data->s_phone,
                'is_applied'    => (bool)$data->is_personal_save,
                'is_paid'       => (bool)$data->is_payment,
                'is_approved'   => (bool)$data->is_approved,
                'is_reject'     => (bool)$data->is_reject,
                'remarks'       => $data->s_remarks ?: '',
                'overall_status' => (function () use ($data) {
                    if (!$data->is_personal_save) return 'Not Applied';
                    if ($data->is_personal_save && !$data->is_payment) return 'Applied but Not Paid';
                    if ($data->is_payment && !$data->is_approved && !$data->is_reject) return 'Paid but Not Approved';
                    if ($data->is_reject) return 'Rejected';
                    if ($data->is_approved) return 'Approved';
                    return 'Pending';
                })(),
            ];
        });

        return response()->json([
            'error'   => false,
            'message' => 'Data fetched successfully',
            'list'    => $student_adm_list
        ], 200);
    }

    public function approveCouncil(Request $request)
    {
        $user_id = $request->auth_user_id ?? null;
        $form_num = $request->form_num;
        $remarks = $request->remarks;
        $status   = null;
        $message  = null;
        if ($request->is_approve) {
            $checked = Student::where('s_appl_form_num', $form_num)->where('is_personal_save', 1)->Update([
                'is_approved' => (bool)$request->is_approve,
                'is_reject'   => null,
                's_remarks'   => null
            ]);
            $message = "Admission Approved Successfully";
            $status = "APPROVED";
        } elseif ($request->is_reject) {
            $checked = Student::where('s_appl_form_num', $form_num)->where('is_personal_save', 1)->Update([
                'is_reject' => (bool)$request->is_reject,
                'is_approved'   => null,
                's_remarks' => $request->remarks
            ]);
            $status = "REJECTED";
            $message = "Admission rejected";
        }
        auditTrail($user_id, "$form_num, $status - Approve successfully");
        return response()->json([
            'error' => false,
            'message' => $message
        ]);
    }
    public function verifierList(Request $request)
    {
        $roleId = $request->auth_role_id ?? null;
        $userData = $request->auth_user ?? null;

        if ($roleId == 1) {
            $list = SuperUser::whereNotIn('u_role_id', [1])   // exclude head verifier
                ->where('is_active', 1)                       // only active
                ->with('role', 'district')
                ->get()
                ->map(function ($data) {
                    return [
                        'institute' => [
                            'inst_code'   => $data->u_inst_code ?? '',
                            'inst_name'   => $data->u_inst_name ?? '',
                        ],
                        'phone_no' => $data->u_phone,
                        'name'     => $data->u_fullname,
                        'email'    => $data->u_email,
                        'username' => $data->u_username,
                        'is_active' => (bool)$data->is_active,
                        'district' => [
                            'district_id'   => optional($data->district)->district_id_pk,
                            'district_name' => optional($data->district)->district_name,
                        ],
                        'role' => [
                            'role_id'   => $data->u_role_id ?? '',
                            'role_name' => optional($data->role)->role_name,
                        ],
                    ];
                });
        } elseif ($roleId == 3) {
            $list = SuperUser::where('u_role_id', 4)                  // only verifiers
                ->where('u_inst_code', $userData->u_inst_code)        // same institute
                ->where('u_inst_district', $userData->u_inst_district) // same district
                ->where('is_active', 1)                               // only active
                ->with('role', 'district')
                ->get()
                ->map(function ($data) {
                    return [
                        'institute' => [
                            'inst_code'   => $data->u_inst_code ?? '',
                            'inst_name'   => $data->u_inst_name ?? '',
                        ],
                        'phone_no' => $data->u_phone,
                        'name'     => $data->u_fullname,
                        'email'    => $data->u_email,
                        'username' => $data->u_username,
                        'is_active' => (bool)$data->is_active,
                        'district' => [
                            'district_id'   => optional($data->district)->district_id_pk,
                            'district_name' => optional($data->district)->district_name,
                        ],
                        'role' => [
                            'role_id'   => $data->u_role_id ?? '',
                            'role_name' => optional($data->role)->role_name,
                        ],
                    ];
                });
        }
        if (sizeof($list) > 0) {
            $reponse = array(
                'error'     =>  false,
                'message'   =>  'list found',
                'count'     =>   sizeof($list),
                'list'  =>   $list
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
    public function addVerifier(Request $request)
    {
        // Validation
        $validated = Validator::make($request->all(), [
            'phone_no' => 'required',
            'name'     => 'required',
            'email'    => 'required|email',
            'inst_code' => 'required',
            'role_id'  => 'required|in:3,4', // only allow Head Verifier(3) or Verifier(4)

        ]);

        if ($validated->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validated->errors()->first()
            ], 422);
        }

        // Normalize name
        $normalizedName = preg_replace('/\s+/', ' ', trim($request->name));
        $instName = Institute::where('i_code', $request->inst_code)->value('i_name');

        // Check if phone already exists for the role
        $roleId = $request->role_id;
        if ($roleId == 3) {

            // 1. Check phone number
            if (SuperUser::where('u_role_id', 3)->where('u_phone', $request->phone_no)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Head Verifier already exists with this phone number'
                ], 400);
            }

            // 2. Check institute (only one Head Verifier per institute)
            if (SuperUser::where('u_role_id', 3)->where('u_inst_code', $request->inst_code)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Head Verifier already exists with this institute'
                ], 400);
            }

            // 3. Check normalized name
            // if (SuperUser::where('u_role_id', 3)
            //     ->whereRaw("LOWER(REGEXP_REPLACE(u_fullname, '\\s+', ' ', 'g')) = ?", [strtolower($normalizedName)])
            //     ->exists()
            // ) {
            //     return response()->json([
            //         'error' => true,
            //         'message' => 'Head Verifier already exists with this name'
            //     ], 400);
            // }
            if (SuperUser::where('u_role_id', 3)
                ->where('u_email', $request->email)
                ->exists()
            ) {
                return response()->json([
                    'error' => true,
                    'message' => 'Head Verifier already exists with this email'
                ], 400);
            }
        }
        if ($roleId == 4) {

            // Check phone
            if (SuperUser::where('u_role_id', 4)->where('u_phone', $request->phone_no)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Verifier already exists with this phone number'
                ], 400);
            }

            // // Check name
            // if (SuperUser::where('u_role_id', 4)
            //     ->whereRaw("LOWER(REGEXP_REPLACE(u_fullname, '\\s+', ' ', 'g')) = ?", [strtolower($normalizedName)])
            //     ->exists()
            // ) {
            //     return response()->json([
            //         'error' => true,
            //         'message' => 'Verifier already exists with this name'
            //     ], 400);
            // }
            if (SuperUser::where('u_role_id', 4)
                ->where('u_email', $request->email)
                ->exists()
            ) {
                return response()->json([
                    'error' => true,
                    'message' => 'Verifier already exists with this email'
                ], 400);
            }
        }
        $user_id = $request->auth_user_id ?? null;
        $now = now();
        // Create the user
        $superUser = SuperUser::create([
            'u_fullname'      => $normalizedName,
            'u_email'         => $request->email,
            'u_username'      => $request->username,
            'u_phone'         => $request->phone_no,
            'u_inst_name'     => $instName,
            'u_inst_code'     => $request->inst_code,
            'u_role_id'       => $roleId,
            'u_inst_district' => $request->district,
            'created_at'      => now()
        ]);

        if ($superUser) {
            auditTrail("{$normalizedName} has successfully created headverifier  at {$user_id} on {$now}.", 'insert');
            return response()->json([
                'error'   => false,
                'message' => 'User created successfully',
                'data'    => $superUser
            ]);
        }

        return response()->json([
            'error'   => true,
            'message' => 'Something went wrong while creating the user'
        ], 500);
    }

    public function updateVerifier(Request $request)
    {
        // Validation
        $validated = Validator::make($request->all(), [
            'phone_no' => 'required',
            'name'     => 'required',
            'email'    => 'required|email',
            'inst_code' => 'required',
            'role_id'  => 'required|in:3,4', // ensure role is valid

        ]);
        $now = now();

        if ($validated->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validated->errors()->first()
            ], 422);
        }

        // Normalize name
        $normalizedName = preg_replace('/\s+/', ' ', trim($request->name));
        $instName = Institute::where('i_code', $request->inst_code)->value('i_name');

        // Find user by phone
        $existingUser = SuperUser::where('u_phone', $request->phone_no)->first();

        if (!$existingUser) {
            return response()->json([
                'error'   => true,
                'message' => 'User not found'
            ], 404);
        }

        // Check if normalized name already exists for the role (excluding current user)
        $roleId = $request->role_id;
        if (SuperUser::where('u_role_id', $roleId)
            ->whereRaw('LOWER(REGEXP_REPLACE(u_fullname, \'\\s+\', \' \', \'g\')) = ?', [strtolower($normalizedName)])
            ->where('u_id', '!=', $existingUser->u_id)
            ->exists()
        ) {
            $message = $roleId == 3 ? 'Head Verifier already exists with this name' : 'Verifier already exists with this name';
            return response()->json(['error' => true, 'message' => $message], 400);
        }

        // Update user
        $existingUser->update([
            'u_fullname'      => $normalizedName,
            'u_email'         => $request->email,
            'u_username'      => $request->username,
            'u_phone'         => $request->phone_no,
            'u_inst_name'     => $instName,
            'u_inst_code'     => $request->inst_code,
            'u_role_id'       => $roleId,
            'u_inst_district' => $request->district,
            'updated_at'      => now()
        ]);
        auditTrail("{$normalizedName} has successfully updated headverifier  at {$existingUser->u_id} on {$now}.", 'update');
        return response()->json([
            'error'   => false,
            'message' => 'User updated successfully',
            'data'    => $existingUser
        ]);
    }

    public function checkValidationFields(Request $request, $role)
    {
        try {
            if ($role === 'COUNCIL') {
                return response()->json([
                    'error'   => false,
                    'message' => 'All Validation passed',
                    'data'    => []
                ]);
            }

            $validated = Validator::make($request->all(), [
                'student_phone'      => 'required',
                'student_first_name' => 'required',
                'student_last_name'  => 'required',
                'student_email'      => 'required|email',
            ]);

            if ($validated->fails()) {
                return response()->json([
                    'error'   => true,
                    'message' => $validated->errors()->first()
                ], 422);
            }

            return response()->json([
                'error'   => false,
                'message' => 'Validation passed'
            ]);
        } catch (\Exception $e) {
            // Catch any unexpected exception
            return response()->json([
                'error'   => true,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getBranchByIfsc(Request $request, $ifsc)
    {
        $ifsc = strtoupper($request->ifsc);
        $response = Http::get("https://ifsc.razorpay.com/{$ifsc}");

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'bank'   => $data['BANK'] ?? null,
                'branch' => $data['BRANCH'] ?? null,
                'address' => $data['ADDRESS'] ?? null,
            ]);
        }

        return response()->json(['error' => 'Invalid IFS code'], 400);
    }
    public function districtWiseVerifier(Request $request)
    {
        $role_id = $request->auth_role_id;
        $district_id = $request->district_id;
        if ($role_id == 1) {
            $today = now();

            // Get all head verifiers in the district
            $verifiers = SuperUser::with(['HeadVerifierStudentAssign' => function ($query) use ($district_id) {
                $query->where('dist_id', $district_id);
            }, 'institute'])
                ->select('u_id', 'u_fullname', 'u_inst_code', 'u_phone')
                ->where('u_role_id', 3)
                ->where('u_inst_district', $district_id)
                ->get();


            // Build verifier list with student count
            $verifier_list = $verifiers->map(function ($verifier) use ($district_id) {
                $student_count = HeadVerifierStudentAssign::where('dist_id', $district_id)
                    ->where('head_verifier_id', $verifier->u_id)
                    ->count();

                return [
                    'verifier_id'        => $verifier->u_id,
                    'verifier_name'      => $verifier->u_fullname,
                    'verifier_inst_name' => optional($verifier->institute)->i_name,
                    'verifier_phone'     => $verifier->u_phone,
                    'student_count'      => $student_count,
                ];
            });


            $count = $verifier_list->count();


            $student_list = Student::where('s_home_district', $district_id)
                ->where('is_payment', 1)
                ->count();


            $assign_student_list = Student::where('s_home_district', $district_id)
                ->where('is_assign', 1)
                ->count();

            // Remaining students
            $left_student_list = $student_list - $assign_student_list;

            return response()->json([
                'error' => false,
                'distribution' => $verifier_list,
                'count' => $count,
                'student_count' => $student_list,
                'assigned_student_count' => $assign_student_list,
                'left_student_count' => $left_student_list
            ]);
        } elseif ($role_id == 3) {
            $authUserId = $request->auth_user_id;

            // Get all verifiers under this head verifier's district
            $verifierList = SuperUser::with('institute')
                ->select('u_id', 'u_fullname', 'u_inst_code', 'u_phone')
                ->where('u_role_id', 4) // Regular verifier
                ->where('u_inst_district', $district_id)

                ->get()
                ->map(function ($verifier) use ($authUserId, $district_id) {
                    $studentCount = VerifierStudentAssign::where([
                        ['verifier_id', '=', $verifier->u_id],
                        ['head_verifier_id', '=', $authUserId],
                        ['dist_id', '=', $district_id],
                    ])
                        ->count();

                    return [
                        'verifier_id'   => $verifier->u_id,
                        'verifier_name' => $verifier->u_fullname,
                        'student_count' => $studentCount,
                        'verifier_phone' => $verifier->u_phone,
                        // Optional: include institute name if needed
                        'verifier_inst_name' => optional($verifier->institute)->i_name,
                    ];
                });

            // dd($verifierList);
            $count = $verifierList->count(); // total verifiers under head

            // Total students assigned to this head verifier
            $student_list = HeadVerifierStudentAssign::where('dist_id', $district_id)
                ->where('head_verifier_id', $authUserId)
                ->count();

            // Total students distributed (assigned + distributed)
            $assignStudentCount = HeadVerifierStudentAssign::where('head_verifier_id', $authUserId)
                ->where('dist_id', $district_id)
                ->whereHas('student', function ($query) {
                    $query->where('is_assign', 1)
                        ->where('is_distribute', 1);
                })
                ->count();

            // Remaining students under this head verifier
            $left_student_list = $student_list - $assignStudentCount;

            return response()->json([
                'error' => false,
                'distribution' => $verifierList,
                'count' => $count,
                'student_count' => $student_list,
                'assigned_student_count' => $assignStudentCount,
                'left_student_count' => $left_student_list
            ]);
        }
    }

    public function districtWiseAssign(Request $request)
    {
        $districtId = $request->district_id;
        $role_id    = $request->auth_role_id;

        if ($role_id == 1) {

            $districtId = $request->input('district_id');

            $students = Student::where('s_home_district', $districtId)
                ->where('is_payment', 1)
                ->where('is_assign', 0)
                ->pluck('s_appl_form_num');

            if ($students->isEmpty()) {
                return response()->json(['message' => 'No unassigned students found'], 404);
            }
            $verifiers = SuperUser::where('u_role_id', 3)
                ->where('u_inst_district', $districtId)
                ->get(['u_id', 'u_fullname', 'u_inst_code']);

            if ($verifiers->isEmpty()) {
                return response()->json(['message' => 'No head verifiers found'], 404);
            }
            $totalStudents  = $students->count();
            $totalVerifiers = $verifiers->count();
            $perVerifier    = intdiv($totalStudents, $totalVerifiers);
            $extra          = $totalStudents % $totalVerifiers;

            $distribution   = [];
            $studentIndex   = 0;
            foreach ($verifiers as $index => $verifier) {
                $count = $perVerifier + ($index < $extra ? 1 : 0);
                $assignedStudents = $students->slice($studentIndex, $count);

                if ($assignedStudents->isEmpty()) {
                    continue;
                }
                foreach ($assignedStudents as $formNum) {
                    HeadVerifierStudentAssign::create([
                        'head_verifier_id'   => $verifier->u_id,
                        'dist_id'            => $districtId,
                        'inst_code'          => $verifier->u_inst_code,
                        'student_form_num'   => $formNum,
                        'created_at'         => now(),
                    ]);
                }
                Student::whereIn('s_appl_form_num', $assignedStudents)
                    ->update(['is_assign' => 1]);
                $distribution[] = [
                    'verifier_id'   => $verifier->u_id,
                    'verifier_name' => $verifier->u_fullname,
                    'student_count' => $assignedStudents->count(),
                ];

                $studentIndex += $count;
            }
            auditTrail(
                "Students have been successfully distributed to verifiers by head verifier ID {$user_id} on " . now(),
                'distribute'
            );
            return response()->json([
                'message'         => 'Students assigned successfully to head verifiers',
                'total_students'  => $totalStudents,
                'total_verifiers' => $totalVerifiers,
                'distribution'    => $distribution,
            ]);
        } elseif ($role_id == 3) {
            $user_id = $request->auth_user_id ?? null;

            $headVerifier = SuperUser::where('u_id', $user_id)
                ->where('u_role_id', 3)
                ->first();

            if (!$headVerifier) {
                return response()->json(['message' => 'Invalid Head Verifier'], 404);
            }
            $childVerifiers = SuperUser::where('u_role_id', 4)
                ->where('u_inst_code', $headVerifier->u_inst_code)
                ->where('u_inst_district', $districtId)
                ->get(['u_id', 'u_fullname', 'u_inst_code']);

            if ($childVerifiers->isEmpty()) {
                return response()->json(['message' => 'No verifiers found under this head verifier'], 404);
            }
            $students =   HeadVerifierStudentAssign::where('head_verifier_id', $user_id)
                ->where('dist_id', $districtId)
                ->whereHas('student', function ($query) {
                    $query->where('is_assign', 1)
                        ->where('is_distribute', 0);
                })
                ->pluck('student_form_num');

            if ($students->isEmpty()) {
                return response()->json(['message' => 'No students to distribute'], 404);
            }
            $totalStudents  = $students->count();
            $totalVerifiers = $childVerifiers->count();
            $perVerifier    = intdiv($totalStudents, $totalVerifiers);
            $extra          = $totalStudents % $totalVerifiers;

            $distribution = [];
            $studentIndex = 0;
            DB::beginTransaction();

            try {
                foreach ($childVerifiers as $index => $verifier) {
                    $count = $perVerifier + ($index < $extra ? 1 : 0);
                    $assignedStudents = $students->slice($studentIndex, $count)->values();

                    if ($assignedStudents->isEmpty()) {
                        continue;
                    }


                    foreach ($assignedStudents as $formNum) {
                        VerifierStudentAssign::create([
                            'head_verifier_id' => $user_id,
                            'verifier_id'      => $verifier->u_id,
                            'dist_id'          => $districtId,
                            'inst_code'        => $verifier->u_inst_code,
                            'student_form_num' => $formNum,
                            'created_at'       => now(),
                        ]);
                    }

                    Student::whereIn('s_appl_form_num', $assignedStudents)
                        ->update(['is_distribute' => 1]);

                    $distribution[] = [
                        'verifier_id'   => $verifier->u_id,
                        'verifier_name' => $verifier->u_fullname,
                        'student_count' => $assignedStudents->count(),
                    ];

                    $studentIndex += $count;
                }

                DB::commit();
                auditTrail("Students have been successfully distributed to verifiers by head verifier ID {$user_id} on " . now(), 'distribute');
                return response()->json([
                    'message'         => 'Students distributed successfully to verifiers',
                    'total_students'  => $totalStudents,
                    'total_verifiers' => $totalVerifiers,
                    'distribution'    => $distribution,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Distribution failed',
                    'error'   => $e->getMessage(),
                ], 500);
            }
        }
    }
    public function otherdistrictverifier(Request $request)
    {
        if ($request->district === 'OTHER') {
            $effective_district_id = 15; // force to district 15
            $authUserId = $request->auth_user_id; // head verifier ID

            // Get all verifiers (role_id = 4) under district 15
            $verifierList = SuperUser::with('institute')
                ->select('u_id', 'u_fullname', 'u_inst_code', 'u_phone')
                ->where('u_role_id', 3) // Regular verifier
                ->where('u_inst_district', $effective_district_id)
                ->get()
                ->map(function ($verifier) use ($authUserId, $effective_district_id) {
                    // Count students assigned to this verifier under the head
                    $studentCount = VerifierStudentAssign::where([
                        ['verifier_id', '=', $verifier->u_id],
                        ['head_verifier_id', '=', $authUserId],
                        ['dist_id', '=', $effective_district_id],
                    ])->count();

                    return [
                        'verifier_id'        => $verifier->u_id,
                        'verifier_name'      => $verifier->u_fullname,
                        'student_count'      => $studentCount,
                        'verifier_phone'     => $verifier->u_phone,
                        'verifier_inst_name' => optional($verifier->institute)->i_name,
                    ];
                });

            $count = $verifierList->count(); // total verifiers under head

            // Total students assigned to this head verifier (in dist 15)
            $student_list = HeadVerifierStudentAssign::where('dist_id', $effective_district_id)
                ->where('head_verifier_id', $authUserId)
                ->count();

            // Students distributed (assigned + distributed)
            $assignStudentCount = HeadVerifierStudentAssign::where('head_verifier_id', $authUserId)
                ->where('dist_id', $effective_district_id)
                ->whereHas('student', function ($query) {
                    $query->where('is_assign', 1)
                        ->where('is_distribute', 1);
                })
                ->count();

            // Remaining students under this head verifier
            $left_student_list = $student_list - $assignStudentCount;

            return response()->json([
                'error' => false,
                'distribution' => $verifierList,
                'count' => $count,
                'student_count' => $student_list,
                'assigned_student_count' => $assignStudentCount,
                'left_student_count' => $left_student_list
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid request',
        ]);
    }
    public function inActiveVerifier(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'phone_no' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validated->errors()->first()
            ], 422);
        }
        $authUserId = $request->auth_user_id;
        $user = SuperUser::where('u_phone', $request->phone_no)->first();

        if (!$user) {
            return response()->json([
                'error'   => true,
                'message' => 'User not found'
            ], 404);
        }

        // Always set inactive
        $user->is_active = 0;
        $user->updated_at = now();
        $user->save();
        auditTrail(
            "User ID {$user->u_id} has been deactivated by Head Verifier ID {$authUserId} on " . now(),
            'distribute'
        );


        return response()->json([
            'error'   => false,
            'message' => "User has been successfully deactivated",
            'data'    => $user
        ]);
    }
}
