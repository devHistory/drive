<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FilesController extends Controller
{

    public function preview(Request $request, Response $response)
    {
        $dataHome = env('DATA_HOME', storage_path('app'));
        $path = $dataHome . '/' . ltrim($request->input('path'), '/');
        if (!file_exists($path)) {
            throw new \Exception('file is not exist');
        }

        $lastModified = filemtime($path);

        // return 304
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }
        // TODO :: eTag
        $eTag = md5(file_get_contents($path));
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ($_SERVER['HTTP_IF_NONE_MATCH'] == $eTag) {
                header("HTTP/1.1 304 Not Modified");
                exit;
            }
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
        $response->setContent(file_get_contents($path));
        $response->send();
    }

}
