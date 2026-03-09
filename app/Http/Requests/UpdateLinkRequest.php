<?php

namespace App\Http\Requests;

use App\Models\Link;
use App\Rules\LinkExpirationGateRule;
use App\Rules\LinkPixelGateRule;
use App\Rules\LinkStatsGateRule;
use App\Rules\LinkTargetingGateRule;
use App\Rules\LinkRedirectPasswordGateRule;
use App\Rules\LinkSpaceGateRule;
use App\Rules\ValidateAliasRule;
use App\Rules\ValidateBadWordsRule;
use App\Rules\ValidateDeepLinkRule;
use App\Rules\ValidateTargetKeyRule;
use App\Rules\ValidateGoogleSafeBrowsingRule;
use App\Rules\ValidatePixelOwnershipRule;
use App\Rules\ValidateSpaceOwnershipRule;
use App\Rules\ValidateUrlRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // If the request is to edit a link as a specific user, and the user is not an admin
        if ($this->has('user_id') && $this->user()->role == 0) {
            return false;
        }

        // Check if the link to be edited exists under that user
        if ($this->has('user_id')) {
            Link::where([['id', '=', $this->route('id')], ['user_id', '=', $this->input('user_id')]])->firstOrFail();
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
            'url' => ['bail', 'sometimes', 'required', new ValidateUrlRule(), 'max:2048', new ValidateBadWordsRule(), new ValidateDeepLinkRule($this->user()), new ValidateGoogleSafeBrowsingRule()],
            'alias' => ['sometimes', 'alpha_dash', 'max:255', new ValidateAliasRule(), new ValidateBadWordsRule()],
            'redirect_password' => ['sometimes', 'nullable', 'string', 'min:1', 'max:128', new LinkRedirectPasswordGateRule($this->user())],
            'space_id' => ['nullable', 'integer', new ValidateSpaceOwnershipRule($this->input('user_id') ?? $this->user()->id), new LinkSpaceGateRule($this->user())],
            'pixel_ids' => ['nullable', new ValidatePixelOwnershipRule($this, $this->input('user_id') ?? $this->user()->id), new LinkPixelGateRule($this->user())],
            'sensitive_content' => ['nullable', 'boolean'],
            'privacy' => ['nullable', 'integer', 'between:0,2', new LinkStatsGateRule($this->user())],
            'password' => [(in_array($this->input('privacy'), [0, 1]) ? 'nullable' : 'sometimes'), 'string', 'min:1', 'max:128', new LinkStatsGateRule($this->user())],
            'active_period_start_at' => ['sometimes', 'nullable', 'required_with:active_period_end_at', 'date', 'before:active_period_end_at', new LinkExpirationGateRule($this->user())],
            'active_period_end_at' => ['sometimes', 'nullable', 'required_with:active_period_start_at', 'date', 'after:active_period_start_at', new LinkExpirationGateRule($this->user())],
            'clicks_limit' => ['nullable', 'integer', 'min:0', 'digits_between:0,9', new LinkExpirationGateRule($this->user())],
            'expiration_url' => ['bail', 'nullable', new ValidateUrlRule(), 'max:2048', new ValidateBadWordsRule(), new LinkExpirationGateRule($this->user()), new ValidateDeepLinkRule($this->user()), new ValidateGoogleSafeBrowsingRule()],
            'targets_type' => ['nullable', 'in:' . implode(',', array_keys(config('targets')))],
            'targets' => ['sometimes', 'nullable', 'array', 'max:2'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->sometimes('targets.*.key', ['nullable', 'required_with:targets.*.value', 'max:2048', new ValidateTargetKeyRule($this->input('targets_type')), new LinkTargetingGateRule($this->user())], function ($input, $item) {
            return request()->input('targets_type') && request()->input('targets_type') != 'rotations';
        });

        $validator->sometimes('targets.*.value', ['bail', 'nullable', 'required_with:targets.*.key', 'max:2048', new ValidateUrlRule(), new ValidateBadWordsRule(), new ValidateDeepLinkRule($this->user())], function ($input, $item) {
            return request()->input('targets_type') && request()->input('targets_type') != 'rotations';
        });

        $validator->sometimes('targets.*.value', ['bail', 'nullable', 'max:2048', new ValidateUrlRule(), new ValidateBadWordsRule(), new ValidateDeepLinkRule($this->user())], function ($input, $item) {
            return request()->input('targets_type') && request()->input('targets_type') == 'rotations';
        });
    }
}
