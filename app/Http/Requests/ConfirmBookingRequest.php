<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Services\RoomService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfirmBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        // Must be authenticated and own this booking
        return auth()->check() && $booking->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Booking $booking */
            $booking = $this->route('booking');

            if ($booking->status !== 'draft') {
                $validator->errors()->add(
                    'booking',
                    'This booking has already been submitted.'
                );

                return;
            }

            // Resolve the unit and bind it to the request so the
            // controller doesn't have to look it up again
            $unit = collect(app(RoomService::class)->getRooms())
                ->firstWhere('id', $booking->unit_id);

            if (! $unit) {
                $validator->errors()->add('booking', 'Room no longer available.');

                return;
            }

            // Attach the resolved unit so the controller can use it directly
            $this->merge(['resolved_unit' => $unit]);
        });
    }

    /**
     * Return the unit resolved during validation.
     * Avoids a second cache lookup in the controller.
     */
    public function unit(): array
    {
        return $this->input('resolved_unit');
    }

    protected function failedAuthorization(): never
    {
        $booking = $this->route('booking');

        // Distinguish between unauthenticated and forbidden
        $message = ! auth()->check() ? 'Unauthenticated.' : 'Forbidden.';
        $status = ! auth()->check() ? 401 : 403;

        throw new HttpResponseException(
            response()->json(['message' => $message], $status)
        );
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'message' => collect($validator->errors()->all())->first(),
            ], 422)
        );
    }
}
