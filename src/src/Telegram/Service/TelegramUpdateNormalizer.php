<?php

declare(strict_types=1);

namespace App\Telegram\Service;

use App\Telegram\DTO\NormalizedChat;
use App\Telegram\DTO\NormalizedEvent;
use App\Telegram\DTO\NormalizedMessage;
use App\Telegram\DTO\NormalizedSender;

final class TelegramUpdateNormalizer
{
    /**
     * Normalize a raw Telegram Update into one or more platform NormalizedEvents.
     *
     * @param array<string, mixed> $update
     *
     * @return list<NormalizedEvent>
     */
    public function normalize(array $update, string $botId): array
    {
        $updateId = (int) ($update['update_id'] ?? 0);
        $traceId = 'tg_'.bin2hex(random_bytes(8));
        $requestId = 'req_'.bin2hex(random_bytes(8));

        if (isset($update['callback_query'])) {
            return [$this->normalizeCallbackQuery($update['callback_query'], $botId, $updateId, $traceId, $requestId)];
        }

        if (isset($update['channel_post'])) {
            return [$this->normalizeMessage($update['channel_post'], $botId, $updateId, $traceId, $requestId, 'channel_post_created')];
        }

        if (isset($update['edited_channel_post'])) {
            return [$this->normalizeMessage($update['edited_channel_post'], $botId, $updateId, $traceId, $requestId, 'channel_post_edited')];
        }

        if (isset($update['edited_message'])) {
            return [$this->normalizeMessage($update['edited_message'], $botId, $updateId, $traceId, $requestId, 'message_edited')];
        }

        if (isset($update['message'])) {
            return $this->normalizeMessageUpdate($update['message'], $botId, $updateId, $traceId, $requestId);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return list<NormalizedEvent>
     */
    private function normalizeMessageUpdate(array $message, string $botId, int $updateId, string $traceId, string $requestId): array
    {
        // Member joined
        if (isset($message['new_chat_members'])) {
            $events = [];
            foreach ($message['new_chat_members'] as $member) {
                $events[] = $this->buildEvent(
                    'member_joined',
                    $botId,
                    $this->extractChat($message),
                    $this->buildSender($member),
                    $this->buildMessage($message),
                    $updateId,
                    $traceId,
                    $requestId,
                );
            }

            return $events;
        }

        // Member left
        if (isset($message['left_chat_member'])) {
            return [$this->buildEvent(
                'member_left',
                $botId,
                $this->extractChat($message),
                $this->buildSender($message['left_chat_member']),
                $this->buildMessage($message),
                $updateId,
                $traceId,
                $requestId,
            )];
        }

        // Bot command
        if ($this->hasCommandEntity($message)) {
            return [$this->normalizeCommand($message, $botId, $updateId, $traceId, $requestId)];
        }

        // Regular message
        return [$this->normalizeMessage($message, $botId, $updateId, $traceId, $requestId, 'message_created')];
    }

    /**
     * @param array<string, mixed> $message
     */
    private function normalizeMessage(array $message, string $botId, int $updateId, string $traceId, string $requestId, string $eventType): NormalizedEvent
    {
        return $this->buildEvent(
            $eventType,
            $botId,
            $this->extractChat($message),
            $this->extractSender($message),
            $this->buildMessage($message),
            $updateId,
            $traceId,
            $requestId,
        );
    }

    /**
     * @param array<string, mixed> $message
     */
    private function normalizeCommand(array $message, string $botId, int $updateId, string $traceId, string $requestId): NormalizedEvent
    {
        $text = (string) ($message['text'] ?? '');
        $commandName = null;
        $commandArgs = null;

        foreach (($message['entities'] ?? []) as $entity) {
            if (($entity['type'] ?? '') === 'bot_command' && ($entity['offset'] ?? -1) === 0) {
                $commandFull = substr($text, (int) $entity['offset'], (int) $entity['length']);
                // Remove @botname suffix if present
                $commandName = explode('@', $commandFull)[0];
                $commandArgs = trim(substr($text, (int) $entity['length'])) ?: null;
                break;
            }
        }

        $msg = new NormalizedMessage(
            id: (string) ($message['message_id'] ?? '0'),
            text: $text,
            replyToMessageId: isset($message['reply_to_message']) ? (string) $message['reply_to_message']['message_id'] : null,
            hasMedia: false,
            timestamp: isset($message['date']) ? date('c', (int) $message['date']) : null,
            commandName: $commandName,
            commandArgs: $commandArgs,
        );

        return $this->buildEvent(
            'command_received',
            $botId,
            $this->extractChat($message),
            $this->extractSender($message),
            $msg,
            $updateId,
            $traceId,
            $requestId,
        );
    }

    /**
     * @param array<string, mixed> $callbackQuery
     */
    private function normalizeCallbackQuery(array $callbackQuery, string $botId, int $updateId, string $traceId, string $requestId): NormalizedEvent
    {
        $cbMessage = $callbackQuery['message'] ?? [];
        $chat = isset($cbMessage['chat']) ? $this->extractChat($cbMessage) : new NormalizedChat(id: '0', type: 'unknown');

        $msg = new NormalizedMessage(
            id: (string) ($cbMessage['message_id'] ?? '0'),
            text: $callbackQuery['data'] ?? null,
            callbackData: $callbackQuery['data'] ?? null,
            callbackQueryId: (string) ($callbackQuery['id'] ?? ''),
        );

        $from = $callbackQuery['from'] ?? [];

        return $this->buildEvent(
            'callback_query',
            $botId,
            $chat,
            $this->buildSender($from),
            $msg,
            $updateId,
            $traceId,
            $requestId,
        );
    }

    /**
     * @param array<string, mixed> $message
     */
    private function extractChat(array $message): NormalizedChat
    {
        $chat = $message['chat'] ?? [];

        $threadId = null;
        if (isset($message['message_thread_id']) && ($message['is_topic_message'] ?? false)) {
            $threadId = (string) $message['message_thread_id'];
        }

        return new NormalizedChat(
            id: (string) ($chat['id'] ?? '0'),
            type: (string) ($chat['type'] ?? 'unknown'),
            title: $chat['title'] ?? null,
            threadId: $threadId,
        );
    }

    /**
     * @param array<string, mixed> $message
     */
    private function extractSender(array $message): NormalizedSender
    {
        $from = $message['from'] ?? $message['sender_chat'] ?? [];

        return $this->buildSender($from);
    }

    /**
     * @param array<string, mixed> $from
     */
    private function buildSender(array $from): NormalizedSender
    {
        return new NormalizedSender(
            id: (string) ($from['id'] ?? '0'),
            username: $from['username'] ?? null,
            firstName: $from['first_name'] ?? $from['title'] ?? null,
            role: 'user',
            isBot: (bool) ($from['is_bot'] ?? false),
        );
    }

    /**
     * @param array<string, mixed> $message
     */
    private function buildMessage(array $message): NormalizedMessage
    {
        $text = $message['text'] ?? $message['caption'] ?? null;
        $hasMedia = false;
        $mediaType = null;

        $mediaTypes = ['photo', 'document', 'video', 'voice', 'audio', 'sticker', 'animation', 'video_note'];
        foreach ($mediaTypes as $type) {
            if (isset($message[$type])) {
                $hasMedia = true;
                $mediaType = $type;
                break;
            }
        }

        $forwardFrom = null;
        if (isset($message['forward_from'])) {
            $forwardFrom = $message['forward_from']['username'] ?? $message['forward_from']['first_name'] ?? 'unknown';
        } elseif (isset($message['forward_from_chat'])) {
            $forwardFrom = $message['forward_from_chat']['title'] ?? 'unknown';
        }

        return new NormalizedMessage(
            id: (string) ($message['message_id'] ?? '0'),
            text: $text,
            replyToMessageId: isset($message['reply_to_message']) ? (string) $message['reply_to_message']['message_id'] : null,
            hasMedia: $hasMedia,
            mediaType: $mediaType,
            forwardFrom: $forwardFrom,
            timestamp: isset($message['date']) ? date('c', (int) $message['date']) : null,
        );
    }

    /**
     * @param array<string, mixed> $message
     */
    private function hasCommandEntity(array $message): bool
    {
        foreach (($message['entities'] ?? []) as $entity) {
            if (($entity['type'] ?? '') === 'bot_command' && ($entity['offset'] ?? -1) === 0) {
                return true;
            }
        }

        return false;
    }

    private function buildEvent(
        string $eventType,
        string $botId,
        NormalizedChat $chat,
        NormalizedSender $sender,
        NormalizedMessage $message,
        int $updateId,
        string $traceId,
        string $requestId,
    ): NormalizedEvent {
        return new NormalizedEvent(
            eventType: $eventType,
            platform: 'telegram',
            botId: $botId,
            chat: $chat,
            sender: $sender,
            message: $message,
            traceId: $traceId,
            requestId: $requestId,
            rawUpdateId: $updateId,
        );
    }
}
