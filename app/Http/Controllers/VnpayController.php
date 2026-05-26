<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class VnpayController extends Controller
{
    private const PLANS = [
        'monthly' => ['amount' => 79000, 'days' => 30, 'label' => 'VIP 1 tháng'],
        'yearly'  => ['amount' => 790000, 'days' => 365, 'label' => 'VIP 1 năm'],
    ];

    public function plans()
    {
        return response()->json(['data' => self::PLANS]);
    }

    public function createPayment(Request $request)
    {
        $data = $request->validate([
            'plan' => 'required|in:monthly,yearly',
        ]);

        $plan = self::PLANS[$data['plan']];
        $orderId = 'VIP' . time() . rand(100, 999);

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'order_id' => $orderId,
            'amount' => $plan['amount'],
            'plan' => $data['plan'],
            'status' => 'pending',
        ]);

        // Dùng cấu hình project hiện tại (config/vnpay.php -> env VNPAY_*)
        $vnpUrl = (string) config('vnpay.url');
        $vnpTmnCode = (string) config('vnpay.tmn_code');
        $vnpHashSecret = (string) config('vnpay.hash_secret');
        $vnpReturnUrl = (string) config('vnpay.return_url');

        if ($vnpUrl === '' || $vnpTmnCode === '' || $vnpHashSecret === '' || $vnpReturnUrl === '') {
            return response()->json([
                'message' => 'Missing VNPay config (check VNPAY_URL, VNPAY_TMN_CODE, VNPAY_HASH_SECRET, VNPAY_RETURN_URL)',
                'data' => [
                    'vnpUrl' => $vnpUrl,
                    'vnpTmnCode' => $vnpTmnCode,
                    'vnpReturnUrl' => $vnpReturnUrl,
                ],
            ], 500);
        }

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $vnpTmnCode,
            'vnp_Amount' => $plan['amount'] * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now()->timezone((string) config('vnpay.timezone', 'Asia/Ho_Chi_Minh'))->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            // VNPay docs: tiếng Việt không dấu, không ký tự đặc biệt
            'vnp_OrderInfo' => $this->cleanOrderInfo('Nang cap VIP - ' . $plan['label']),
            'vnp_OrderType' => 'billpayment',
            'vnp_ReturnUrl' => $vnpReturnUrl,
            'vnp_TxnRef' => $orderId,
            'vnp_ExpireDate' => now()->timezone((string) config('vnpay.timezone', 'Asia/Ho_Chi_Minh'))->addMinutes(15)->format('YmdHis'),
        ];

        ksort($inputData);
        $query = '';
        $hashdata = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnpHashSecret);
        $paymentUrl = $vnpUrl . '?' . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        return response()->json([
            'data' => [
                'payment_url' => $paymentUrl,
                'order_id' => $orderId,
            ],
        ]);
    }

    /**
     * VNPay redirect trình duyệt về đây (Return URL).
     * Backend: verify chữ ký → cập nhật payments + VIP → redirect FE.
     *
     * .env: VNPAY_RETURN_URL=http://localhost:8080/vnpay-return
     */
    public function return(Request $request)
    {
        $result = $this->processVnpayReturn($request->all());

        $payload = [
            'success' => $result['ok'],
            'order_id' => $result['order_id'] ?? '',
            'vnp_ResponseCode' => $result['response_code'] ?? '',
            'message' => $result['message'] ?? '',
            'payment' => $result['payment'],
        ];

        if (! config('vnpay.redirect_to_frontend', false)) {
            return response()->json(['data' => $payload]);
        }

        $frontend = rtrim((string) config('vnpay.frontend_url', 'http://localhost:3000'), '/');
        $query = http_build_query([
            'success' => $result['ok'] ? '1' : '0',
            'order_id' => $payload['order_id'],
            'vnp_ResponseCode' => $payload['vnp_ResponseCode'],
            'message' => $payload['message'],
        ]);

        return redirect($frontend.'/vnpay-return?'.$query);
    }

    /**
     * FE (localhost:3000) có thể gọi API này với full query VNPay trả về (JSON).
     * Hoặc dùng GET /api/vnpay/return để backend tự xử lý + redirect.
     */
    public function callback(Request $request)
    {
        $result = $this->processVnpayReturn($request->all());

        if (! $result['ok'] && ($result['reason'] ?? '') === 'invalid_hash') {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        if (! $result['payment']) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $status = $result['ok'] ? 'success' : 'failed';

        return response()->json([
            'data' => [
                'status' => $status,
                'message' => $result['message'],
                'payment' => $result['payment'],
            ],
        ]);
    }

    /**
     * @return array{ok: bool, message: string, payment: ?Payment, order_id: ?string, response_code: ?string, reason?: string}
     */
    private function processVnpayReturn(array $input): array
    {
        $vnpHashSecret = (string) config('vnpay.hash_secret');
        $vnpSecureHash = (string) ($input['vnp_SecureHash'] ?? '');

        $verifyData = $input;
        unset($verifyData['vnp_SecureHash'], $verifyData['vnp_SecureHashType']);
        ksort($verifyData);

        $hashData = '';
        $i = 0;
        foreach ($verifyData as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if ($i === 1) {
                $hashData .= '&'.urlencode((string) $key).'='.urlencode((string) $value);
            } else {
                $hashData .= urlencode((string) $key).'='.urlencode((string) $value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);

        $orderId = (string) ($input['vnp_TxnRef'] ?? '');
        $responseCode = (string) ($input['vnp_ResponseCode'] ?? '');
        $transactionNo = (string) ($input['vnp_TransactionNo'] ?? '');

        $payment = Payment::where('order_id', $orderId)->first();

        if (! $payment) {
            return [
                'ok' => false,
                'message' => 'Không tìm thấy đơn thanh toán',
                'payment' => null,
                'order_id' => $orderId,
                'response_code' => $responseCode,
            ];
        }

        if (! hash_equals($secureHash, $vnpSecureHash)) {
            $payment->update(['status' => 'failed', 'vnp_response_code' => 'INVALID_HASH']);

            return [
                'ok' => false,
                'message' => 'Chữ ký không hợp lệ',
                'payment' => $payment->fresh(),
                'order_id' => $orderId,
                'response_code' => $responseCode,
                'reason' => 'invalid_hash',
            ];
        }

        if ($responseCode === '00') {
            if ($payment->status !== 'success') {
                $payment->update([
                    'status' => 'success',
                    'vnp_transaction_no' => $transactionNo,
                    'vnp_response_code' => $responseCode,
                    'paid_at' => now(),
                ]);

                $plan = self::PLANS[$payment->plan] ?? null;
                if ($plan) {
                    $user = User::find($payment->user_id);
                    if ($user) {
                        $currentExpiry = $user->vip_expires_at && $user->vip_expires_at->isFuture()
                            ? $user->vip_expires_at->copy()
                            : now();
                        $updates = [
                            'vip_expires_at' => $currentExpiry->addDays($plan['days']),
                        ];
                        $protected = config('vnpay.protected_roles', ['ADMIN', 'SUPER_ADMIN']);
                        if (! in_array($user->roleSlug(), $protected, true)) {
                            $updates['role'] = config('vnpay.vip_role', 'VIP');
                        }
                        $user->update($updates);
                    }
                }
            }

            return [
                'ok' => true,
                'message' => 'Thanh toán thành công! Tài khoản đã được nâng cấp VIP.',
                'payment' => $payment->fresh(),
                'order_id' => $orderId,
                'response_code' => $responseCode,
            ];
        }

        $payment->update([
            'status' => 'failed',
            'vnp_transaction_no' => $transactionNo,
            'vnp_response_code' => $responseCode,
        ]);

        return [
            'ok' => false,
            'message' => 'Thanh toán thất bại. Mã lỗi: '.$responseCode,
            'payment' => $payment->fresh(),
            'order_id' => $orderId,
            'response_code' => $responseCode,
        ];
    }

    public function history(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    private function cleanOrderInfo(string $info): string
    {
        $clean = strtr(mb_strtolower($info, 'UTF-8'), [
            'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
            'đ' => 'd',
        ]);

        $clean = preg_replace('/[^A-Za-z0-9 _\\-.,]/', '', $clean) ?? '';
        $clean = preg_replace('/\\s+/', ' ', trim($clean));

        return $clean !== '' ? $clean : 'Nang cap VIP';
    }
}
