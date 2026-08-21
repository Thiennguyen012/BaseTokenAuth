<?php

namespace App\Http\Controllers\Api\CustomerContact;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerContact\StoreCustomerContactRequest;
use App\Http\Requests\CustomerContact\UpdateCustomerContactRequest;
use App\Http\Resources\CustomerContactResource;
use App\Services\CustomerContact\CustomerContactService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class CustomerContactController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected CustomerContactService $service) {}

    /**
     * @OA\Post(
     *     path="/api/customer-contacts", summary="Gửi yêu cầu tư vấn", tags={"Public"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreCustomerContactRequest")),
     *     @OA\Response(response=201, description="Đã tiếp nhận yêu cầu"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=429, description="Gửi quá nhiều yêu cầu")
     * )
     */
    public function publicStore(StoreCustomerContactRequest $request): JsonResponse
    {
        $contact = $this->service->create($request->validated());

        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'Đã tiếp nhận yêu cầu tư vấn.',
            'data' => new CustomerContactResource($contact),
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/admin/api/customer-contacts", summary="Danh sách khách hàng liên hệ", tags={"Customer Contacts"}, security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công"), @OA\Response(response=401, description="Chưa xác thực")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('per_page', Helpers::LIMIT_PER_PAGE), Helpers::LIMIT_PER_PAGE));
        $categoryId = $request->filled('category_id') ? (int) $request->query('category_id') : null;
        $contacts = $this->service->paginate($limit, (string) $request->query('search', ''), $categoryId);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy danh sách khách hàng liên hệ thành công.',
            'data' => CustomerContactResource::collection($contacts->getCollection()),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    /** @OA\Post(path="/admin/api/customer-contacts", summary="Tạo khách hàng liên hệ", tags={"Customer Contacts"}, security={{"sanctum":{}}}, @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreCustomerContactRequest")), @OA\Response(response=201, description="Đã tạo")) */
    public function store(StoreCustomerContactRequest $request): JsonResponse
    {
        $contact = $this->service->create($request->validated());

        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'Tạo khách hàng liên hệ thành công.',
            'data' => new CustomerContactResource($contact),
        ], Response::HTTP_CREATED);
    }

    /** @OA\Get(path="/admin/api/customer-contacts/{id}", summary="Chi tiết khách hàng liên hệ", tags={"Customer Contacts"}, security={{"sanctum":{}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Thành công"), @OA\Response(response=404, description="Không tồn tại")) */
    public function show(int $id): JsonResponse
    {
        $contact = $this->service->find($id);
        if (!$contact) return $this->errorResponse('Không tìm thấy khách hàng liên hệ.', Response::HTTP_NOT_FOUND);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy thông tin khách hàng liên hệ thành công.',
            'data' => new CustomerContactResource($contact),
        ]);
    }

    /** @OA\Patch(path="/admin/api/customer-contacts/{id}", summary="Cập nhật khách hàng liên hệ", tags={"Customer Contacts"}, security={{"sanctum":{}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreCustomerContactRequest")), @OA\Response(response=200, description="Đã cập nhật"), @OA\Response(response=404, description="Không tồn tại")) */
    public function update(UpdateCustomerContactRequest $request, int $id): JsonResponse
    {
        $contact = $this->service->find($id);
        if (!$contact) return $this->errorResponse('Không tìm thấy khách hàng liên hệ.', Response::HTTP_NOT_FOUND);
        $contact = $this->service->update($contact, $request->validated());

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Cập nhật khách hàng liên hệ thành công.',
            'data' => new CustomerContactResource($contact),
        ]);
    }

    /** @OA\Delete(path="/admin/api/customer-contacts/{id}", summary="Xóa khách hàng liên hệ", tags={"Customer Contacts"}, security={{"sanctum":{}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Đã xóa"), @OA\Response(response=404, description="Không tồn tại")) */
    public function destroy(int $id): JsonResponse
    {
        $contact = $this->service->find($id);
        if (!$contact) return $this->errorResponse('Không tìm thấy khách hàng liên hệ.', Response::HTTP_NOT_FOUND);
        $this->service->delete($contact);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Xóa khách hàng liên hệ thành công.',
        ]);
    }
}
