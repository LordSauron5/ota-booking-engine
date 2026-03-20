<?php

namespace App\Http\Requests;

use App\Services\RoomService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDraftBookingRequest extends FormRequest
{
    /**
     * Anyone (guest or authenticated) may submit a draft.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'quantity' => ['required', 'integer', 'min:1'],
            'guests' => ['required', 'array'],
            'guests.adults' => ['required', 'integer', 'min:1'],
            'guests.children' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Business-rule validation that requires resolved data (the unit record).
     * Runs after the field rules above pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $this->validated();

            /** @var RoomService $roomService */
            $roomService = app(RoomService::class);

            $unit = collect($roomService->getRooms())
                ->firstWhere('id', $data['unit_id']);

            if (! $unit) {
                $validator->errors()->add('unit_id', 'Selected room not found.');

                return;
            }

            if ($data['quantity'] > $unit['available_count']) {
                $validator->errors()->add(
                    'quantity',
                    'Requested quantity exceeds available rooms.'
                );
            }

            $totalGuests = $data['guests']['adults'] + $data['guests']['children'];
            $maxGuests = $unit['max_guests'] * $data['quantity'];

            if ($totalGuests > $maxGuests) {
                $validator->errors()->add(
                    'guests',
                    'Too many guests for the selected room configuration.'
                );
            }
        });
    }

    /**
     * Return JSON error responses consistently (the frontend always sends
     * X-Requested-With, but being explicit here removes any ambiguity).
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
