<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        // Si pas d'utilisateur authentifié
        if (!$user || !($user instanceof User)) {
            return false;
        }

        $targetUser = $this->route('user');

        // Si on modifie le profil (pas de targetUser dans la route)
        if (!$targetUser) {
            return true; // L'utilisateur peut modifier son propre profil
        }

        return $user->isAdmin() ||
               $user->isBureauStaff() ||
               $user->id === $targetUser->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = Auth::user();
        $targetUser = $this->route('user');

        $rules = [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255',
                       Rule::unique('users', 'email')->ignore($targetUser ? $targetUser->id : $user->id)],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        // Seul admin peut modifier le rôle et le statut actif
        if ($user && $user instanceof User && $user->isAdmin()) {
            $rules['role'] = ['sometimes', 'required', 'string', Rule::in(['client', 'bureau_staff', 'mechanic', 'admin'])];
            $rules['is_active'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'adresse email',
            'password' => 'mot de passe',
            'role' => 'rôle',
            'phone' => 'numéro de téléphone',
            'address' => 'adresse',
            'company' => 'entreprise',
            'is_active' => 'statut actif',
        ];
    }

    /**
     * Get custom error messages for validator.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.in' => 'Le rôle sélectionné n\'est pas valide.',
        ];
    }
}
