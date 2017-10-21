<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
class TestController extends Controller
{

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
  
        $data = $this->curl_post("http://www.yi.com/index.php?r=biz/ajax-login", array('username'=>'驰程', 'password'=>'123456','remember'=>0));  
         
        // $data = $this->file_get_contents_post("http://www.yi.com/index.php?r=biz/ajax-login", array('username'=>'驰程', 'password'=>'123456','remember'=>0)); 
        var_dump($data);  
    }

    public function curl_post($url, $post) {  
        $options = array(  
            CURLOPT_RETURNTRANSFER => true,  
            CURLOPT_HEADER         => false,  
            CURLOPT_POST           => true,  
            CURLOPT_POSTFIELDS     => $post,  
        );  
      
        $ch = curl_init($url);  
        curl_setopt_array($ch, $options);  
        $result = curl_exec($ch);  
        curl_close($ch);  
        return $result;  
    }


    public function file_get_contents_post($url, $post) {  
        $options = array(  
            'http' => array(  
                'method' => 'POST',  
                // 'content' => 'name=caiknife&email=caiknife@gmail.com',  
                'content' => http_build_query($post),  
                'header' => 'Content-Type: application/x-www-form-urlencoded',  
            ),  
        );  
        // header('Content-Type: application/x-www-form-urlencoded');
        $result = file_get_contents($url, false, stream_context_create($options));  
      
        return $result;  
    }  

}
