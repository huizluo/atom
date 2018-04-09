<?php
namespace app\controller;

use \system\core\Controller;
use system\library\log\Log;

class IndexController extends Controller{
    public function index(){
        Log::instance()->write('say hi','error');
        return 'hi';
    }
}