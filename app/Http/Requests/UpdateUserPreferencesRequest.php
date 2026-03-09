<?php

namespace App\Http\Requests;

use App\Models\Domain;
use App\Rules\ValidateDomainOwnershipRule;
use App\Rules\ValidateSpaceOwnershipRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferencesRequest extends FormRequest
{
    /**
     * @var
     */
    private $domain;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->input('default_domain')) {
            $this->domain = Domain::where('id', $this->input('default_domain'))->firstOrFail();
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'default_space' => ['nullable', 'integer', new ValidateSpaceOwnershipRule($this->user()->id)],
            'default_domain' => ['integer', new ValidateDomainOwnershipRule($this->user(), $this->domain)],
            'default_stats' => ['nullable', 'integer', 'between:0,1']
        ];
    }
}
