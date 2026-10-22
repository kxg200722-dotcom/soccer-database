<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

class LiveLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // 可置空（清空直播链接）
            'live_link' => ['nullable', 'url', 'max:512'],
        ];
    }
}
