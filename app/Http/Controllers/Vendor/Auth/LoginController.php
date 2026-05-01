<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Enums\ViewPaths\Vendor\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\LoginRequest;
use App\Repositories\VendorWalletRepository;
use App\Enums\SessionKey;
use App\Services\VendorService;
use App\Support\SimpleSvgCaptcha;
use App\Traits\RecaptchaTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    use RecaptchaTrait;

    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepo,
        private readonly VendorService             $vendorService,
        private readonly VendorWalletRepository    $vendorWalletRepo,

    )
    {
        $this->middleware('guest:seller', ['except' => ['logout']]);
    }

    /**
     * Image captcha for seller registration (theme forms), not used on vendor login.
     */
    public function generateReCaptcha(): void
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        if (Session::has(SessionKey::VENDOR_RECAPTCHA_KEY)) {
            Session::forget(SessionKey::VENDOR_RECAPTCHA_KEY);
        }
        Session::put(SessionKey::VENDOR_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-Type: '.($recaptchaBuilder instanceof SimpleSvgCaptcha ? 'image/svg+xml' : 'image/jpeg'));
        $recaptchaBuilder->output();
    }

    public function getLoginView(): View
    {
        return view(Auth::VENDOR_LOGIN[VIEW]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $vendor = $this->vendorRepo->getFirstWhere(['identity' => $request['email']]);
        if (!$vendor) {
            Toastr::error(translate('credentials_doesnt_match') . '!');
            return back();
        }
        $passwordCheck = Hash::check($request['password'], $vendor['password']);
        if ($passwordCheck && $vendor['status'] !== 'approved') {
            Toastr::error(translate('Not_approve_yet') . '!');
            return back();
        }
        if ($this->vendorService->isLoginSuccessful($request->email, $request->password, $request->remember)) {
            if ($this->vendorWalletRepo->getFirstWhere(params: ['id' => auth('seller')->id()]) === false) {
                $this->vendorWalletRepo->add($this->vendorService->getInitialWalletData(vendorId: auth('seller')->id()));
            }
            Toastr::info(translate('welcome_to_your_dashboard') . '.');
            return redirect()->route('vendor.dashboard.index');
        } else {
            Toastr::error(translate('credentials_doesnt_match') . '!');
            return back();
        }
    }

    public function logout(): RedirectResponse
    {
        $this->vendorService->logout();
        Toastr::success(translate('logged_out_successfully') . '.');
        return redirect()->route('vendor.auth.login');
    }
}
