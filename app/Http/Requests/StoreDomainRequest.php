<?php

namespace App\Http\Requests;

use App\Rules\ValidateDnsRule;
use App\Rules\ValidateDomainNameRule;
use App\Rules\DomainLimitGateRule;
use App\Rules\ValidateExternalUrlRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->input('name')) {
            // Remove the URL protocol
            $this->merge(['name' => str_replace(['https://', 'http://'], '', mb_strtolower($this->input('name')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['bail', 'required', 'max:255', new ValidateDomainNameRule(), new DomainLimitGateRule($this->user()), 'unique:domains,name', new ValidateDnsRule()],
            'index_page' => ['nullable', 'url', 'max:255', new ValidateExternalUrlRule($this->name)],
            'not_found_page' => ['nullable', 'url', 'max:255', new ValidateExternalUrlRule($this->name)]
        ];
    }
}
