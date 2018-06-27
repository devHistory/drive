<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Sabre\DAV;
use Sabre\DAV\Auth;
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
        $dataHome = env('DATA_HOME', storage_path('app'));
        $rootDirectory = new DAV\FS\Directory($dataHome);
        $server = new DAV\Server($rootDirectory);
        $server->setBaseUri('/webdav');

        // 文件锁
        $lockBackend = new DAV\Locks\Backend\File('/tmp/locks');
        $lockPlugin = new DAV\Locks\Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // 浏览器插件
        $server->addPlugin(new DAV\Browser\Plugin());

        // 认证插件
        $authBackend = new Auth\Backend\PDO($pdo);
        $authBackend->setRealm($realm);
        $authPlugin = new Auth\Plugin($authBackend);
        $server->addPlugin($authPlugin);

        $server->exec();
    }

}
