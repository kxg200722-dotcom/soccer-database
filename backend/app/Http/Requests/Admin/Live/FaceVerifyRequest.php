<?php

namespace App\Http\Requests\Admin\Live;

use App\Enums\ResponseCode;
use App\Support\Result;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FaceVerifyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'player_name' => ['required', 'string', 'max:50'],
            'player_no' => ['nullable', 'integer', 'min:0', 'max:99'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    /** 照片格式错误映射 10006（与文件上传口径一致） */
    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed()['photo'] ?? [];
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
