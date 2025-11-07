<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Validator;


class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 5);
            $perPage = min(max($perPage, 1), 100);

            /** @var \App\Models\User $user */
            $user = $request->user();

            $query = $user->contacts()->orderBy('name');

            $q = trim((string) $request->query('q', ''));
            if ($q !== '') {
                $digits = preg_replace('/\D+/', '', $q);
                $query->where(function ($sub) use ($q, $digits) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                    if ($digits !== '')
                        $sub->orWhere('phone', 'like', "%{$digits}%");
                });
            }

            $paginator = $query->paginate($perPage)->appends($request->query());

            return response()->json([
                'success' => true,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar contatos',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $request->only(['name', 'phone', 'email']);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('contacts', 'public');
                $data['photo'] = $photoPath;
            }

            $contact = $request->user()->contacts()->create($data);

            $contact->photo_url = $contact->photo ? Storage::url($contact->photo) : null;

            return response()->json([
                'success' => true,
                'message' => 'Contato criado com sucesso!',
                'data' => $contact,
            ], Response::HTTP_CREATED, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar contato'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        try {
            $contact = $request->user()->contacts()->find($id);

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contato não encontrado',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar contato'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contact = $request->user()->contacts()->find($id);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contato não encontrado',
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:100',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $request->only(['name', 'phone', 'email']);

            if ($request->hasFile('photo')) {
                if ($contact->photo) {
                    Storage::disk('public')->delete($contact->photo);
                }

                $photoPath = $request->file('photo')->store('contacts', 'public');
                $data['photo'] = $photoPath;
            }

            $contact->update($data);
            $contact->refresh();

            return response()->json(
                ['success' => true,
                'message' => 'Contato atualizado com sucesso',
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar contato'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $contact = $request->user()->contacts()->find($id);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contato não encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($contact->photo) {
                Storage::disk('public')->delete($contact->photo);
            }

            $contact->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contato deletado com sucesso.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar contato'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
