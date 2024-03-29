<?php

namespace App\Http\Requests\Investment;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SharpUpdateInvestmentMRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            //'account' => ['required', 'string'],
            //'nextofkin' => ['required', 'string'],
            //'plan' => ['required', 'string'],
            //'agreementdate' => ['required', 'string'],
            'amountpaid' => ['required', 'string'],
            //'amounttobereturned' => ['required', 'string'],
            //'percentage' => ['required', 'string'],
            //'return' => ['required', 'string'],
            //'amountpaidsofar' => ['required', 'string'],
            'no_of' => ['required', 'string'],
            //'timeremaining' => ['required', 'string'],
            'startdate' => ['required', 'string'],
            //'stopdate' => ['required', 'string'],
            //'duration' => ['required', 'string'],
            //'witnessname' => ['required', 'string'],
            //'witnessaddress' => ['required', 'string'],
            //'witnessphone' => ['required', 'string'],
            //'status' => ['required']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = array();
        foreach ($validator->errors()->all() as $error) {
            array_push($message, $error);
        }
        $response = response()->json([
            'status' => 'error',
            'message' => $message,
        ], 422);

        throw (new ValidationException($validator, $response))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    public function failedAuthorization()
    {
        throw new AuthorizationException("You don't have the authority to perform this resource");
    }
}

