<?php

namespace App\Http\Controllers\Internal;

use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MenuItemController
{
    /**
     * GET /internal/admin/menus
     * Returns the full menu tree (top-level items with nested children), sorted by sort_order.
     */
    public function index(): JsonResponse
    {
        $tree = MenuItem::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with('children')
            ->get();

        return $this->success($tree);
    }

    /**
     * POST /internal/admin/menus
     */
    public function store(Request $request): JsonResponse
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $menuItem = MenuItem::create($validator->validated());

        return $this->success($menuItem, 201);
    }

    /**
     * PUT /internal/admin/menus/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $menuItem = MenuItem::find($id);

        if (! $menuItem) {
            return $this->notFound();
        }

        $validator = $this->validator($request->all(), partial: true);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $menuItem->update($validator->validated());

        return $this->success($menuItem);
    }

    /**
     * DELETE /internal/admin/menus/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $menuItem = MenuItem::find($id);

        if (! $menuItem) {
            return $this->notFound();
        }

        if ($menuItem->children()->exists()) {
            return new JsonResponse([
                'code' => 'shop-4011',
                'message' => 'Cannot delete: menu item has children',
                'data' => null,
            ], 409);
        }

        $menuItem->delete();

        return $this->success(null);
    }

    private function validator(array $data, bool $partial = false): \Illuminate\Contracts\Validation\Validator
    {
        $requiredRule = $partial ? 'sometimes|required' : 'required';

        return Validator::make($data, [
            'label' => "{$requiredRule}|string|max:100",
            'path' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'parent_id' => ['nullable', 'integer', Rule::exists('menu_items', 'id')],
            'permission_key' => 'nullable|string|max:100',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);
    }

    private function validationError(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        return new JsonResponse([
            'code' => 'shop-4010',
            'message' => $validator->errors()->first(),
            'data' => null,
        ], 400);
    }

    private function notFound(): JsonResponse
    {
        return new JsonResponse([
            'code' => 'shop-4012',
            'message' => 'Menu item not found',
            'data' => null,
        ], 404);
    }

    private function success(mixed $data, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'code' => 'OK',
            'message' => 'success',
            'data' => $data,
        ], $status);
    }
}
