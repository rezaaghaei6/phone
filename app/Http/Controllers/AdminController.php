<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationCode;
use App\Models\RecaptchaLog;
use App\Helpers\PhoneHelper;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function showCreateAdminForm()
    {
        return view('admin.create');
    }

    public function createAdmin(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        try {
            $phone = PhoneHelper::normalizePhone($request->input('phone'));
            User::create([
                'phone' => $phone,
                'name' => $request->input('name'),
                'surname' => $request->input('name'),
                'is_admin' => true,
            ]);
            return redirect()->route('dashboard')->with('success', 'Admin created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['phone' => $e->getMessage()]);
        }
    }

    public function userLogs()
    {
        $logs = VerificationCode::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.user_logs', compact('logs'));
    }

    public function recaptchaLogs()
    {
        $logs = RecaptchaLog::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.recaptcha_logs', compact('logs'));
    }
}