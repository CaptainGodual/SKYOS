<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:50'],
            'version' => ['nullable', 'string', 'max:20'],
            'file' => ['required', 'file', 'max:102400', 'mimes:pdf,zip,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file must not exceed 100 MB.',
        ];
    }
}
