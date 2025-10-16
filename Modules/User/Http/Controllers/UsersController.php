<?php

namespace Modules\User\Http\Controllers;

use Modules\User\DataTables\UsersDataTable;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
      
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Upload\Entities\Upload;

class UsersController extends Controller
{
    public function index(UsersDataTable $dataTable) {
        abort_if(Gate::denies('access_user_management'), 403);

        return $dataTable->render('user::users.index');

    }



    public function create() {
        abort_if(Gate::denies('access_user_management'), 403);
        $departments = \Modules\Department\Entities\Departments::all();
        $categories = \Modules\Product\Entities\Category::all(); 
        
        return view('user::users.create', compact('categories', 'departments'));
    }

     public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $folder = uniqid() . '-' . now()->timestamp;
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();

            // simpan sementara di storage/app/public/temp
            $file->storeAs('public/temp/' . $folder, $filename);

            // catat di DB Uploads
            Upload::create([
                'folder' => $folder,
                'filename' => $filename,
            ]);

            return response()->json(['id' => $folder]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }

    public function store(Request $request) {
        abort_if(Gate::denies('access_user_management'), 403);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255|confirmed',
            'department_name'=> 'nullable|exists:departments',
            'categories'   => 'nullable|array',
            'categories.*' => 'nullable|integer|exists:categories,id',
            'signature'    => 'nullable|image|mimes:jpg,jpeg,png|max:500',



        ]);

        $user = User::create([
            'department_id'=> $request->department_id,
            'name'     => $request->name,
            'email'    => $request->email,

            'password' => Hash::make($request->password),
            'is_active' => $request->is_active,

        ]);
        // simpan relasi

        $user->assignRole($request->role);

        if ($request->hasFile('signature')) {
            $path = $request->file('signature')->store('signatures', 'public');
            $user->signature = $path;
            $user->save();
             }   
        // Simpan kategori
        if ($request->categories) {
            $user->categories()->sync($request->categories);
            }
        if ($request->has('image')) {
            $tempFile = Upload::where('folder', $request->image)->first();

            if ($tempFile) {
                $user->addMedia(Storage::path('public/temp/' . $request->image . '/' . $tempFile->filename))->toMediaCollection('avatars');

                Storage::deleteDirectory('public/temp/' . $request->image);
                $tempFile->delete();
            }
        }

        toast("User Created & Assigned '$request->role' Role!", 'success');

        return redirect()->route('users.index');
    }


    public function edit(User $user) {
        abort_if(Gate::denies('access_user_management'), 403);

        $departments = \Modules\Department\Entities\Departments::all();
        $categories = \Modules\Product\Entities\Category::all(); 
        $userCategories = $user->categories->pluck('id')->toArray();

        return view('user::users.edit', compact('user', 'departments', 'categories', 'userCategories'));
    }

    public function update(Request $request, User $user) {
        abort_if(Gate::denies('access_user_management'), 403);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,'.$user->id,
            'department_name'=> 'nullable|exists:departments',
            'categories'   => 'nullable|array',
            'categories.*' => 'nullable|integer|exists:categories,id',
            'signature'    => 'nullable|image|mimes:jpg,jpeg,png|max:500',
        ]);

        $user->update([
            'department_id'=> $request->department_id,
            'name'     => $request->name,
            'email'    => $request->email,
            
            'is_active' => $request->is_active,
        ]);

         // sinkronisasi kategori ke pivot (jika tidak ada -> detach semua)
        $user->categories()->sync($request->categories ?? []);


        $user->syncRoles($request->role);

        if ($request->has('image')) {
            $tempFile = Upload::where('folder', $request->image)->first();

            if ($tempFile) {
                $user->addMedia(Storage::path('public/temp/' . $request->image . '/' . $tempFile->filename))->toMediaCollection('avatars');

                Storage::deleteDirectory('public/temp/' . $request->image);
                $tempFile->delete();
            }
        }

        toast("User Updated & Assigned '$request->role' Role!", 'info');

        return redirect()->route('users.index');
    }


    public function destroy(User $user) {
        abort_if(Gate::denies('access_user_management'), 403);

        $user->delete();

        toast('User Deleted!', 'warning');

        return redirect()->route('users.index');
    }
}
