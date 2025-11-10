<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'telephone_destinataire' => ['required', 'string', 'regex:/^(?:\+221)?\s?(77|70|76|75|78)\s?\d{3}\s?\d{2}\s?\d{2}$/'],
            'montant' => 'required|numeric|min:100|max:1000000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone_destinataire.required' => 'Le numéro de téléphone du destinataire est obligatoire.',
            'telephone_destinataire.regex' => 'Le numéro de téléphone du destinataire doit être au format sénégalais (+221) 77/70/76/75/78 XXX XX XX.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 FCFA.',
            'montant.max' => 'Le montant maximum est de 1 000 000 FCFA.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Nettoyer et normaliser le numéro de téléphone du destinataire
        if ($this->has('telephone_destinataire')) {
            $telephone = $this->telephone_destinataire;

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

            $this->merge(['telephone_destinataire' => $telephone]);
        }
    }
}
