<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTicketRequest;
use App\Http\Requests\Api\StoreTicketResponseRequest;
use App\Http\Requests\Api\UpdateTicketRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $tickets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->tickets->paginateForUser($request->user());

        return ApiResource::success([
            'data' => TicketResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->tickets->store(
            $request->user(),
            $request->safe()->only(['title', 'message', 'priority']),
            $request->file('attachment')
        );

        return ApiResource::success(
            (new TicketResource($ticket->load('user:id,name,email')))->resolve(),
            'پیام شما با موفقیت ارسال شد.'
        );
    }

    public function show(Request $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->show($request->user(), $ticket);

        return new TicketResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket = $this->tickets->update(
            $request->user(),
            $ticket,
            $request->safe()->only(['title', 'message', 'priority']),
            $request->file('attachment')
        );

        return ApiResource::success(
            (new TicketResource($ticket))->resolve(),
            'تیکت شما با موفقیت بروزرسانی شد.'
        );
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->tickets->delete($request->user(), $ticket);

        return ApiResource::success(null, 'تیکت حذف شد.');
    }

    public function storeResponse(StoreTicketResponseRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket = $this->tickets->respond(
            $request->user(),
            $ticket,
            $request->string('message')->toString(),
            $request->file('attachment')
        );

        return ApiResource::success(
            (new TicketResource($ticket))->resolve()
        );
    }
}
