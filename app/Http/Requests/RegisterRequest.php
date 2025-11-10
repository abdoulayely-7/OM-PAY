<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Tout le monde peut s'inscrire
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['required', 'string', 'regex:/^(?:\+221)?\s?(77|70|76|75|78)\s?\d{3}\s?\d{2}\s?\d{2}$/', 'unique:users,telephone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'email.required' => 'L\'email est obligatoire.',
            'email.string' => 'L\'email doit être une chaîne de caractères.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.max' => 'L\'email ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Cet email est déjà utilisé.',

            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'telephone.regex' => 'Le numéro de téléphone doit être au format sénégalais (+221) 77/70/76/75/78 XXX XX XX.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',

            'password.required' => 'Le mot de passe est obligatoire.',
            'password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',

            'password_confirmation.required' => 'La confirmation du mot de passe est obligatoire.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'adresse email',
            'telephone' => 'numéro de téléphone',
            'password' => 'mot de passe',
            'password_confirmation' => 'confirmation du mot de passe',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Nettoyer et normaliser le numéro de téléphone
        if ($this->has('telephone')) {
            $telephone = $this->telephone;

            // Supprimer tous les espaces
            $telephone = preg_replace('/\s+/', '', $telephone);

            // Ajouter +221 si absent
            if (!str_starts_with($telephone, '+221')) {
                if (str_starts_with($telephone, '+')) {
                    // Si commence par + mais pas +221, remplacer
                    $telephone = '+221' . substr($telephone, 1);
                } else {
                    // Ajouter +221
                    $telephone = '+221' . $telephone;
                }
            }

            $this->merge(['telephone' => $telephone]);
        }
    }
}
