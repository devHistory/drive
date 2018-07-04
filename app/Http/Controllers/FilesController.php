<?php

namespace App\Http\Controllers;

use App\Files;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\ImageManagerStatic as Image;

class FilesController extends Controller
{

    private $sizeFormat = [
        'thumb'  => [64, 64],
        'crop'   => [200, 150],
        'small'  => [400, 300],
        'medium' => [800, 600],
        'large'  => [1200, 900],
    ];

    public function preview(Request $request, Response $response)
    {
        $fileId = $request->input('id');
        $resize = $request->input('resize', 'thumb');

        // get size
        if (!isset($this->sizeFormat[$resize])) {
            return $this->getDefaultImage();
        }
        $r = $this->sizeFormat[$resize];


        // 生成缓存图片
        $dirPrefix = substr(md5($fileId), 0, 2);
        $cacheDir = env('DATA_HOME', storage_path('app/cache/' . $this->uid . '/' . $dirPrefix));
        $cachePath = $cacheDir . '/' . $fileId . '-' . $r['0'] . '-' . $r['1'] . '.jpg';
        $image = null;
        if (!file_exists($cachePath)) {
            $dataHome = env('DATA_HOME', storage_path('app/data/' . $this->uid . '/files'));
            $file = Files::findOrFail($fileId);
            if (in_array($resize, ['thumb', 'crop'])) {
                $image = Image::make($dataHome . '/' . $file->path)->fit($r['0'], $r['1'], function ($constraint) {
                    $constraint->upsize();
                });
            }
            else {
                $image = Image::make($dataHome . '/' . $file->path)->resize($r['0'], $r['1'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 755, true);
            }
            $image->save($cachePath, 90);
        }


        // return 304
        $lastModified = filemtime($cachePath);
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }
        // TODO :: eTag
        $eTag = $this->getETag($fileId);
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ($_SERVER['HTTP_IF_NONE_MATCH'] == $eTag) {
                header("HTTP/1.1 304 Not Modified");
                exit;
            }
        }

        if (!$image) {
            $image = Image::make($cachePath);
        }

        // output
        $headers = [
            'Cache-Control' => 'private, max-age=10800, pre-check=10800',
            'Last-Modified' => gmdate("D, d M Y H:i:s", $lastModified) . " GMT",
            'ETag'          => $eTag,
            'Expires'       => gmdate("D, d M Y H:i:s", time() + 86400) . " GMT",
            'Date'          => gmdate("D, d M Y H:i:s", time() + 86400) . " GMT",
            'Content-type'  => 'image/jpeg',
        ];
        $response->withHeaders($headers);
        $response->setContent($image->encode('jpg'));
        $response->send();
    }


    private function getETag($fileId)
    {
        return md5($fileId);
    }


    private function getDefaultImage()
    {
    }


}
