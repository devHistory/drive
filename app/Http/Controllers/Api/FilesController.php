<?php


namespace App\Http\Controllers\Api;

use App\FilesTrash;
use Illuminate\Http\Request;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use App\Files;

class FilesController extends Base
{

    public function __construct()
    {
        parent::__construct();

    }


    public function index(Request $request)
    {
        $path = trim($request->input('path'), '/');
        $dir = Files::where('path_hash', md5($path))
            ->where('uid', $this->uid)
            ->firstOrFail();
        $list = Files::where('parent', $dir->id)
            ->where('status', 0)
            ->get();
        $data = [];
        foreach ($list as $value) {
            $data[] = [
                'id'         => $value->id,
                'path'       => $value->path,
                'name'       => $value->name,
                'type'       => $value->mimetype == -1 ? 'dir' : 'file',
                'mimetype'   => $value->mimetype,
                'size'       => $value->size,
                'file_mtime' => $value->file_mtime,
                'encrypted'  => $value->encrypted,
                'attribute'  => $value->attribute,
                'ctime'      => $value->ctime,
                'mtime'      => $value->mtime,
            ];
        }
        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'payload' => $data
        ]);
    }


    public function store(Request $request)
    {
        $force = $request->get('force', 0);
        $path = trim($request->get('path'), '/');

        if ($_FILES['file']['error'] != 0) {
            return response()->json(['code' => 400, 'message' => 'upload failed']);
        }


        // 检查文件
        $dir = Files::where('path_hash', md5($path))->where('uid', $this->uid)->first();
        if (!$dir) {
            return response()->json(['code' => 400, 'message' => 'invalid directory']);
        }
        $filePath = $path . '/' . $_FILES['file']['name'];
        $exist = Files::where('path_hash', md5($filePath))->where('uid', $this->uid)->first();
        if ($exist && !$force) {
            return response()->json(['code' => 400, 'message' => 'file is exist']);
        }
        if ($exist && $force) {
            Files::destroy($exist->id);
        }


        // TODO :: exif
        // $exif= exif_read_data($_FILES['file']['tmp_name']);


        $storagePath = 'data/' . $this->getUserStoragePath($path);
        Storage::putFileAs($storagePath, new File($_FILES['file']['tmp_name']), $_FILES['file']['name']);

        // 入库 TODO :: mimeType
        $file = new Files;
        $file->path = $filePath;
        $file->path_hash = md5($filePath);
        $file->name = $_FILES['file']['name'];
        $file->parent = $dir->id;
        //$file->mimetype = 0;
        $file->size = $_FILES['file']['size'];
        //$file->file_mtime = filemtime($_FILES['file']['tmp_name']);
        $file->uid = $this->uid;
        $file->ctime = time();
        $file->mtime = time();
        $file->save();

        return response()->json([
            'code'    => 400,
            'message' => 'success'
        ]);
    }


    public function show($id)
    {
    }


    public function update($id, Request $request)
    {
        $allowAction = ['favorite', 'colour', 'move', 'rename'];
        $action = $request->get('action');
        $value = $request->get('value');

        // check
        if (!in_array($action, $allowAction)) {
            return response()->json([
                'code'    => 400,
                'message' => 'failed, action error'
            ]);
        }
        $data = Files::findOrFail($id);
        if ($data->uid != $this->uid) {
            return response()->json([
                'code'    => 400,
                'message' => 'failed'
            ]);
        }

        switch ($action) {

            case 'favorite':
                $attribute = json_decode($data->attribute, true);
                if ($value == 1) {
                    $attribute['favorite'] = 1;
                }
                else {
                    $attribute['favorite'] = 0;
                }
                $data->attribute = json_encode($attribute);
                $data->save();
                break;

            case 'colour':
                $attribute = json_decode($data->attribute, true);
                $attribute['colour'] = $value;
                $data->attribute = json_encode($attribute);
                $data->save();
                break;

            case 'move':
                break;

            case 'rename':
                // TODO :: check filename  移动文件
                $newPath = substr($data->path, 0, -strlen($data->name)) . $value;
                $data->path = $newPath;
                $data->path_hash = md5($newPath);
                $data->name = $value;
                $data->mtime = time();
                $data->save();
                break;

        }

        return response()->json([
            'code'    => 200,
            'message' => 'success'
        ]);
    }


    public function destroy($id)
    {
        $data = Files::findOrFail($id);
        if ($data->status == -1) {
            return response()->json([
                'code'    => 400,
                'message' => 'failed, file is already destroy'
            ]);
        }
        $data->status = -1;
        $data->save();
        $trash = new FilesTrash();
        $trash->name = $data->name;
        $trash->uid = $data->uid;
        $trash->time = time();
        $trash->path = $data->path;
        $trash->mimetype = $data->mimetype;
        $trash->save();

        // 移动文件
        $trashDir = $this->getUserStoragePath(null, true, 'files_trash');
        if (!is_dir($trashDir)) {
            mkdir($trashDir);
        }
        $pathOld = $this->getUserStoragePath($data->path, true, 'files');
        rename($pathOld, $trashDir . '/' . $data->name . time());

        return response()->json([
            'code'    => 200,
            'message' => 'success'
        ]);
    }


    private function getUserStoragePath($path = null, $absolute = false, $folder = 'files')
    {
        if ($absolute) {
            $path = empty($path) ? $this->uid . '/' . $folder : $this->uid . '/' . $folder . '/' . $path;
            return storage_path('app/data') . '/' . $path;
        }
        return empty($path) ? $this->uid . '/' . $folder : $this->uid . '/' . $folder . '/' . $path;
    }

}
