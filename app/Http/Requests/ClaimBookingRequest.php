<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClaimBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth check happens here — no need to repeat it in the controller
        return auth()->check();
    }

    public function rules(): array
    {
        // No input fields to validate — all logic is in withValidator
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Booking $booking */
            $booking = $this->route('booking');

            // Already claimed by this user — idempotent, let it through
            if ($booking->user_id === auth()->id()) {
                return;
            }

            // Claimed by someone else
            if ($booking->user_id !== null) {
                $validator->errors()->add('booking', 'Booking already claimed.');

                return;
            }

            if ($booking->status !== 'draft') {
                $validator->errors()->add('booking', 'Booking is no longer a draft.');

                return;
            }

            if ($booking->session_token_expires_at->isPast()) {
                $validator->errors()->add('booking', 'Session expired. Please start again.');
            }
        });
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json(['message' => 'Unauthenticated.'], 401)
        );
    }

    protected function failedValidation(Validator $validator): never
    {
        $message = collect($validator->errors()->all())->first();

        // Map specific messages to appropriate HTTP status codes
        $status = match (true) {
            str_contains($message, 'expired') => 403,
            str_contains($message, 'already claimed') => 403,
            default => 422,
        };

        throw new HttpResponseException(
            response()->json(['message' => $message], $status)
        );
    }
}
