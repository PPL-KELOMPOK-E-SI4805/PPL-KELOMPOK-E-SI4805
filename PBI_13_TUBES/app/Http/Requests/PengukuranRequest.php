<?php

namespace App\Http\Requests;

use App\Models\Balita;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PengukuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hak akses ditangani middleware role:kader pada route
    }

    public function rules(): array
    {
        return [
            'id_balita' => ['required', 'integer', 'exists:balita,id_balita'],
            'tanggal_ukur' => ['required', 'date', 'before_or_equal:today'],
            'berat_badan' => ['required', 'numeric', 'min:1', 'max:30'],
            'tinggi_badan' => ['required', 'numeric', 'min:30', 'max:130'],
            'cara_ukur' => ['required', 'in:terlentang,berdiri'],
            'lingkar_kepala' => ['nullable', 'numeric', 'min:20', 'max:60'],
            'lingkar_lengan' => ['nullable', 'numeric', 'min:5', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_ukur.before_or_equal' => 'Tanggal pengukuran tidak boleh di masa depan.',
            'berat_badan.min' => 'Berat badan di luar rentang wajar (1-30 kg).',
            'berat_badan.max' => 'Berat badan di luar rentang wajar (1-30 kg).',
            'tinggi_badan.min' => 'Panjang/tinggi badan di luar rentang wajar (30-130 cm).',
            'tinggi_badan.max' => 'Panjang/tinggi badan di luar rentang wajar (30-130 cm).',
        ];
    }

    public function attributes(): array
    {
        return [
            'berat_badan' => 'berat badan',
            'tinggi_badan' => 'panjang/tinggi badan',
            'lingkar_kepala' => 'lingkar kepala',
            'lingkar_lengan' => 'LiLA',
            'cara_ukur' => 'cara ukur',
        ];
    }

    /** Tanggal ukur tidak boleh sebelum tanggal lahir balita. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['id_balita', 'tanggal_ukur'])) {
                    return;
                }

                $balita = Balita::find($this->input('id_balita'));
                if ($balita && Carbon::parse($this->input('tanggal_ukur'))->lt(Carbon::parse($balita->tanggal_lahir))) {
                    $validator->errors()->add('tanggal_ukur', 'Tanggal pengukuran tidak boleh sebelum tanggal lahir balita.');
                }
            },
        ];
    }
}
