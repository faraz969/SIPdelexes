<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'login';
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $login = $request->get('login');
        
        // Check if it's a Student ID email format: Student_ID@delexesuniversity.edu.gh
        if (preg_match('/^(\d{8,9})@delexesuniversity\.edu\.gh$/', $login, $matches)) {
            $studentId = $matches[1];
            $student = \App\Models\Student::where('student_id', $studentId)
                ->where('sip_account_created', true)
                ->with('user')
                ->first();
            
            if ($student && $student->user) {
                // Return credentials using the user's email (which should be the student email)
                return [
                    'email' => $student->user->email,
                    'password' => $request->get('password')
                ];
            }
        }
        
        // Check if the input is an email
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            // It's an email
            return [
                'email' => $login,
                'password' => $request->get('password')
            ];
        }
        
        // Check if it's a Student ID (8-digit legacy or 9-digit current format)
        if (preg_match('/^\d{8,9}$/', $login)) {
            $student = \App\Models\Student::where('student_id', $login)
                ->where('sip_account_created', true)
                ->with('user')
                ->first();
            
            if ($student && $student->user) {
                // Return credentials using the user's email
                return [
                    'email' => $student->user->email,
                    'password' => $request->get('password')
                ];
            }
        }
        
        // Otherwise, treat it as a serial number
        return [
            'serial_number' => $login,
            'password' => $request->get('password')
        ];
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Check if user has a SIP account and needs to change password
        $student = \App\Models\Student::where('user_id', $user->id)
            ->where('sip_account_created', true)
            ->first();
        
        if ($student) {
            // Check if password needs to be changed (first login)
            if (is_null($user->password_changed_at)) {
                return redirect()->route('sip.change-password')
                    ->with('warning', 'Please change your password to continue.');
            }
            return redirect()->route('sip.dashboard');
        }

        // Redirect users to their specific dashboards based on role
        if ($user->isHOD()) {
            return redirect()->route('hod.dashboard');
        } elseif ($user->isPresident()) {
            return redirect()->route('president.dashboard');
        } elseif ($user->isRegistrar()) {
            return redirect()->route('registrar.dashboard');
        } elseif ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isLecturer()) {
            return redirect()->route('lecturer.dashboard');
        } elseif ($user->isBank()) {
            return redirect()->route('bank.dashboard');
        }

        // Redirect regular users to portal dashboard
        return redirect()->route('portal.dashboard');
    }
}
