<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $data = User::with(['company', 'branch', 'division', 'department', 'section'])
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $this->normalizePhone($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|min:8',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:super_admin,admin,inspector,viewer,supervisor,she_section_head,user_dept_head',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
            'department_id' => 'nullable|exists:departments,id',
            'section_id' => 'nullable|exists:sections,id',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);
        $this->storeSignature($request, $data);

        $user = User::create($data);

        return response()->json(['data' => $user], 201);
    }

    public function show($id)
    {
        $user = User::with(['company', 'branch', 'division', 'department', 'section'])->findOrFail($id);
        return response()->json(['data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->normalizePhone($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:super_admin,admin,inspector,viewer,supervisor,she_section_head,user_dept_head',
            'password' => 'nullable|min:8',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
            'department_id' => 'nullable|exists:departments,id',
            'section_id' => 'nullable|exists:sections,id',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['password']) && $data['password']) {
            $data['password_hash'] = Hash::make($data['password']);
        }
        unset($data['password']);
        $this->storeSignature($request, $data);

        $user->update($data);

        return response()->json(['data' => $user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function storeSignature(Request $request, array &$data): void
    {
        unset($data['signature']);

        if (!$request->hasFile('signature')) {
            return;
        }

        $file = $request->file('signature');
        if (!$file->isValid()) {
            return;
        }

        $targetPath = public_path('images');
        File::ensureDirectoryExists($targetPath);

        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = 'user_signature_' . now()->format('YmdHisv') . '_' . uniqid() . '.' . $extension;
        $file->move($targetPath, $filename);

        $data['signature_path'] = 'images/' . $filename;
    }

    private function normalizePhone(Request $request): void
    {
        if (!$request->has('phone')) {
            return;
        }

        $request->merge([
            'phone' => filled($request->phone) ? trim((string) $request->phone) : null,
        ]);
    }
}
