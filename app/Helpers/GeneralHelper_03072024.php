<?php

use Illuminate\Support\Str;
use App\Models\wbscte\AuditTrail;
use App\Models\wbscte\StudentActivity;
use App\Models\wbscte\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\PaymentLib\AESEncDec;

if (!function_exists('generateLaravelLog')) {

    function generateLaravelLog($e)
    {
        $routeArray = app('request')->route()->getAction();
        $controllerAction = class_basename($routeArray['controller']);
        list($controller, $action) = explode('@', $controllerAction);

        Log::info($controller . '||' . $action . ' ERROR-' . $e->getMessage()

            . "\nFile path :" . $e->getFile()
            . "\nline no :" . $e->getLine());
        // dd($controller, $action);
    }
}

if (!function_exists("auditTrail")) {
    function auditTrail($user_id, $task)
    {
        AuditTrail::create([
            'audittrail_user_id' => $user_id,
            'audittrail_ip' => request()->ip(),
            'audittrail_task' => $task,
            'audittrail_date' => now()
        ]);
    }
}

if (!function_exists("studentActivite")) {
    function studentActivite($user_id, $task)
    {
        StudentActivity::create([
            'a_stu_id' => $user_id,
            'a_ip' => request()->ip(),
            'a_task' => $task,
            'a_date' => now()
        ]);
    }
}

if (!function_exists("searchAssociative")) {
    function searchAssociative($arr, $key, $value)
    {
        foreach ($arr as $data) {
            if ($data[$key] == $value) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('sessionYear')) {
    function sessionYear($year)
    {
        $a = $year;
        $b = Str::charAt(($year + 1), 2) . Str::charAt(($year + 1), 3);

        return "{$a}-{$b}";
    }
}

//CHANGE DATE FORMATE OF A DATE
if (!function_exists('formatDate')) {
    function formatDate($date, $fromFormat = 'Y-m-d', $toFormat = 'd-M-Y')
    {
        $dt = new DateTime();
        if ($date != null) {
            $datetime = $dt->createFromFormat($fromFormat, $date)->format($toFormat);
            return $datetime;
        } else {
            return '---';
        }
    }
}

//generate otp
if (!function_exists('generateOTP')) {
    function generateOTP()
    {
        $possible_letters = '1234567890';
        $code = '';
        for ($x = 0; $x < 6; $x++) {
            $code .= ($num = substr($possible_letters, mt_rand(0, strlen($possible_letters) - 1), 1));
        }
        return $code;
    }
}

//get time difference in minute
if (!function_exists('getTimeDiffInMinute')) {
    function getTimeDiffInMinute($time1, $time2)
    {
        $minutes = (strtotime($time1) - strtotime($time2)) / 60;

        return $minutes;
    }
}

//send Sms
if (!function_exists('send_sms')) {
    function send_sms($phone_to, $sms_message)
    {
        $template_id  = 0;

        $response = Http::withoutVerifying()
            ->withQueryParameters([
                'ukey' => 'xa1a8ogxRdKjGM62zMO3yti3P',
                'msisdn' => urlencode($phone_to),
                'language' => 0,
                'credittype' => 7,
                'senderid' => 'TVESD',
                'templateid' => urlencode($template_id),
                'message' => $sms_message,
                'filetype' => 2
            ])->get('https://125.16.147.178/VoicenSMS/webresources/CreateSMSCampaignGet');

        return $response;
    }
}

if (!function_exists('sessionYear')) {
    function sessionYear($year)
    {
        $a = (int)$year - 1;
        $b = Str::charAt($year, 2) . Str::charAt($year, 3);

        return "{$a}-{$b}";
    }
}

if (!function_exists('getFinancialYear')) {
    function getFinancialYear($currentSession, $type = "")
    {
        $current = explode("-", $currentSession);

        $y = $current[0];
        $yy = $current[1];
        $m = date('m');

        $financial_year = array();
        if ($type == 'regular') {
            $year = $y . '-' . ($yy);
            array_push($financial_year, $year);
        } elseif ($type == 'continuing') {
            for ($i = 0; $i <= 2; $i++) {
                if ($i == 0) {
                    $year = $y - 1 . '-' . ($yy - 1);
                    array_push($financial_year, $year);
                } else if ($i == 1) {
                    $year = ($y - ($i + 1)) . '-' . ($yy - 2);
                    array_push($financial_year, $year);
                }
            }
        } else {
            for ($i = 0; $i <= 2; $i++) {
                if ($i == 0) {
                    $year = $y . '-' . ($yy);
                    array_push($financial_year, $year);
                } else if ($i == 1) {
                    $year = ($y - $i) . '-' . ($yy - 1);
                    array_push($financial_year, $year);
                } else {
                    $year = ($y - $i) . '-' . ($yy - 2);
                    array_push($financial_year, $year);
                }
            }
        }

        return $financial_year;
    }
}

//generate random code
if (!function_exists('generateRandomCode')) {
    function generateRandomCode($length = 6)
    {
        $possible_letters = '23456789BCDFGHJKMNPQRSTVWXYZ';
        $code = '';
        for ($x = 0; $x < $length; $x++) {
            $code .= ($num = substr($possible_letters, mt_rand(0, strlen($possible_letters) - 1), 1));
        }
        return $code;
    }
}

if (!function_exists('encryptHEXFormat')) {
    function encryptHEXFormat($data, $key = null)
    {
        if ($key == null) {
            $key = env('ENC_KEY');
        }
        return bin2hex(openssl_encrypt($data, 'aes-256-ecb', $key, OPENSSL_RAW_DATA));
    }
}

if (!function_exists('decryptHEXFormat')) {
    function decryptHEXFormat($data, $key = null)
    {
        if ($key == null) {
            $key = env('ENC_KEY');
        }
        return trim(openssl_decrypt(hex2bin($data), 'aes-256-ecb', $key, OPENSSL_RAW_DATA));
    }
}

if (!function_exists('str_to_hex')) {
    function str_to_hex($string)
    {
        $hexstr = unpack('H*', $string);
        return array_shift($hexstr);
    }
}

if (!function_exists('casteValue')) {
    function casteValue($key)
    {
        $arr = [
            'OBCA' => 'OBC-A',
            'OBCB' => 'OBC-B',
            'GENERAL' => 'GENERAL',
            'SC' => 'SC',
            'ST' => 'ST',
            'DQFGEN' => 'DISTRICT QUOTA FEMALE GENERAL',
            'DQFSC' => 'DISTRICT QUOTA FEMALE SCHEDULED CASTE',
            'DQFST' => 'DISTRICT QUOTA FEMALE SCHEDULED TRIBE',
            'DQOGEN' => 'DISTRICT QUOTA OPEN GENERAL',
            'DQOSC' => 'DISTRICT QUOTA OPEN SCHEDULED CASTE',
            'DQOST' => 'DISTRICT QUOTA OPEN SCHEDULED TRIBE',
            'DQFPC' => 'DISTRICT QUOTA FEMALE PHYSICALLY CHALLENGED',
            'DQOPC' => 'DISTRICT QUOTA OPEN PHYSICALLY CHALLENGED',
            'SQOBCA' => 'STATE QUOTA OBC-A',
            'SQOBCB' => 'STATE QUOTA OBC-B',
            'SQST' => 'STATE QUOTA SCHEDULED TRIBE',
            'SQSC' => 'STATE QUOTA SCHEDULED CASTE',
            'SQGEN' => 'STATE QUOTA GENERAL',
            'SQPC' => 'STATE QUOTA PHYSICALLY CHALLENGED',
            'EXS' => 'WARDS OF EX-SERVICEMAN DIED IN ACTION',
            'EXSM' => 'WARDS OF EX-SERVICEMAN DIED IN ACTION',
            'LLQ' => 'LAND LOOSER QUOTA',
            'TFW' => 'TUTION FEE WAIVER',
            'GEN' => 'GENERAL',
            'PWD' => 'PC',
            'EWS' => 'ECONOMICALLY WEAKER SECTIONS',
            'SQFPC' => 'STATE QUOTA FEMALE PHYSICALLY CHALLENGED',
            'SQFGEN' => 'STATE QUOTA FEMALE GENERAL',
            'SQFSC' => 'STATE QUOTA FEMALE SCHEDULED CASTE',
            'SQFST' => 'STATE QUOTA FEMALE SCHEDULED TRIBE',
            'SQFOBCA' => 'STATE QUOTA FEMALE OBC-A',
            'SQFOBCB' => 'STATE QUOTA FEMALE OBC-B',
            'SQOPC' => 'STATE QUOTA OPEN PHYSICALLY CHALLENGED',
            'SQOGEN' => 'STATE QUOTA OPEN GENERAL',
            'SQOSC' => 'STATE QUOTA OPEN SCHEDULED CASTE',
            'SQOST' => 'STATE QUOTA OPEN SCHEDULED TRIBE',
            'SQOOBCA' => 'STATE QUOTA OPEN OBC-A',
            'SQOOBCB' => 'STATE QUOTA OPEN OBC-B',
            'DQFOBCA' => 'DISTRICT QUOTA FEMALE OBC-A',
            'DQFOBCB' => 'DISTRICT QUOTA FEMALE OBC-B',
            'DQOOBCA' => 'DISTRICT QUOTA OPEN OBC-A',
            'DQOOBCB' => 'DISTRICT QUOTA OPEN OBC-B',
        ];

        return $arr[$key];
    }
}

if (!function_exists('encryptedString')) {
    function encryptedString($requestParameter, $key)
    {
        $aes =  new AESEncDec();
        $EncryptTrans = $aes->encrypt($requestParameter, $key);
        return $EncryptTrans;
    }
}

if (!function_exists('cast')) {
    function cast()
    {
        $category_preference = array(
            'TFW' => 1,
            'LLQ' => 2,
            'EXS' => 3,
            'EWS' => 4,
            'SQFPC' => 5,
            'SQFGEN' => 6,
            'SQFSC' => 7, 'SQFST' => 7, 'SQFOBCA' => 7, 'SQFOBCB' => 7,

            'SQOPC' => 9,
            'SQOGEN' => 10,
            'SQOSC' => 11, 'SQOST' => 11, 'SQOOBCA' => 11, 'SQOOBCB' => 11,

            'DQFPC' => 12, 'DQFGEN' => 13,
            'DQFSC' => 14, 'DQFST' => 14, 'DQFOBCA' => 14, 'DQFOBCB' => 14,
            'DQOPC' => 15, 'DQOGEN' => 16,
            'DQOSC' => 17, 'DQOST' => 17, 'DQOOBCA' => 17, 'DQOOBCB' => 17
        );
        return array_keys($category_preference);
    }
}

if (!function_exists('config_schedule')) {
    function config_schedule($event)
    {
        $time = date('Y-m-d H:i:s');
        //return $event;
        $data = Schedule::where('sch_event', $event)->where('sch_start_dt', '<=', $time)->where('sch_end_dt', '>=', $time)->first();
        //return $data;
        if ($data) {
            return [
                'round' => $data->sch_round,
                'event' => $data->sch_event,
                'status' => true,
            ];
        } else {
            return [
                'round' => '',
                'event' => '',
                'status' => false,
            ];
        }
    }
}

if (!function_exists('generateManagementApplicationNumber')) {
    function generateManagementApplicationNumber($val)
    {
        $sl_num  = str_pad($val, 6, '0', STR_PAD_LEFT);
        $appl_no = date('y') . $sl_num;

        return $appl_no;
    }
}

if (!function_exists('resizeImage')) {
    function resizeImage($path, $width = 200, $height = null)
    {
        $path = public_path() . "/" . $path;
        $mime_type = mime_content_type($path);
        // Get the original image's dimensions
        list($original_width, $original_height) = getimagesize($path);

        $aspect_ratio = $original_width / $original_height;
        $height = $width / $aspect_ratio;

        // Create a new image with the desired dimensions
        $resized_image = imagecreatetruecolor($width, $height);

        // Read the original image
        if ($mime_type == "image/webp") {
            $original_image = imagecreatefromwebp($path);
        } else if ($mime_type == "image/jpeg") {
            $original_image = imagecreatefromjpeg($path);
        } else if ($mime_type == "image/png") {
            $original_image = imagecreatefrompng($path);
        }

        // Resize the original image to fit the desired dimensions
        imagecopyresampled($resized_image, $original_image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);

        // Save the resized image, overwriting the original image
        imagejpeg($resized_image, $path);

        // Free up memory
        imagedestroy($original_image);
        imagedestroy($resized_image);
    }
}
