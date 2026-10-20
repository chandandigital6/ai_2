<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessFollower;
use App\Models\Order;
use App\Models\Review;
use App\Models\ServiceEnquiry;
use App\Models\User;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;

class GrowyaDashboardController extends Controller
{
    public function __construct(private BusinessAccessService $access) {}

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->hasRole('super_admin', 'web')) {
            return view('growya-dashboards.admin', [
                'usersCount' => User::count(),
                'businessesCount' => Business::count(),
                'pendingCount' => Business::where('verification_status', 'pending')->count(),
                'approvedCount' => Business::where('verification_status', 'approved')->count(),
                'rejectedCount' => Business::where('verification_status', 'rejected')->count(),
                'ordersCount' => Order::count(),
                'revenueTotal' => Order::where('payment_status', 'paid')->whereNotIn('status', ['cancelled'])->sum('total_amount'),
                'latestPending' => Business::with('owner:id,name')->where('verification_status', 'pending')->latest('id')->limit(6)->get(),
                'latestOrders' => Order::with('business:id,name')->latest('id')->limit(6)->get(),
            ]);
        }

        // Each business is chosen through real ownership or active staff membership.
        // A global Spatie owner role must never expose other owners' businesses.
        $businesses = Business::query()
            ->where('status', 'active')
            ->where('verification_status', 'approved')
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('businessUsers', function ($membership) use ($user) {
                        $membership->where('user_id', $user->id)->where('is_active', true);
                    });
            })
            ->with('city:id,name')
            ->orderBy('name')
            ->get()
            ->filter(fn (Business $business) => $this->canOpenBusiness($user, $business))
            ->values();

        $request->validate(['business_id' => ['nullable', 'integer', 'min:1']]);
        $selectedId = $request->query('business_id');
        $business = $selectedId
            ? $businesses->firstWhere('id', (int) $selectedId)
            : $businesses->first();
        if ($selectedId && ! $business) {
            abort(404);
        }

        // User who owns only a pending business is still a customer until approval.
        if (! $business) {
            return view('growya-dashboards.customer', [
                'ordersCount' => Order::where('customer_id', $user->id)->count(),
                'followingCount' => BusinessFollower::where('user_id', $user->id)->count(),
                'reviewsCount' => Review::where('user_id', $user->id)->count(),
                'businesses' => Business::where('owner_id', $user->id)->latest('id')->limit(8)->get(),
                'recentOrders' => Order::with('business:id,name')->where('customer_id', $user->id)->latest('id')->limit(5)->get(),
            ]);
        }

        $can = fn (string $permission) => $this->access->can($user, $business, $permission);
        $canProducts = $can('products.view');
        $canServices = $can('services.view');
        // Existing order, payment, return and delivery endpoints use products.view.
        $canOrders = $can('orders.view');
        $canEnquiries = $can('service_enquiries.view');
        $isBusinessOwner = (int) $business->owner_id === (int) $user->id;
        $canStaff = $can('staff.view');
        $canOverview = $can('business.view');
        $workspaceRole = $isBusinessOwner ? 'Business Owner' : ucfirst($this->access->membership($user, $business)?->role ?? 'Staff');

        $stats = [
            'views' => (int) $business->views_count,
            'followers' => (int) $business->followers_count,
            'products' => $canProducts ? $business->products()->count() : null,
            'services' => $canServices ? $business->services()->count() : null,
            'orders' => $canOrders ? $business->orders()->count() : null,
            'pendingOrders' => $canOrders ? $business->orders()->where('status', 'pending')->count() : null,
            'sales' => $can('analytics.view') ? $business->orders()->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->sum('total_amount') : null,
            'enquiries' => $canEnquiries ? ServiceEnquiry::where('business_id', $business->id)->count() : null,
            'appointments' => $can('appointments.view') ? Appointment::where('business_id', $business->id)->whereIn('status', ['pending', 'confirmed'])->whereDate('appointment_date', '>=', now()->toDateString())->count() : null,
        ];
        $recentOrders = $canOrders ? $business->orders()->latest('id')->limit(5)->get() : collect();
        $recentEnquiries = $canEnquiries ? ServiceEnquiry::where('business_id', $business->id)->with('service:id,name')->latest('id')->limit(5)->get() : collect();
        $upcomingAppointments = $can('appointments.view') ? Appointment::where('business_id', $business->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->with('service:id,name')->orderBy('appointment_date')->orderBy('appointment_time')->limit(5)->get() : collect();

        return view('growya-dashboards.owner', compact(
            'businesses', 'business', 'stats', 'recentOrders', 'recentEnquiries',
            'upcomingAppointments', 'canProducts', 'canServices', 'canOrders', 'canStaff', 'canOverview', 'workspaceRole'
        ));
    }

    private function canOpenBusiness(User $user, Business $business): bool
    {
        foreach (config('business_permissions.permissions') as $permission) {
            if ($this->access->can($user, $business, $permission)) {
                return true;
            }
        }

        return false;
    }
}
