<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\ChannelManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BookWithChannelManager implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function handle(ChannelManagerService $service): void
    {
        try {
            $result = $service->book([
                'reference' => $this->booking->reference,
                'unit_id' => $this->booking->unit_id,
                'check_in' => $this->booking->check_in->format('Y-m-d'),
                'check_out' => $this->booking->check_out->format('Y-m-d'),
                'nights' => $this->booking->nights,
                'quantity' => $this->booking->quantity,
                'guests' => $this->booking->guests,
                'total' => $this->booking->total_price,
            ]);

            Log::info('Channel manager raw response', [
                'booking_id' => $this->booking->id,
                'result' => $result,
            ]);

            // Defensively extract channel ref regardless of key name
            $channelRef = $result['external_confirmation_id']
                ?? $result['reference']
                ?? $result['ref']
                ?? null;

            $this->booking->update([
                'status' => 'confirmed',
                'channel_manager_ref' => $channelRef,
            ]);

            Log::info('Booking confirmed successfully', [
                'booking_id' => $this->booking->id,
                'channel_ref' => $channelRef,
            ]);

        } catch (\Throwable $e) {
            Log::warning('Channel manager attempt failed', [
                'booking_id' => $this->booking->id,
                'attempt' => $this->attempts(),
                'of' => $this->tries,
                'error' => $e->getMessage(),
            ]);

            // If retries remain, release back onto the queue cleanly
            // release() does NOT trigger Laravel's error logger unlike rethrowing
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);

                return;
            }

            // All retries exhausted — mark permanently failed
            $this->booking->update(['status' => 'failed']);

            Log::error('Channel manager booking failed permanently', [
                'booking_id' => $this->booking->id,
                'reference' => $this->booking->reference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        // Safety net for unexpected crashes outside our try/catch
        $this->booking->update(['status' => 'failed']);

        Log::error('BookWithChannelManager job failed unexpectedly', [
            'booking_id' => $this->booking->id,
            'error' => $e->getMessage(),
        ]);
    }
}
