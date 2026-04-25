<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;

class UpdateOwnedThingRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerOfBoundModel('thing');
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string'],
        ];
    }
}
