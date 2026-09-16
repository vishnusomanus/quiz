<?php

namespace App\Http\Controllers\Api\V1\Swagger;

/**
 * @OA\Tag(
 *     name="Uploads",
 *     description="Admin media uploads for quiz option images",
 * )
 */
class UploadSwagger
{
    /**
     * @OA\Post(
     *     path="/v1/uploads/option-images",
     *     tags={"Uploads"},
     *     summary="Upload an option image",
     *     description="Admin-only. Stores a jpeg, png, gif, or webp (max 2 MB) and returns a public URL to attach to a question option.",
     *     security={{"passport":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"image"},
     *
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Image uploaded",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Image uploaded"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="url", type="string", format="uri", example="http://localhost:8000/storage/option-images/flag.png"),
     *                 @OA\Property(property="path", type="string", example="option-images/flag.png")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store() {}
}
