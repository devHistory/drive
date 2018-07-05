<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Sabre\DAV;
use App;
use PDO;

class WebDavController extends Controller
{

    public function index()
    {
        $realm = 'SabreDAV';

        if (!isset($_SERVER['PHP_AUTH_DIGEST'])) {
            // TODO :: 写自己的数据库权限认证
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Digest realm="' . $realm .
                '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) . '"');
            die('login failed');
        }

        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 基本设置
        // $rootDirectory = new DAV\FS\Directory($dataHome);
        $dataHome = env('DATA_HOME', storage_path('app/data/' . $this->uid . '/files'));
        $rootDirectory = new App\Lib\DAV\Collection($dataHome);
        $server = new DAV\Server($rootDirectory);
        $server->setBaseUri('/webdav');

        // 文件锁插件
        $lockBackend = new DAV\Locks\Backend\File('/tmp/locks');
        $lockPlugin = new DAV\Locks\Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // 浏览器插件
        $server->addPlugin(new DAV\Browser\Plugin());

        // 认证插件
        $authBackend = new DAV\Auth\Backend\PDO($pdo);
        $authBackend->setRealm($realm);
        $authPlugin = new DAV\Auth\Plugin($authBackend);
        $server->addPlugin($authPlugin);

        // mimeType插件
        $server->addPlugin(new DAV\Browser\GuessContentType());

        $server->exec();
    }

}
