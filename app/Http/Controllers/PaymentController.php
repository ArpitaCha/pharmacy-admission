<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Token;
use App\Models\User;
use App\Models\StudentChoice;
use App\Models\PaymentTransaction;
use App\Models\PaymentSpotTransaction;
use App\Models\SpotStudent;
use App\Models\Payment;
use App\Models\Fees;
use Exception;
use Validator;
use DB;
use App\PaymentLib\AESEncDec;
use Illuminate\Support\Carbon;


use App\Http\Resources\StudentChoiceResource;

class PaymentController extends Controller
{

    protected $auth;
    public $back_url = null;

    public function __construct()
    {
        //$this->auth = new Authentication();
    }

    //Payment 
    public function AdmissionPaymentFees(Request $request)
    {
        $student_array = $request->input('student_info');

        // Ensure it's a non-empty array and get the first element
        if (!is_array($student_array) || empty($student_array[0])) {
            return response()->json([
                'error' => true,
                'message' => 'Student info is missing or invalid.'
            ], 400);
        }

        // Extract the single student data
        $student_data = $student_array[0];

        // Validate required keys
        $requiredKeys = ['student_name', 'student_phn_no', 'student_payment_for', 'appl_form_num'];
        foreach ($requiredKeys as $key) {
            if (empty($student_data[$key])) {
                return response()->json([
                    'error' => true,
                    'message' => "Missing or empty required field: {$key}"
                ], 400);
            }
        }

        // Get fee amount
        $total_appl_amount = Fees::select('cf_fees_amount')
            ->where('cf_fees_type', 'APPLICATION')
            ->first();

        if (!$total_appl_amount || $total_appl_amount->cf_fees_amount <= 0) {
            return response()->json([
                'error' => true,
                'message' => 'Application fee amount not found or invalid.'
            ], 500);
        }

        $amount = $total_appl_amount->cf_fees_amount;

        $other_data = "{$student_data['student_name']}_{$student_data['student_phn_no']}_{$student_data['student_payment_for']}_{$student_data['appl_form_num']}_{$student_data['session_year']}_{$amount}";

        // Generate a random 10-character order ID
        $orderid = '';
        for ($i = 0; $i < 10; $i++) {
            $d = rand(1, 30) % 2;
            $d = $d ? chr(rand(65, 90)) : chr(rand(48, 57));
            $orderid .= $d;
        }
        // $student_check = PaymentTransaction::where('pmnt_stud_form_num', $student_data['appl_form_num'])
        //     ->where('pmnt_pay_type', $student_data['student_payment_for'])
        //     ->w
        //     ->exists();

        // if ($student_check) {
        //     return response()->json([
        //         'error' => true,
        //         'message' => 'Payment transaction already exists for this student.'
        //     ], 400);
        // } else {
        // Create new payment transaction
        PaymentTransaction::create([
            'order_id' => $orderid,
            'pmnt_modified_by' => $student_data['appl_form_num'],
            'trans_time' => now(),
            'pmnt_created_on' => now(),
            'pmnt_pay_type' => $student_data['student_payment_for'],
            'trans_amount' => $amount,
            'pmnt_stud_form_num' => $student_data['appl_form_num']
        ]);



        auditTrail($student_data['appl_form_num'], "Payment initiated by student: {$student_data['appl_form_num']} with order id: {$orderid} for {$student_data['student_payment_for']}");

        return response()->json([
            'error' => false,
            'message' => 'Payment Data Found',
            'payment_data' => getPaymentData($orderid, $amount, $other_data)
        ]);
    }


    public function paymentSuccess(Request $request)
    {

        // Merchant Order Number|SBIePayRefID/ATRN|Transaction Status|Amount|Currency|Pay Mode|Other Details|Reason/Message|Bank Code|Bank Reference Number|Transaction Date|Country|CIN|Merchant ID|Total Fee GST |Ref1|Ref2|Ref3|Ref4|Ref5|Ref6|Ref7|Ref8|Ref9
        try {
            $trans_details = sbiDecrypt($request->encData);
            $data = explode('|', $trans_details);
            $order_id = $data[0];
            $trans_id = $data[1];
            $trans_status = $data[2];
            $trans_amount = $data[3];
            $currency = $data[4];
            $trans_mode = $data[5];
            $message = $data[7];
            $trans_time = $data[10];
            $marchnt_id = $data[13];
            $other_data = explode('_', $data[5]);


            $student_name = $other_data[0];
            $student_phone_no = $other_data[1];
            $paying_for = $other_data[2];
            $form_num = $other_data[3];
            $session_year = $other_data[4];
            $amount = $other_data[5];
            $map = [
                'APPLICATION' => 1,
            ];

            $status = $map[$paying_for] ?? null;

            if ($status !== null) {
                Student::where([
                    'student_form_num' => $form_num,
                    // 'student_semester' => $semester,
                ])->update([
                    's_admited_status' => $status
                ]);
            }
            $tranction = PaymentTransaction::where('order_id', $order_id)->first();

            if ($tranction) {
                $tranction->update([
                    'trans_id' => $trans_id,
                    'trans_status' => $trans_status,
                    'trans_amount' => $trans_amount,
                    'trans_mode' => $trans_mode,
                    'trans_time' => $trans_time,
                    'marchnt_id' => $marchnt_id,
                    'trans_details' => $trans_details,
                    'is_verified' => 1,

                ]);

                Payment::create([
                    'order_id' => $order_id,
                    'trans_id' => $trans_id,
                    'paid_type' => $paying_for,
                    'paid_amount' => $trans_amount,
                    'paid_at' => $trans_time,
                    'payment_mode' => $trans_mode,
                    'detail' => $trans_details,
                    'form_no' =>  $form_num
                ]);

                Student::where('s_appl_form_num', $form_num)
                    ->update([
                        'is_payment' => 1
                    ]);

                auditTrail($form_num, "Payment {$trans_status} for Application No: {$form_num}, ORDER ID: {$order_id}, TRANSACTION ID: {$trans_id}");

                return redirect()->route('payment.redirect', [
                    'trans_id' => $trans_id,
                    'order_id' => $order_id,
                    'paying_for' => $paying_for,
                    'message' => $message,
                    'currency' => $currency,
                    'trans_amount' => $trans_amount,
                    'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                    'trans_status' => $trans_status,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function paymentFail(Request $request)
    {
        // Merchant Order Number|SBIePayRefID/ATRN|Transaction Status|Amount|Currency|Pay Mode|Other Details|Reason/Message|Bank Code|Bank Reference Number|Transaction Date|Country|CIN|Merchant ID|Total Fee GST |Ref1|Ref2|Ref3|Ref4|Ref5|Ref6|Ref7|Ref8|Ref9
        try {
            $trans_details = sbiDecrypt($request->encData);
            $data = explode('|', $trans_details);
            $order_id = $data[0];
            $trans_id = $data[1];
            $trans_status = $data[2];
            $trans_amount = $data[3];
            $currency = $data[4];
            $trans_mode = $data[5];
            $message = $data[7];
            $trans_time = $data[10];
            $marchnt_id = $data[13];
            $other_data = explode('_', $data[5]);


            $student_name = $other_data[0];
            $student_phone_no = $other_data[1];
            $paying_for = $other_data[2];
            $form_num = $other_data[3];
            $session_year = $other_data[4];
            $amount = $other_data[5];
            $tranction = PaymentTransaction::where('order_id', $order_id)->first();

            if ($tranction) {
                $tranction->update([
                    'trans_id' => $trans_id,
                    'trans_status' => $trans_status,
                    'trans_amount' => $trans_amount,
                    'trans_mode' => $trans_mode,
                    'trans_time' => $trans_time,
                    'marchnt_id' => $marchnt_id,
                    'trans_details' => $trans_details,
                    'is_verified' => 1,
                ]);

                auditTrail($form_num, "Payment {$trans_status} for Application No: {$form_num}, ORDER ID: {$order_id}, TRANSACTION ID: {$trans_id}");
                return redirect()->route('payment.redirect', [
                    'trans_id' => $trans_id,
                    'order_id' => $order_id,
                    'paying_for' => $paying_for,
                    'message' => $message,
                    'currency' => $currency,
                    'trans_amount' => $trans_amount,
                    'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                    'trans_status' => $trans_status,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function paymentPush(Request $request)
    {
        // Merchant Order Number|SBIePayRefID/ATRN|Transaction Status|Amount|Currency|Pay Mode|Other Details|Reason/Message|Bank Code|Bank Reference Number|Transaction Date|Country|CIN|Merchant ID|Total Fee GST |Ref1|Ref2|Ref3|Ref4|Ref5|Ref6|Ref7|Ref8|Ref9

        $trans_details = sbiDecrypt($request->encData);
        $data = explode('|', $trans_details);
        $order_id = $data[0];
        $trans_id = $data[1];
        $trans_status = $data[2];
        $trans_amount = $data[3];
        $currency = $data[4];
        $trans_mode = $data[5];
        $message = $data[7];
        $trans_time = $data[10];
        $marchnt_id = $data[13];
        $other_data = explode('_', $data[5]);

        $student_name = $other_data[0];
        $student_phone_no = $other_data[1];
        $paying_for = $other_data[2];
        $form_num = $other_data[3];
        $session_year = $other_data[4];
        $amount = $other_data[5];

        $tranction = PaymentTransaction::where('order_id', $order_id)->first();

        if ($tranction) {
            $tranction->update([
                'trans_id' => $trans_id,
                'trans_status' => $trans_status,
                'trans_amount' => $trans_amount,
                'trans_mode' => $trans_mode,
                'trans_time' => $trans_time,
                'marchnt_id' => $marchnt_id,
                'trans_details' => $trans_details,
                'is_verified' => 1,
            ]);
            auditTrail($form_num, "Payment {$trans_status} for Application No: {$form_num}, ORDER ID: {$order_id}, TRANSACTION ID: {$trans_id}");

            return redirect()->route('payment.redirect', [
                'trans_id' => $trans_id,
                'order_id' => $order_id,
                'paying_for' => $paying_for,
                'message' => $message,
                'currency' => $currency,
                'trans_amount' => $trans_amount,
                'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                'trans_status' => $trans_status,
            ]);
        }
    }

    //allotment upgrade payment


    //Verify payment - double verification
    public function verifypayment(Request $request)
    {
        $currentDateTime    =   date('Y-m-d H:i:s');
        $startToday         =   date('Y-m-d 00:00:01');
        $previousOneHour    =   Carbon::parse($currentDateTime)->subHour(1)->format('Y-m-d H:i:s');
        //dd($previousOneHour);
        // $allData = PaymentTransaction::where('trans_time', '<=', $previousOneHour)->where('trans_time', '>=', $startToday)->get();
        // return $allData;

        $allData = PaymentTransaction::where('trans_time', '<=', $previousOneHour)->where('trans_time', '>=', $startToday)->chunk(25, function ($people) {
            foreach ($people as $person) {
                $order_id = $person->order_id;
                $marchnt_id     = '1001954';
                $trans_amount   = '500';
                //Two step verification 
                $merchant_order_no = $order_id; // merchant order no
                $merchantid = $marchnt_id;  //merchant id
                $amount = $trans_amount;
                $url = "https://www.sbiepay.sbi/payagg/statusQuery/getStatusQuery"; // double verification url
                $queryRequest = "|$merchantid| $merchant_order_no|$amount";
                $queryRequest33 = http_build_query(array('queryRequest' => $queryRequest, "aggregatorId" => "SBIEPAY", "merchantId" => $merchantid));

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_SSLVERSION, true);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $queryRequest33);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo $error_msg = curl_error($ch);
                }

                curl_close($ch);
                $decrypt = $response;
                $data = explode('|', $decrypt);

                if ($data[2] == "SUCCESS") {
                    $payment_data = PaymentTransaction::where('order_id', $order_id)->first();

                    $user_data = User::where('s_id', $person->pmnt_modified_by)->first();

                    $type = explode('|', $data[5]);
                    if ($payment_data) {
                        $payment_data->update([
                            'trans_id' => $data[1],
                            'trans_status' => $data[2],
                            'trans_amount' => $data[7],
                            'trans_mode' => $data[12],
                            'trans_time' => $data[11],
                            'country_code' => $data[3],
                            'marchnt_id' => $marchnt_id,
                            'trans_details' => $response,
                            'bank_code' => $data[9],
                            'bank_ref' => $data[10],
                            'pmnt_pay_type' => $type[0],
                            'pmnt_modified_by' => $person->pmnt_modified_by,
                        ]);

                        $user_data->update([
                            'is_payment' => 1
                        ]);
                    }
                }
            }
        });

        echo 'Updated';
    }

    //Spot Registration payment

}
