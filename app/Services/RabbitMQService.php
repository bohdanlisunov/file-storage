<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RabbitMQService {
    public function publishFileDeletionNotification(array $fileData, string $reason = 'manual'): bool {
        $payload = json_encode([
            'event'           => 'file.deleted',
            'reason'          => $reason,
            'timestamp'       => now()->toIso8601String(),
            'notification_to' => env('NOTIFICATION_EMAIL','admin@example.com'),
            'file'            => [
                'id'            => $fileData['id'],
                'original_name' => $fileData['original_name'],
                'size'          => $fileData['size'],
                'mime_type'     => $fileData['mime_type'],
                'uploaded_at'   => $fileData['created_at'],
                'expired_at'    => $fileData['expires_at'],
            ],
        ]);

        try {
            $host     = env('RABBITMQ_HOST','127.0.0.1');
            $port     = env('RABBITMQ_PORT',15672); // Management HTTP API port
            $user     = env('RABBITMQ_USER','guest');
            $pass     = env('RABBITMQ_PASSWORD','guest');
            $vhost    = urlencode(env('RABBITMQ_VHOST','/'));
            $exchange = env('RABBITMQ_EXCHANGE','file_events');
            $queue    = env('RABBITMQ_QUEUE','file_notifications');

            $response = Http::withBasicAuth($user,$pass)
                ->timeout(5)
                ->post("http://{$host}:{$port}/api/exchanges/{$vhost}/{$exchange}/publish", [
                    'properties'       => ['delivery_mode'=>2,'content_type'=>'application/json'],
                    'routing_key'      => $queue,
                    'payload'          => $payload,
                    'payload_encoding' => 'string',
                ]);

            Log::info('RabbitMQ notification sent', ['file_id'=>$fileData['id'],'reason'=>$reason,'status'=>$response->status()]);
            return true;
        } catch(\Exception $e) {
            // RabbitMQ unavailable — just log, don't crash the app
            Log::warning('RabbitMQ unavailable, notification skipped', ['error'=>$e->getMessage(),'file_id'=>$fileData['id']??null]);
            return false;
        }
    }
}
