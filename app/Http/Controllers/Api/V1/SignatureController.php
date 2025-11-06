<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Signature\CreateSignatureAction;
use App\Actions\Signature\DeleteSignatureAction;
use App\Actions\Signature\GetSignatureAction;
use App\Actions\Signature\ListSignaturesAction;
use App\Actions\Signature\UpdateSignatureAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Signature\CreateSignatureRequest;
use App\Http\Requests\Signature\DeleteSignatureRequest;
use App\Http\Requests\Signature\ListSignaturesRequest;
use App\Http\Requests\Signature\UpdateSignatureRequest;
use App\Http\Resources\SignatureResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SignatureController extends Controller
{
    /**
     * List all signatures for authenticated user
     */
    public function index(
        ListSignaturesRequest $request,
        ListSignaturesAction $action
    ): AnonymousResourceCollection {
        /** @var array{user_id: string, per_page?: int, search?: string} $validated */
        $validated = $request->validated();
        $signatures = $action->execute($validated);

        return SignatureResource::collection($signatures);
    }

    /**
     * Get a specific signature
     */
    public function show(
        string $id,
        DeleteSignatureRequest $request,
        GetSignatureAction $action
    ): SignatureResource {
        /** @var array{user_id: string} $validated */
        $validated = $request->validated();
        $validated['signature_id'] = $id;

        $signature = $action->execute($validated);

        return new SignatureResource($signature);
    }

    /**
     * Create a new signature
     */
    public function store(
        CreateSignatureRequest $request,
        CreateSignatureAction $action
    ): SignatureResource {
        /** @var array{user_id: string, name: string, description?: string, image: \Illuminate\Http\UploadedFile} $validated */
        $validated = $request->validated();
        $signature = $action->execute($validated);

        return new SignatureResource($signature);
    }

    /**
     * Update an existing signature
     */
    public function update(
        string $id,
        UpdateSignatureRequest $request,
        UpdateSignatureAction $action
    ): SignatureResource {
        /** @var array{user_id: string, name?: string, description?: string, image?: \Illuminate\Http\UploadedFile} $validated */
        $validated = $request->validated();
        $validated['signature_id'] = $id;

        $signature = $action->execute($validated);

        return new SignatureResource($signature);
    }

    /**
     * Delete a signature (soft delete)
     */
    public function destroy(
        string $id,
        DeleteSignatureRequest $request,
        DeleteSignatureAction $action
    ): JsonResponse {
        /** @var array{user_id: string} $validated */
        $validated = $request->validated();
        $validated['signature_id'] = $id;

        $result = $action->execute($validated);

        return response()->json($result);
    }
}
