<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Book;
use App\Http\Resources\OrderResource;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $orderService;
    protected $whatsappService;

    public function __construct(OrderService $orderService, WhatsAppService $whatsappService)
    {
        $this->orderService = $orderService;
        $this->whatsappService = $whatsappService;
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request->validated());
            
            // Send WhatsApp notification
            $this->whatsappService->sendOrderNotification($order);
            
            return new OrderResource($order);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create order',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Order $order)
    {
        // Check if user can view this order
        if (auth()->check()) {
            $user = auth()->user();
            if ($order->user_id !== $user->id && !$user->is_staff && !$user->is_superuser) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return new OrderResource($order->load(['items.book', 'user']));
    }

    public function index(Request $request)
    {
        $query = Order::with(['items.book', 'user']);
        
        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->is_staff && !$user->is_superuser) {
                $query->where('user_id', $user->id);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order number or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ILIKE', '%' . $search . '%')
                  ->orWhere('customer_name', 'ILIKE', '%' . $search . '%')
                  ->orWhere('customer_email', 'ILIKE', '%' . $search . '%');
            });
        }

        $orders = $query->latest()->paginate($request->get('per_page', 15));
        
        return OrderResource::collection($orders);
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'shipped') {
            $updateData['shipped_at'] = now();
            if ($request->has('tracking_number')) {
                $updateData['tracking_number'] = $request->tracking_number;
            }
        }

        if ($request->status === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }

        $order->update($updateData);

        return new OrderResource($order->load(['items.book', 'user']));
    }

    public function cancel(Order $order)
    {
        // Check authorization
        if (auth()->check()) {
            $user = auth()->user();
            if ($order->user_id !== $user->id && !$user->is_staff && !$user->is_superuser) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if order can be cancelled
        if (in_array($order->status, ['shipped', 'delivered', 'cancelled', 'refunded'])) {
            return response()->json([
                'error' => 'Order cannot be cancelled in current status'
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        // Restore stock quantities
        foreach ($order->items as $item) {
            $item->book->increment('stock_quantity', $item->quantity);
            $item->book->decrement('sales_count', $item->quantity);
        }

        return new OrderResource($order->load(['items.book', 'user']));
    }

    public function statistics(Request $request)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::whereIn('status', ['delivered', 'shipped'])->sum('total_amount'),
            'monthly_revenue' => Order::whereIn('status', ['delivered', 'shipped'])
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),
        ];

        return response()->json(['data' => $stats]);
    }
}
