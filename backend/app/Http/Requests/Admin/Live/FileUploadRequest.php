<?php

namespace App\Http\Requests\Admin\Live;

use App\Enums\ResponseCode;
use App\Support\Result;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FileUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:sign_in,record_confirm,referee_report'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /** 文件类错误映射专项码：格式 10006 / 大小 10007（含 PHP INI 层拒收） */
    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed()['file'] ?? [];
        $code = match (true) {
            isset($failed['Mimes']) || isset($failed['Mimetypes']) => ResponseCode::FILE_TYPE_INVALID,
            isset($failed['Max']) || isset($failed['File']) || isset($failed['Uploaded']) => ResponseCode::FILE_SIZE_EXCEEDED,
            default => ResponseCode::PARAM_INVALID,
        };

        throw new HttpResponseException(
            Result::errorCode($code->value, $code->message(), ['errors' => $validator->errors()])->toResponse(),
        );
    }
}
