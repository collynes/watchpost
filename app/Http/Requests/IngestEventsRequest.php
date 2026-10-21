<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('source');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->routeIs('ingest.batch')) {
            $this->replace(['events' => [$this->all()]]);
        }
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'list', 'min:1', 'max:500'],
            'events.*' => ['required', 'array:schema_version,event_id,source_id,event_type,timestamp,severity,actor_ip,actor_email,actor_ua,actor_country,target,outcome,count,url_path,meta,message'],
            'events.*.schema_version' => ['required', Rule::in(['1'])],
            'events.*.event_id' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'events.*.source_id' => ['required', Rule::in([$this->attributes->get('source')->id])],
            'events.*.event_type' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)+$/'],
            'events.*.timestamp' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z$/', 'before_or_equal:'.now()->addMinutes(5)->toIso8601String()],
            'events.*.severity' => ['sometimes', 'integer', 'between:1,5'],
            'events.*.actor_ip' => ['nullable', 'ip'],
            'events.*.actor_email' => ['nullable', 'email:rfc', 'max:254'],
            'events.*.actor_ua' => ['nullable', 'string', 'max:2048'],
            'events.*.actor_country' => ['nullable', 'string', 'regex:/^[A-Z]{2}$/'],
            'events.*.target' => ['nullable', 'string', 'max:255'],
            'events.*.outcome' => ['nullable', Rule::in(['success', 'failure', 'blocked'])],
            'events.*.count' => ['sometimes', 'integer', 'between:1,100000'],
            'events.*.url_path' => ['nullable', 'string', 'max:2048'],
            'events.*.message' => ['nullable', 'string', 'max:4096'],
            'events.*.meta' => ['nullable', 'array', 'max:50'],
        ];
    }
}
