<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\SuperUser;
use App\Models\StudentChoice;
use App\Models\Token;

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

class AuthTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $requiredPermission = null)
    {
        // Skip permission check if 'user_type' is present in route
        if ($request->route('user_type')) {
            return $next($request);
        }

        // Token validation
        $token = $request->header('token');
        if (!$token) {
            return response()->json([
                'error' => true,
                'message' => 'Token missing from request'
            ], 401);
        }

        $now = now();
        $tokenRecord = Token::where('t_token', $token)
            ->where('t_expired_on', '>=', $now)
            ->first();

        if (!$tokenRecord) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $userId = $tokenRecord->t_user_id;

        // Identify user type
        $admin = SuperUser::find($userId);
        // dd($admin);
        if ($admin) {
            $userRoleId = $admin->u_role_id;
            $userData   = $admin;
            $userType   = 'ADMIN';
        } else {
            $student = Student::find($userId);
            $userRoleId = $student->u_role_id ?? null;
            $userData   = $student;
            $userType   = 'STUDENT';
        }

        if (!$userRoleId) {
            return response()->json([
                'error' => true,
                'message' => 'User role not found'
            ], 403);
        }

        // Fetch permitted URLs for role
        $permittedUrlIds = AuthPermission::where('rp_role_id', $userRoleId)
            ->pluck('rp_url_id');

        $permittedUrls = AuthUrl::where('url_visible', 1)
            ->whereIn('url_id', $permittedUrlIds)
            ->pluck('url_name')
            ->toArray();

        // Permission check
        if ($requiredPermission && !in_array($requiredPermission, $permittedUrls)) {
            return response()->json([
                'error' => true,
                'message' => "Oops! You don't have sufficient permission"
            ], 403);
        }

        // Inject user context into request
        $request->merge([
            'auth_user_id' => $userId,
            'auth_role_id' => $userRoleId,
            'auth_user'    => $userData,
            'auth_type'    => $userType,
        ]);
        // dd($userId . '.' . $userRoleId . '.' . $userData);


        return $next($request);
    }
}
