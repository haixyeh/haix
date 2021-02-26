<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        $result = array(
            'status' => 'Y',
            'code' => 12345,
            'message' => $exception->getMessage(),
            'data' => array()
        );
        if ($exception instanceof CustomException) {
            return response()->view('errors.custom', [], 500);
        }
        if ($exception instanceof ModelNotFoundException) {
            return response()->view('errors.404', [], 404);
        }
        if($this->isHttpException($exception))
        {
            $result['code'] = $exception->getStatusCode();
            switch ($exception->getStatusCode()) {
                // not found
                case 404:
                    $result['code'] = $exception->getStatusCode();
                    $result['message'] = '404 error';
                    break;
                // internal error
                case 500:
                    $result['code'] = $exception->getStatusCode();
                    $result['message'] = '500 error'; 
                    break;
                default:
                    break;
            }

        }

        return response()->json($result);
        // return parent::render($request, $exception);
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        $this->setLogRecord($exception);
    }

    protected function setLogRecord(Exception $exception, $ExceptionType = 'Unexpected')
    {

    }
}
