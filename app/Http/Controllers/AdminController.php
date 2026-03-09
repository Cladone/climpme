<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\StoreTaxRateRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Http\Requests\UpdatePixelRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Requests\UpdateSpaceRequest;
use App\Http\Requests\UpdateTaxRateRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Mail\PaymentMail;
use App\Models\Coupon;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkPixel;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Pixel;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Space;
use App\Models\TaxRate;
use App\Traits\DomainTrait;
use App\Traits\LinkTrait;
use App\Traits\PixelTrait;
use App\Traits\SpaceTrait;
use App\Traits\UserTrait;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    use UserTrait, LinkTrait, SpaceTrait, DomainTrait, PixelTrait;

    /**
     * Show the Dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function dashboard()
    {
        $stats = [
            'users' => User::withTrashed()->count(),
            'pages' => Page::count(),
            'plans' => Plan::withTrashed()->count(),
            'payments' => Payment::count()
        ];

        $users = User::withTrashed()->orderBy('id', 'desc')->limit(5)->get();

        $payments = $links = [];
        if (paymentProcessors()) {
            $payments = Payment::with('plan')->orderBy('id', 'desc')->limit(5)->get();
        } else {
            $links = Link::orderBy('id', 'desc')->limit(5)->get();
        }

        return view('admin.dashboard.index', ['stats' => $stats, 'users' => $users, 'payments' => $payments, 'links' => $links]);
    }

    /**
     * Show the Settings forms.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     */
    public function settings(Request $request, $id)
    {
        $domains = [];
        if ($id == 'advanced') {
            $domains = Domain::where('user_id', '=', 0)->get();
        }

        if (view()->exists('admin.settings.' . $id)) {
            return view('admin.container', ['view' => 'admin.settings.' . $id, 'domains' => $domains]);
        }

        abort(404);
    }

    /**
     * Update the Setting.
     *
     * @param UpdateSettingRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateSetting(UpdateSettingRequest $request, $id)
    {
        foreach ($request->except(['_token', 'submit']) as $key => $value) {
            // If the request is for a file upload
            if($request->hasFile($key)) {
                $value = $request->file($key)->hashName();

                // Check if the file exists
                if (file_exists(public_path('uploads/brand/' . config('settings.' . $key)))) {
                    unlink(public_path('uploads/brand/' . config('settings.' . $key)));
                }
                // Save the file
                $request->file($key)->move(public_path('uploads/brand'), $value);
            }

            if ($id == 'cronjob') {
                $value = Str::random(32);
            } elseif ($id == 'license') {
                Setting::where('name', '=', 'license_key')->update(['value' => $request->input('license_key')]);
				Setting::where('name', '=', 'license_type')->update(['value' => 'Extended']);

				return redirect()->route('admin.dashboard');
            }

            Setting::where('name', $key)->update(['value' => $value]);
        }

        return back()->with('success', __('Settings saved.'));
    }

    /**
     * List the Users.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexUsers(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name', 'email']) ? $request->input('search_by') : 'name';
        $planId = $request->input('plan_id');
        $role = $request->input('role');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name', 'email']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $users = User::withTrashed()
            ->when($search, function ($query) use ($search, $searchBy) {
                if($searchBy == 'email') {
                    return $query->searchEmail($search);
                }
                return $query->searchName($search);
            })
            ->when(isset($planId) && !empty($planId), function ($query) use ($planId) {
                return $query->ofPlan($planId);
            })
            ->when(isset($role) && is_numeric($role), function ($query) use ($role) {
                return $query->ofRole($role);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'plan_id' => $planId, 'role' => $role, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        $plans = Plan::withTrashed()->get();

        return view('admin.container', ['view' => 'admin.users.list', 'users' => $users, 'plans' => $plans]);
    }

    /**
     * Show the create User form.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createUser()
    {
        return view('admin.container', ['view' => 'admin.users.new']);
    }

    /**
     * Show the edit User form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editUser($id)
    {
        $user = User::withTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $plans = Plan::withTrashed()->get();

        return view('admin.container', ['view' => 'account.profile', 'user' => $user, 'plans' => $plans]);
    }

    /**
     * Store the User.
     *
     * @param StoreUserRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeUser(StoreUserRequest $request)
    {
        $this->userStore($request);

        return redirect()->route('admin.users')->with('success', __(':name has been created.', ['name' => $request->input('name')]));
    }

    /**
     * Update the User.
     *
     * @param UpdateUserProfileRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateUser(UpdateUserProfileRequest $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($request->user()->id == $user->id && $request->input('role') == 0) {
            return redirect()->route('admin.users.edit', $id)->with('error', __('Operation denied.'));
        }

        $this->userUpdate($request, $user);

        return redirect()->route('admin.users.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Delete the User.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyUser(Request $request, $id)
    {
        if ($request->has('bulk')) {
            User::withTrashed()->whereIn('id', array_slice(json_decode($id, true), 0, 100))->each(function ($user) use ($request) {
                $request->user()->id == $user->id && $user->role == 1 ?: $user->forceDelete();
            });

            return redirect()->route('admin.users')->with('success', __(':count records have been deleted.', ['count' => $request->input('bulk')]));
        }

        $user = User::withTrashed()->findOrFail($id);

        if ($request->user()->id == $user->id && $user->role == 1) {
            return redirect()->route('admin.users.edit', $id)->with('error', __('Operation denied.'));
        }

        $user->forceDelete();

        return redirect()->route('admin.users')->with('success', __(':name has been deleted.', ['name' => $user->name]));
    }

    /**
     * Soft delete the User.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function disableUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->user()->id == $user->id && $user->role == 1) {
            return redirect()->route('admin.users.edit', $id)->with('error', __('Operation denied.'));
        }

        $user->delete();

        return redirect()->route('admin.users.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Restore the soft deleted User.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restoreUser($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Login as the User.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginUser($id)
    {
        $user = User::findOrFail($id);

        Auth::logout();

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * List the Pages.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexPages(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name', 'email']) ? $request->input('search_by') : 'name';
        $visibility = $request->input('visibility');
        $language = $request->input('language');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $pages = Page::when($search, function ($query) use ($search, $searchBy) {
                return $query->searchName($search);
            })
            ->when(isset($visibility) && is_numeric($visibility), function ($query) use ($visibility) {
                return $query->ofVisibility((int)$visibility);
            })
            ->when(isset($language), function ($query) use ($language) {
                return $query->ofLanguage($language);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'visibility' => $visibility, 'language' => $language, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('admin.container', ['view' => 'admin.pages.list', 'pages' => $pages]);
    }

    /**
     * Show the create Page form.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createPage()
    {
        return view('admin.container', ['view' => 'admin.pages.new']);
    }

    /**
     * Show the edit Page form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editPage($id)
    {
        $page = Page::where('id', $id)->firstOrFail();

        return view('admin.container', ['view' => 'admin.pages.edit', 'page' => $page]);
    }

    /**
     * Store the Page.
     *
     * @param StorePageRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePage(StorePageRequest $request)
    {
        $page = new Page;

        $page->name = $request->input('name');
        $page->slug = $request->input('slug');
        $page->visibility = $request->input('visibility');
        $page->language = $request->input('language');
        $page->content = $request->input('content');

        $page->save();

        return redirect()->route('admin.pages')->with('success', __(':name has been created.', ['name' => $request->input('name')]));
    }

    /**
     * Update the Page.
     *
     * @param UpdatePageRequest $request
     * @param $id
     * @return mixed
     */
    public function updatePage(UpdatePageRequest $request, $id)
    {
        $page = Page::findOrFail($id);

        $page->name = $request->input('name');
        $page->slug = $request->input('slug');
        $page->visibility = $request->input('visibility');
        $page->language = $request->input('language');
        $page->content = $request->input('content');

        $page->save();

        return redirect()->route('admin.pages.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Delete the Page.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroyPage(Request $request, $id)
    {
        if ($request->has('bulk')) {
            Page::whereIn('id', array_slice(json_decode($id, true), 0, 100))->each(function ($page) use ($request) {
                $page->delete();
            });

            return redirect()->route('admin.pages')->with('success', __(':count records have been deleted.', ['count' => $request->input('bulk')]));
        }

        $page = Page::findOrFail($id);
        $page->delete();

        return redirect()->route('admin.pages')->with('success', __(':name has been deleted.', ['name' => $page->name]));
    }

    /**
     * List the Payments.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexPayments(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['payment_id', 'invoice_id']) ? $request->input('search_by') : 'payment_id';
        $userId = $request->input('user_id');
        $planId = $request->input('plan_id');
        $interval = $request->input('interval');
        $processor = $request->input('processor');
        $status = $request->input('status');
        $sortBy = in_array($request->input('sort_by'), ['id']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $payments = Payment::with('user')
            ->when(isset($planId) && !empty($planId), function ($query) use ($planId) {
                return $query->ofPlan($planId);
            })
            ->when($userId, function ($query) use ($userId) {
                return $query->ofUser($userId);
            })
            ->when($interval, function ($query) use ($interval) {
                return $query->ofInterval($interval);
            })
            ->when($processor, function ($query) use ($processor) {
                return $query->ofProcessor($processor);
            })
            ->when($status, function ($query) use ($status) {
                return $query->ofStatus($status);
            })
            ->when($search, function ($query) use ($search, $searchBy) {
                if($searchBy == 'invoice_id') {
                    return $query->searchInvoice($search);
                }
                return $query->searchPayment($search);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'interval' => $interval, 'processor' => $processor, 'plan_id' => $planId, 'status' => $status, 'user_id' => $userId, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        // Get all the plans
        $plans = Plan::where([['amount_month', '>', 0], ['amount_year', '>', 0]])->withTrashed()->get();

        $user = null;
        if ($userId) {
            $user = User::where('id', '=', $userId)->withTrashed()->first();
        }

        return view('admin.container', ['view' => 'admin.payments.list', 'payments' => $payments, 'interval' => $interval, 'plans' => $plans, 'user' => $user]);
    }

    /**
     * Show the edit Payment form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editPayment($id)
    {
        $payment = Payment::where('id', $id)->firstOrFail();

        return view('admin.container', ['view' => 'account.payments.edit', 'payment' => $payment]);
    }

    /**
     * Approve the Payment.
     *
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function approvePayment(Request $request, $id)
    {
        $payment = Payment::where([['id', '=', $id], ['status', '=', 'pending']])->firstOrFail();

        $user = User::where('id', $payment->user_id)->first();

        $payment->status = 'completed';
        $payment->save();

        // Assign the plan to the user
        if ($user) {
            $now = Carbon::now();

            if ($user->plan_subscription_id) {
                $user->planSubscriptionCancel();
            }

            $user->plan_id = $payment->plan->id;
            $user->plan_interval = $payment->interval;
            $user->plan_currency = $payment->currency;
            $user->plan_amount = $payment->amount;
            $user->plan_payment_processor = $payment->processor;
            $user->plan_subscription_id = null;
            $user->plan_subscription_status = null;
            $user->plan_created_at = $now;
            $user->plan_recurring_at = null;
            $user->plan_trial_ends_at = $user->plan_trial_ends_at ? $now : null;
            $user->plan_ends_at = $payment->interval == 'month' ? (clone $now)->addMonth() : (clone $now)->addYear();
            $user->save();

            // If a coupon was used
            if (isset($payment->coupon->id)) {
                $coupon = Coupon::find($payment->coupon->id);

                // If a coupon was found
                if ($coupon) {
                    // Increase the coupon usage
                    $coupon->increment('redeems', 1);
                }
            }

            // Attempt to send an email notification
            try {
                Mail::to($user->email)->locale($user->locale)->send(new PaymentMail($payment));
            }
            catch (\Exception $e) {}
        }

        return redirect()->route('admin.payments.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Cancel the Payment.
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function cancelPayment(Request $request, $id)
    {
        $payment = Payment::where([['id', '=', $id], ['status', '=', 'pending']])->firstOrFail();
        $payment->status = 'cancelled';
        $payment->save();

        $user = User::where('id', $payment->user_id)->first();

        if ($user) {
            // Attempt to send an email notification
            try {
                Mail::to($user->email)->locale($user->locale)->send(new PaymentMail($payment));
            }
            catch (\Exception $e) {}
        }

        return redirect()->route('admin.payments.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Show the Invoice.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showInvoice(Request $request, $id)
    {
        $payment = Payment::where([['id', '=', $id], ['status', '!=', 'pending']])->firstOrFail();

        // Sum the inclusive tax rates
        $inclTaxRatesPercentage = collect($payment->tax_rates)->where('type', '=', 0)->sum('percentage');

        // Sum the exclusive tax rates
        $exclTaxRatesPercentage = collect($payment->tax_rates)->where('type', '=', 1)->sum('percentage');

        return view('admin.container', ['view' => 'account.payments.invoice', 'payment' => $payment, 'inclTaxRatesPercentage' => $inclTaxRatesPercentage, 'exclTaxRatesPercentage' => $exclTaxRatesPercentage]);
    }

    /**
     * List the plans.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexPlans(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name']) ? $request->input('search_by') : 'name';
        $visibility = $request->input('visibility');
        $status = $request->input('status');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $plans = Plan::withTrashed()
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->searchName($search);
            })
            ->when(isset($visibility) && is_numeric($visibility), function ($query) use ($visibility) {
                return $query->ofVisibility((int)$visibility);
            })
            ->when(isset($status) && is_numeric($status), function ($query) use ($status) {
                if ($status) {
                    $query->whereNotNull('deleted_at');
                } else {
                    $query->whereNull('deleted_at');
                }
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'visibility' => $visibility, 'status' => $status, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('admin.container', ['view' => 'admin.plans.list', 'plans' => $plans]);
    }

    /**
     * Show the create Plan form.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createPlan()
    {
        $coupons = Coupon::all();

        $taxRates = TaxRate::all();

        return view('admin.container', ['view' => 'admin.plans.new', 'coupons' => $coupons, 'taxRates' => $taxRates]);
    }

    /**
     * Show the edit Plan form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editPlan($id)
    {
        $plan = Plan::withTrashed()->where('id', $id)->firstOrFail();

        $coupons = Coupon::all();

        $taxRates = TaxRate::all();

        return view('admin.container', ['view' => 'admin.plans.edit', 'plan' => $plan, 'coupons' => $coupons, 'taxRates' => $taxRates]);
    }

    /**
     * Store the Plan.
     *
     * @param StorePlanRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePlan(StorePlanRequest $request)
    {
        $plan = new Plan;
        $plan->name = $request->input('name');
        $plan->description = $request->input('description');
        if ($request->has('amount_month')) {
            $plan->amount_month = $request->input('amount_month');
        }
        if ($request->has('amount_year')) {
            $plan->amount_year = $request->input('amount_year');
        }
        if ($request->has('currency')) {
            $plan->currency = $request->input('currency');
        }
        if ($request->has('coupons')) {
            $plan->coupons = $request->input('coupons');
        }
        if ($request->has('tax_rates')) {
            $plan->tax_rates = $request->input('tax_rates');
        }
        if ($request->has('trial_days')) {
            $plan->trial_days = $request->input('trial_days');
        }
        if ($request->has('visibility')) {
            $plan->visibility = $request->input('visibility');
        }
        if ($request->has('position')) {
            $plan->position = $request->input('position');
        }
        $plan->features = $request->input('features');
        $plan->save();

        return redirect()->route('admin.plans')->with('success', __(':name has been created.', ['name' => $request->input('name')]));
    }

    /**
     * Update the Plan.
     *
     * @param UpdatePlanRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePlan(UpdatePlanRequest $request, $id)
    {
        $plan = Plan::withTrashed()->findOrFail($id);
        $plan->name = $request->input('name');
        $plan->description = $request->input('description');
        if (config('settings.license_type')) {
            if (!$plan->isDefault()) {
                if ($request->has('amount_month')) {
                    $plan->amount_month = $request->input('amount_month');
                }
                if ($request->has('amount_year')) {
                    $plan->amount_year = $request->input('amount_year');
                }
                if ($request->has('currency')) {
                    $plan->currency = $request->input('currency');
                }
                if ($request->has('coupons')) {
                    $plan->coupons = $request->input('coupons');
                }
                if ($request->has('tax_rates')) {
                    $plan->tax_rates = $request->input('tax_rates');
                }
                if ($request->has('trial_days')) {
                    $plan->trial_days = $request->input('trial_days');
                }
            }
            if ($request->has('visibility')) {
                $plan->visibility = $request->input('visibility');
            }
            if ($request->has('position')) {
                $plan->position = $request->input('position');
            }
        }
        $plan->features = $request->input('features');
        $plan->save();

        return redirect()->route('admin.plans.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Soft delete the Plan.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function disablePlan($id)
    {
        $plan = Plan::findOrFail($id);

        // Do not delete the default plan
        if ($plan->isDefault()) {
            return redirect()->route('admin.plans.edit', $id)->with('error', __('The default plan can\'t be disabled.'));
        }

        $plan->delete();

        return redirect()->route('admin.plans.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Restore the Plan.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restorePlan($id)
    {
        $plan = Plan::withTrashed()->findOrFail($id);
        $plan->restore();

        return redirect()->route('admin.plans.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * List the Coupons.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexCoupons(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name', 'code']) ? $request->input('search_by') : 'name';
        $type = $request->input('type');
        $status = $request->input('status');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name', 'code']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $coupons = Coupon::withTrashed()
            ->when($search, function ($query) use ($search, $searchBy) {
                if ($searchBy == 'code') {
                    return $query->searchCode($search);
                }
                return $query->searchName($search);
            })
            ->when(isset($type) && is_numeric($type), function ($query) use ($type) {
                return $query->ofType($type);
            })
            ->when(isset($status) && is_numeric($status), function ($query) use ($status) {
                if ($status) {
                    $query->whereNotNull('deleted_at');
                } else {
                    $query->whereNull('deleted_at');
                }
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'type' => $type, 'status' => $status, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('admin.container', ['view' => 'admin.coupons.list', 'coupons' => $coupons]);
    }

    /**
     * Show the create Coupon form.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createCoupon()
    {
        return view('admin.container', ['view' => 'admin.coupons.new']);
    }

    /**
     * Show the edit Coupon form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editCoupon($id)
    {
        $coupon = Coupon::where('id', $id)->withTrashed()->firstOrFail();

        return view('admin.container', ['view' => 'admin.coupons.edit', 'coupon' => $coupon]);
    }

    /**
     * Store the Coupon.
     *
     * @param StoreCouponRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeCoupon(StoreCouponRequest $request)
    {
        $coupon = new Coupon;

        $coupon->name = $request->input('name');
        $coupon->code = $request->input('code');
        $coupon->type = $request->input('type');
        $coupon->days = $request->input('days');
        $coupon->percentage = $request->input('type') ? 100 : $request->input('percentage');
        $coupon->quantity = $request->input('quantity');

        $coupon->save();

        return redirect()->route('admin.coupons')->with('success', __(':name has been created.', ['name' => $request->input('name')]));
    }

    /**
     * Update the Coupon.
     *
     * @param UpdateCouponRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCoupon(UpdateCouponRequest $request, $id)
    {
        $coupon = Coupon::withTrashed()->findOrFail($id);

        $coupon->code = $request->input('code');
        $coupon->days = $request->input('days');
        $coupon->quantity = $request->input('quantity');

        $coupon->save();

        return redirect()->route('admin.coupons.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Soft delete the Coupon.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableCoupon($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return redirect()->route('admin.coupons.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Restore the Coupon.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restoreCoupon($id)
    {
        $coupon = Coupon::withTrashed()->findOrFail($id);
        $coupon->restore();

        return redirect()->route('admin.coupons.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * List the Tax Rates.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexTaxRates(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name', 'code']) ? $request->input('search_by') : 'name';
        $type = $request->input('type');
        $status = $request->input('status');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name', 'code']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $taxRates = TaxRate::withTrashed()
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->searchName($search);
            })
            ->when(isset($type) && is_numeric($type), function ($query) use ($type) {
                return $query->ofType($type);
            })
            ->when(isset($status) && is_numeric($status), function ($query) use ($status) {
                if ($status) {
                    $query->whereNotNull('deleted_at');
                } else {
                    $query->whereNull('deleted_at');
                }
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'type' => $type, 'status' => $status, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        return view('admin.container', ['view' => 'admin.tax-rates.list', 'taxRates' => $taxRates]);
    }

    /**
     * Show the create Tax Rate form.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createTaxRate()
    {
        return view('admin.container', ['view' => 'admin.tax-rates.new']);
    }

    /**
     * Show the edit Tax Rate form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editTaxRate($id)
    {
        $taxRate = TaxRate::where('id', $id)->withTrashed()->firstOrFail();

        return view('admin.container', ['view' => 'admin.tax-rates.edit', 'taxRate' => $taxRate]);
    }

    /**
     * Store the Tax Rate.
     *
     * @param StoreTaxRateRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeTaxRate(StoreTaxRateRequest $request)
    {
        $taxRate = new TaxRate;

        $taxRate->name = $request->input('name');
        $taxRate->type = $request->input('type');
        $taxRate->percentage = $request->input('percentage');
        $taxRate->regions = $request->input('regions');

        $taxRate->save();

        return redirect()->route('admin.tax_rates')->with('success', __(':name has been created.', ['name' => $request->input('name')]));
    }

    /**
     * Update the Tax Rate.
     *
     * @param UpdateTaxRateRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTaxRate(UpdateTaxRateRequest $request, $id)
    {
        $taxRate = TaxRate::withTrashed()->findOrFail($id);

        $taxRate->regions = $request->input('regions');

        $taxRate->save();

        return redirect()->route('admin.tax_rates.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Soft delete the Tax Rate.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableTaxRate($id)
    {
        $taxRate = TaxRate::findOrFail($id);
        $taxRate->delete();

        return redirect()->route('admin.tax_rates.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Restore the Tax Rate.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restoreTaxRate($id)
    {
        $taxRate = TaxRate::withTrashed()->findOrFail($id);
        $taxRate->restore();

        return redirect()->route('admin.tax_rates.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * List the Links.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexLinks(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['title', 'alias', 'url']) ? $request->input('search_by') : 'title';
        $userId = $request->input('user_id');
        $spaceId = $request->input('space_id');
        $domainId = $request->input('domain_id');
        $pixelId = $request->input('pixel_id');
        $status = $request->input('status');
        $sortBy = in_array($request->input('sort_by'), ['id', 'clicks', 'title', 'alias', 'url']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $links = Link::with('user', 'domain')
            ->when($status, function ($query) use ($status) {
                if($status == 1) {
                    return $query->active();
                } elseif($status == 2) {
                    return $query->expired();
                }
            })
            ->when($search, function ($query) use ($search, $searchBy) {
                if($searchBy == 'url') {
                    return $query->searchUrl($search);
                } elseif ($searchBy == 'alias') {
                    return $query->searchAlias($search);
                }
                return $query->searchTitle($search);
            })
            ->when($userId, function ($query) use ($userId) {
                return $query->ofUser($userId);
            })
            ->when($spaceId, function ($query) use ($spaceId) {
                return $query->ofSpace($spaceId);
            })
            ->when($domainId, function ($query) use ($domainId) {
                return $query->ofDomain($domainId);
            })
            ->when($pixelId, function ($query) use ($pixelId) {
                return $query->whereIn('id', LinkPixel::select('link_id')->where('pixel_id', '=', $pixelId)->get());
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'user_id' => $userId, 'space_id' => $spaceId, 'domain_id' => $domainId, 'pixel_id' => $pixelId, 'status' => $status, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        $user = null;
        if ($userId) {
            $user = User::where('id', '=', $userId)->withTrashed()->first();
        }

        $space = null;
        if ($spaceId) {
            $space = Space::where('id', '=', $spaceId)->first();
        }

        $domain = null;
        if ($domainId) {
            $domain = Domain::where('id', '=', $domainId)->first();
        }

        $pixel = null;
        if ($pixelId) {
            $pixel = Pixel::where('id', '=', $pixelId)->first();
        }

        return view('admin.container', ['view' => 'admin.links.list', 'links' => $links, 'user' => $user, 'space' => $space, 'domain' => $domain, 'pixel' => $pixel]);
    }

    /**
     * Show the edit Link form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function editLink($id)
    {
        $link = Link::where([['id', '=', $id]])->firstOrFail();

        $spaces = Space::where('user_id', $link->user_id)->get();

        $domains = Domain::where('user_id', $link->user_id)->get();

        $pixels = Pixel::where('user_id', $link->user_id)->get();

        return view('admin.container', ['view' => 'links.edit', 'domains' => $domains, 'spaces' => $spaces, 'pixels' => $pixels, 'link' => $link]);
    }

    /**
     * Update the Link.
     *
     * @param UpdateLinkRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateLink(UpdateLinkRequest $request, $id)
    {
        $link = Link::where('id', '=', $id)->firstOrFail();

        $this->linkUpdate($request, $link);

        return redirect()->route('admin.links.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Delete the Link.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroyLink(Request $request, $id)
    {
        if ($request->has('bulk')) {
            Link::whereIn('id', array_slice(json_decode($id, true), 0, 100))->each(function ($link) use ($request) {
                $link->delete();
            });

            return redirect()->route('admin.links')->with('success', __(':count records have been deleted.', ['count' => $request->input('bulk')]));
        }

        $link = Link::where('id', $id)->firstOrFail();
        $link->delete();

        return redirect()->route('admin.links')->with('success', __(':name has been deleted.', ['name' => $link->displayShortUrl]));
    }

    /**
     * List the Spaces.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexSpaces(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name']) ? $request->input('search_by') : 'name';
        $userId = $request->input('user_id');
        $linkId = $request->input('link_id');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $spaces = Space::with('user')
            ->when($userId, function ($query) use ($userId) {
                return $query->ofUser($userId);
            })
            ->when($linkId, function ($query) use ($linkId) {
                return $query->whereIn('id', Link::select('space_id')->where('id', '=', $linkId)->get());
            })
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->searchName($search);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'user_id' => $userId, 'link_id' => $linkId, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        $user = null;
        if ($userId) {
            $user = User::where('id', '=', $userId)->withTrashed()->first();
        }

        $link = null;
        if ($linkId) {
            $link = Link::where('id', '=', $linkId)->first();
        }

        return view('admin.container', ['view' => 'admin.spaces.list', 'spaces' => $spaces, 'user' => $user, 'link' => $link]);
    }

    /**
     * Show the edit Space form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editSpace($id)
    {
        $space = Space::where([['id', '=', $id]])->firstOrFail();

        return view('admin.container', ['view' => 'spaces.edit', 'space' => $space]);
    }

    /**
     * Update the Space.
     *
     * @param UpdateSpaceRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSpace(UpdateSpaceRequest $request, $id)
    {
        $space = Space::where('id', $id)->firstOrFail();

        $this->spaceUpdate($request, $space);

        return redirect()->route('admin.spaces.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Delete the Space.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroySpace(Request $request, $id)
    {
        if ($request->has('bulk')) {
            Space::whereIn('id', array_slice(json_decode($id, true), 0, 100))->each(function ($space) use ($request) {
                $space->delete();
            });

            return redirect()->route('admin.spaces')->with('success', __(':count records have been deleted.', ['count' => $request->input('bulk')]));
        }

        $space = Space::where('id', $id)->firstOrFail();
        $space->delete();

        return redirect()->route('admin.spaces')->with('success', __(':name has been deleted.', ['name' => $space->name]));
    }

    /**
     * List the Domains.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexDomains(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name']) ? $request->input('search_by') : 'name';
        $userId = $request->input('user_id');
        $linkId = $request->input('link_id');
        $type = $request->input('type');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $domains = Domain::with('user')
            ->when($userId, function ($query) use ($userId) {
                return $query->ofUser($userId);
            })
            ->when($linkId, function ($query) use ($linkId) {
                return $query->whereIn('id', Link::select('domain_id')->where('id', '=', $linkId)->get());
            })
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->searchName($search);
            })
            ->when($type, function ($query) use ($type) {
                if ($type == 1) {
                    return $query->global();
                } else {
                    return $query->private();
                }
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'user_id' => $userId, 'type' => $type, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        $user = null;
        if ($userId) {
            $user = User::where('id', '=', $userId)->withTrashed()->first();
        }

        $link = null;
        if ($linkId) {
            $link = Link::where('id', '=', $linkId)->first();
        }

        return view('admin.container', ['view' => 'admin.domains.list', 'domains' => $domains, 'user' => $user, 'link' => $link]);
    }

    /**
     * Show the create Domain form.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function createDomain()
    {
        return view('admin.container', ['view' => 'domains.new']);
    }

    /**
     * Show the edit Domain form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editDomain($id)
    {
        $domain = Domain::where([['id', '=', $id]])->firstOrFail();

        return view('admin.container', ['view' => 'domains.edit', 'domain' => $domain]);
    }

    /**
     * Store the Domain.
     *
     * @param StoreDomainRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDomain(StoreDomainRequest $request)
    {
        $this->domainStore($request);

        return redirect()->route('admin.domains')->with('success', __(':name has been created.', ['name' => $request->input('name')]));
    }

    /**
     * Update the Domain.
     *
     * @param UpdateDomainRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDomain(UpdateDomainRequest $request, $id)
    {
        $domain = Domain::where([['id', '=', $id]])->firstOrFail();

        $this->domainUpdate($request, $domain);

        return redirect()->route('admin.domains.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Delete the Domain.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroyDomain(Request $request, $id)
    {
        if ($request->has('bulk')) {
            Domain::whereIn('id', array_slice(json_decode($id, true), 0, 100))->each(function ($domain) use ($request) {
                if ($domain->id == config('settings.short_domain')) {
                    Setting::where('name', '=', 'short_domain')->update(['value' => 0]);
                }

                $domain->delete();
            });

            return redirect()->route('admin.domains')->with('success', __(':count records have been deleted.', ['count' => $request->input('bulk')]));
        }

        $domain = Domain::where([['id', '=', $id]])->firstOrFail();
        $domain->delete();

        // If the deleted domain, was a default domain
        if ($domain->id == config('settings.short_domain')) {
            Setting::where('name', '=', 'short_domain')->update(['value' => 0]);
        }

        return redirect()->route('admin.domains')->with('success', __(':name has been deleted.', ['name' => $domain->name]));
    }

    /**
     * List the Pixels.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexPixels(Request $request)
    {
        $search = $request->input('search');
        $searchBy = in_array($request->input('search_by'), ['name']) ? $request->input('search_by') : 'name';
        $userId = $request->input('user_id');
        $linkId = $request->input('link_id');
        $type = $request->input('type');
        $sortBy = in_array($request->input('sort_by'), ['id', 'name']) ? $request->input('sort_by') : 'id';
        $sort = in_array($request->input('sort'), ['asc', 'desc']) ? $request->input('sort') : 'desc';
        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100]) ? $request->input('per_page') : config('settings.paginate');

        $pixels = Pixel::with('user')
            ->when($userId, function ($query) use ($userId) {
                return $query->ofUser($userId);
            })
            ->when($linkId, function ($query) use ($linkId) {
                return $query->whereIn('id', LinkPixel::select('pixel_id')->where('link_id', '=', $linkId)->get());
            })
            ->when($search, function ($query) use ($search, $searchBy) {
                return $query->searchName($search);
            })->when($type, function ($query) use ($type) {
                return $query->ofType($type);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($perPage)
            ->appends(['search' => $search, 'search_by' => $searchBy, 'user_id' => $userId, 'link_id' => $linkId, 'type' => $type, 'sort_by' => $sortBy, 'sort' => $sort, 'per_page' => $perPage]);

        $user = null;
        if ($userId) {
            $user = User::where('id', '=', $userId)->withTrashed()->first();
        }

        $link = null;
        if ($linkId) {
            $link = Link::where('id', '=', $linkId)->first();
        }

        return view('admin.container', ['view' => 'admin.pixels.list', 'pixels' => $pixels, 'user' => $user, 'link' => $link]);
    }

    /**
     * Show the edit Pixel form.
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editPixel($id)
    {
        $pixel = Pixel::where([['id', '=', $id]])->firstOrFail();

        return view('admin.container', ['view' => 'pixels.edit', 'pixel' => $pixel]);
    }

    /**
     * Update the Pixel.
     *
     * @param UpdatePixelRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePixel(UpdatePixelRequest $request, $id)
    {
        $pixel = Pixel::where('id', $id)->firstOrFail();

        $this->pixelUpdate($request, $pixel);

        return redirect()->route('admin.pixels.edit', $id)->with('success', __('Settings saved.'));
    }

    /**
     * Delete the Pixel.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroyPixel(Request $request, $id)
    {
        if ($request->has('bulk')) {
            Pixel::whereIn('id', array_slice(json_decode($id, true), 0, 100))->each(function ($pixel) use ($request) {
                $pixel->delete();
            });

            return redirect()->route('admin.pixels')->with('success', __(':count records have been deleted.', ['count' => $request->input('bulk')]));
        }

        $pixel = Pixel::where('id', $id)->firstOrFail();
        $pixel->delete();

        return redirect()->route('admin.pixels')->with('success', __(':name has been deleted.', ['name' => $pixel->name]));
    }
}
