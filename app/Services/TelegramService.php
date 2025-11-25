<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Settings\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected IntegrationSettings $settings;

    public function __construct()
    {
        $this->settings = app(IntegrationSettings::class);
    }

    /**
     * Send Telegram message
     *
     * @param string $message Text message to send (supports HTML)
     * @param array $metadata Additional metadata for logging
     * @return bool Success status
     */
    public function sendMessage(string $message, array $metadata = []): bool
    {
        if (!$this->settings->telegram_enabled) {
            Log::info('Telegram notifications are disabled');
            return false;
        }

        if (empty($this->settings->telegram_bot_token) || empty($this->settings->telegram_chat_id)) {
            Log::error('Telegram bot credentials not configured');
            return false;
        }

        $chatId = $this->settings->telegram_chat_id;

        $notificationLog = NotificationLog::create([
            'type' => $metadata['type'] ?? 'TelegramMessage',
            'channel' => 'telegram',
            'recipient' => $chatId,
            'message' => $message,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);

        try {
            $url = "https://api.telegram.org/bot{$this->settings->telegram_bot_token}/sendMessage";

            $response = Http::timeout(30)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful() && $response->json('ok')) {
                $notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                Log::info('Telegram message sent successfully', [
                    'chat_id' => $chatId,
                    'response' => $response->json(),
                ]);

                return true;
            }

            throw new \Exception('Telegram API returned error: ' . $response->body());
        } catch (\Exception $e) {
            $notificationLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if Telegram is enabled and configured
     */
    public function isEnabled(): bool
    {
        return $this->settings->telegram_enabled
            && !empty($this->settings->telegram_bot_token)
            && !empty($this->settings->telegram_chat_id);
    }

    /**
     * Get configured chat ID
     */
    public function getChatId(): ?string
    {
        return $this->settings->telegram_chat_id;
    }
}
