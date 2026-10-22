<?php

namespace App\Services\Admin\Live;

use App\Models\MatchFile;
use App\Models\MatchInfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 赛事文件：私有 disk 存储 + 鉴权下载。
 * 文件落盘在事务外；删除时物理文件删除失败仅记 exception 日志，不回滚。
 * 注意：裁判报告书等文件允许赛后上传，不做 finished 锁定（见计划 A14-A17 码表无 40002）。
 */
class MatchFileService
{
    private const DISK = 'local';

    public function listForMatch(MatchInfo $match): array
    {
        return $match->files()->orderByDesc('id')->get()
            ->map(fn (MatchFile $f) => $this->toArray($f))->all();
    }

    public function upload(MatchInfo $match, string $type, UploadedFile $file, int $userId): array
    {
        $path = $file->storeAs(
            sprintf('match-files/%d', $match->id),
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            self::DISK,
        );

        try {
            $record = DB::transaction(function () use ($match, $type, $file, $path, $userId) {
                $record = $match->files()->create([
                    'type' => $type,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $userId,
                ]);

                Log::channel('business')->info('match file uploaded', [
                    'file_id' => $record->id,
                    'match_id' => $match->id,
                    'operator' => $userId,
                    'type' => $type,
                    'file_name' => $record->file_name,
                    'size' => $record->size,
                ]);

                return $record;
            });
        } catch (\Throwable $e) {
            // 落库失败清理孤儿文件
            Storage::disk(self::DISK)->delete($path);
            throw $e;
        }

        return $this->toArray($record);
    }

    public function download(MatchFile $file): StreamedResponse
    {
        return Storage::disk(self::DISK)->download($file->file_path, $file->file_name);
    }

    public function delete(MatchFile $file, int $userId): void
    {
        DB::transaction(function () use ($file, $userId) {
            $file->delete();

            Log::channel('business')->info('match file deleted', [
                'file_id' => $file->id,
                'match_id' => $file->match_id,
                'operator' => $userId,
                'file_name' => $file->file_name,
            ]);
        });

        // 物理文件删除失败不回滚，仅记 exception 日志
        try {
            Storage::disk(self::DISK)->delete($file->file_path);
        } catch (\Throwable $e) {
            Log::channel('exception')->error('match file physical delete failed', [
                'file_id' => $file->id,
                'path' => $file->file_path,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function toArray(MatchFile $f): array
    {
        return [
            'id' => $f->id,
            'type' => $f->type->value,
            'type_label' => $f->type->label(),
            'file_name' => $f->file_name,
            'mime' => $f->mime,
            'size' => $f->size,
            'created_at' => $f->created_at?->toDateTimeString(),
        ];
    }
}
