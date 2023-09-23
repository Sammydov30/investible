<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class AdminRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:300'],
            'firstname' => ['required', 'string', 'max:300'],
            'lastname' => ['required', 'string', 'max:300'],
            'phone' => ['required'],
            'address' => ['required'],
            'role' => ['required', 'numeric'],
            'status' => ['required', 'numeric'],
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

