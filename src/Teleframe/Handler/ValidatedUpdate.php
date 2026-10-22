<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * Opt-in DI wrapper over the uprate — the FormRequest analog (spec 5a §4).
 * A handler asks for it by type: ``function (Update $u, ValidatedUpdate $v)``
 * and the dispatcher resolves it from the active frame. Plain-array
 * guardrails on the ``{from_, chat, message}`` shape only; zero failure
 * magic (Q6a): no throw on invalid, no auto-reply — ``valid()``/``errors()``
 * are explicit reads, Pa recipes replicate against them later (5e).
 */
final class ValidatedUpdate
{
    public function __construct(
        private readonly Update $update,
    ) {
    }

    /** The underlying uprate — passthrough for any field the handler needs. */
    public function update(): Update
    {
        return $this->update;
    }

    /** The guardrail rules, exposed like a FormRequest's ``rules()``. */
    public function rules(): array
    {
        return [
            'from' => ['required', 'array'],
            'from.id' => ['required', 'integer'],
            'chat' => ['required', 'array'],
            'chat.id' => ['required', 'integer'],
            'message' => ['required', 'array'],
            'message.text' => ['nullable', 'string'],
        ];
    }

    /** True when every guardrail passes. */
    public function valid(): bool
    {
        return $this->errors() === [];
    }

    /**
     * Field-shaped errors: path → message list (absent = valid).
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        $data = $this->update->array;
        $errors = [];

        $from = $data['from'] ?? null;
        if (! is_array($from)) {
            $errors['from'] = ['The from_ field is required and must be an array.'];
        } elseif (! isset($from['id']) || ! is_int($from['id'])) {
            $errors['from.id'] = ['The from_ id is required and must be an integer.'];
        }

        $chat = $data['chat'] ?? null;
        if (! is_array($chat)) {
            $errors['chat'] = ['The chat field is required and must be an array.'];
        } elseif (! isset($chat['id']) || ! is_int($chat['id'])) {
            $errors['chat.id'] = ['The chat id is required and must be an integer.'];
        }

        $message = $data['message'] ?? null;
        if (! is_array($message)) {
            $errors['message'] = ['The message field is required and must be an array.'];
        } elseif (isset($message['text']) && ! is_string($message['text'])) {
            $errors['message.text'] = ['The message text must be a string.'];
        }

        return $errors;
    }
}