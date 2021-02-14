<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Mollie\Laravel\Facades\Mollie;

class PaymentController extends Controller
{
    /**
     * Status of payment
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhookNotification(Request $request)
    {
        $paymentId = $request->input('id');
        $molliePayment = Mollie::api()->payments->get($paymentId);

        if ($molliePayment->isPaid())
        {
            $payment = Payment::where('mollie_id', $molliePayment->id)->with('user')->first();
            try {
                $user = User::findOrFail($payment->user_id);
                $balance = $user->balance;

                $balance += $molliePayment->amount->value;
                $user->balance = $balance;
                $user->updated_at = Carbon::now();
                $user->save();

                $payment->paid = true;
                $payment->save();

                return response()->json([
                    'payment' => $payment,
                ], 200);
            } catch (\ErrorException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Payment failed.',
            ], 400);
        }
    }

    /**
     * Deposit amount to User
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'depositAmount' => ['required'],
            'bearer' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $user = User::where('session_token', $request->bearer)->first();

        if ($user === null) {
            return response()->json([
                'message' => "No user found",
            ], 404);
        }

        $molliePayment = null;

        $payment = Payment::orderBy('id', 'desc')->first();

        try {
            $id = 1;
            if ($payment)
                $id += $payment->id;

            $molliePayment = Mollie::api()->payments->create([
                "amount" => [
                    "currency" => "EUR",
                    "value" => $request->depositAmount // You must send the correct number of decimals, thus we enforce the use of strings
                ],
                "description" => "Order {$user->id}",
                "redirectUrl" => "http://localhost:8080/account/deposit/{$user->sign_in_code}/status/{$id}",
                "webhookUrl" => 'https://13fd378b0d32.ngrok.io', //route('webhooks.mollie'),
                "metadata" => [
                    "order_id" => $id,
                ],
            ]);

            Payment::create([
                'amount' => $request->depositAmount,
                'user_id' => $user->id,
                'mollie_id' =>  $molliePayment->id,
                'paid' => false,
            ]);

            return response()->json([
                'url' => $molliePayment->getCheckoutUrl(),
            ], 200);
        } catch (\ErrorException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deposit payment status
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paymentId' => ['required'],
            'bearer' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $user = User::where('session_token', $request->bearer)->first();
        if ($user === null) {
            return response()->json([
                'message' => "No user or payment found",
            ], 404);
        }

        $payment = Payment::where('id',$request->paymentId)->where('user_id', $user->id)->with('user')->first();
        if ($payment === null) {
            return response()->json([
                'message' => "No payment found",
            ], 204);
        }

        $molliePayment = Mollie::api()->payments->get($payment->mollie_id);

        if ($molliePayment->isPaid())
        {
            return response()->json([
                'payment' => $payment,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Payment failed.',
            ], 400);
        }
    }
}
