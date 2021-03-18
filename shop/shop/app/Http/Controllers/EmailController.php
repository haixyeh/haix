<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
// use App\Mail\Forgot;
 
class EmailController extends Controller
{
    public function send($userMail, $title, $content, $user = '')
    { 
        $data = array(
            'userMail' => $userMail,
            'title' => $title,
            'content' => $content,
            'user' => $user,
        );
        Mail::send([], [], function($message) use ($data) {
            $message->to($data['userMail'], $data['user'])
                    ->subject($data['title'])
                    ->setBody($data['content']);
        });
    }
}