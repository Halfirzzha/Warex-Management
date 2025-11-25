<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Settings\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected IntegrationSettings $settings;

    public function __construct()
    {
        $this->settings = app(IntegrationSettings::class);
    }

    /**
     * Send WhatsApp message
     *
     * @param string $to Phone number with country code (e.g., 628123456789)
     * @param string $message Text message to send
     * @param array $metadata Additional metadata for logging
     * @return bool Success status
     */
    public function sendMessage(string $to, string $message, array $metadata = []): bool
    {
        if (!$this->settings->whatsapp_enabled) {
            Log::info('WhatsApp notifications are disabled');
            return false;
        }

        if (empty($this->settings->whatsapp_api_url) || empty($this->settings->whatsapp_token)) {
            Log::error('WhatsApp API credentials not configured');
            return false;
        }

        $notificationLog = NotificationLog::create([
            'type' => $metadata['type'] ?? 'WhatsAppMessage',
            'channel' => 'whatsapp',
            'recipient' => $to,
            'message' => $message,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->settings->whatsapp_token,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->settings->whatsapp_api_url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ]);

            if ($response->successful()) {
                $notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                Log::info('WhatsApp message sent successfully', [
                    'to' => $to,
                    'response' => $response->json(),
                ]);

                return true;
            }

            throw new \Exception('WhatsApp API returned error: ' . $response->body());
        } catch (\Exception $e) {
            $notificationLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            Log::error('Failed to send WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if WhatsApp is enabled and configured
     */
    public function isEnabled(): bool
    {
        return $this->settings->whatsapp_enabled
            && !empty($this->settings->whatsapp_api_url)
            && !empty($this->settings->whatsapp_token);
    }

    /**
     * Get configured phone number
     */
    public function getPhoneNumber(): ?string
    {
        return $this->settings->whatsapp_phone_number;
    }
}
