<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="Quiz API",
 *     version="v1",
 *     description="REST API for listing, attending, submitting, and administering quizzes. Authenticate with a Laravel Passport Bearer token."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="passport",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Paste the Passport access token from POST /v1/auth/login. Swagger UI sends it as Authorization: Bearer {token}."
 * )
 */
class BaseDocumentation {}
