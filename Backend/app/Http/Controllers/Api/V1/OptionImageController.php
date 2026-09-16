<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\QuizException;
use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Controllers\Api\HandlesQuizApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOptionImageRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OptionImageController extends Controller
{
    use ApiResponseTrait, HandlesQuizApiErrors;

    public function __construct(
        protected AuditLogService $auditLogService,
    ) {}

    public function store(StoreOptionImageRequest $request): JsonResponse
    {
        try {
            /** @var User $admin */
            $admin = $request->user();
            $file = $request->file('image');
            if (! $file instanceof UploadedFile) {
                throw new QuizException('An image file is required', 422);
            }
            $path = $file->store('option-images', 'public');
            $url = Storage::disk('public')->url($path);

            $this->auditLogService->log([
                'action' => config('constants.OPTION_IMAGE_UPLOAD'),
                'entity' => 'question_option_image',
                'entity_id' => null,
                'change_log' => [
                    'uploaded_by' => $admin->id,
                    'path' => $path,
                ],
            ]);

            return $this->sendResponse([
                'url' => $url,
                'path' => $path,
            ], 'Image uploaded');
        } catch (Throwable $exception) {
            return $this->quizErrorResponse($exception, 'Failed to upload image');
        }
    }
}
