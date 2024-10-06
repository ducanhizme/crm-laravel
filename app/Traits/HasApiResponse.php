<?php

namespace App\Traits;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

trait HasApiResponse
{
    private ?array $defaultSuccessData = ['success' => true];

    public function responseNotFound(
        string|Exception $message,
        ?string          $key = 'error'
    ): JsonResponse
    {
        return $this->apiResponse(
            data: [
                $key => $this->morphMessage($message)
            ],
            code: Response::HTTP_NOT_FOUND,
        );
    }

    function respondWithSuccess(
        array|Arrayable|JsonSerializable|null $contents = null,
        string $message = "Success",
    ):JsonResponse
    {
        $contents = $this->morphToArray($contents)??[];
        $data = [] === $contents
            ? $this->defaultSuccessData
            : $contents;
        return $this->apiResponse(data: $data,code: Response::HTTP_OK,message: $message);
    }

    public function respondOk (string $message): JsonResponse
    {
        return $this->respondWithSuccess(['success'=> $message]);
    }

    public function respondUnAuthenticated(?string $message = null): JsonResponse
    {
        return $this->apiResponse(
            data: ['error' => $message ?? 'Unauthenticated'],
            code: Response::HTTP_UNAUTHORIZED
        );
    }

    public function respondForbidden(?string $message = null): JsonResponse
    {
        return $this->apiResponse(
            data: ['error' => $message ?? 'Forbidden'],
            code: Response::HTTP_FORBIDDEN
        );
    }

    public function respondError(?string $message = 'Error'): JsonResponse
    {
        return $this->apiResponse(
            data: ['error' => $message ?? "Error"],
            code: Response::HTTP_BAD_REQUEST,message: "Error"
        );
    }

    public function respondCreated(
        array|Arrayable|JsonSerializable|null $data = null,
        string $message = 'Created'
    ): JsonResponse {
        $data ??= [];

        return $this->apiResponse(
            data: $this->morphToArray(data: $data),
            code: Response::HTTP_CREATED,message: $message
        );
    }

    public function respondFailedValidation(
        string|Exception $message,
        ?string $key = 'failed_at'
    ): JsonResponse {
        return $this->apiResponse(
            data: [$key => $this->morphMessage($message)],
            code: Response::HTTP_UNPROCESSABLE_ENTITY,message: "Validation failed"
        );
    }

    public function respondNoContent(
        array|Arrayable|JsonSerializable|null $data = null
    ): JsonResponse {
        $data ??= [];
        $data = $this->morphToArray(data: $data);

        return $this->apiResponse(
            data: $data,
            code: Response::HTTP_NO_CONTENT,message: "No content"
        );
    }


    private function apiResponse(array $data, int $code = 200,string $message =""): JsonResponse
    {
        return response()->json(data: array_merge($data,["message"=>$message]), status: $code);
    }

    private function morphToArray(array|Arrayable|JsonSerializable|null $data): ?array
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if ($data instanceof JsonSerializable) {
            return $data->jsonSerialize();
        }

        return $data;
    }

    private function morphMessage(string|Exception $message): string
    {
        return $message instanceof Exception
            ? $message->getMessage()
            : $message;
    }
}
