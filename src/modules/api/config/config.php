<?php
    /**
     * @var \unique\yii2vue\modules\api\Module $this
     */

    use yii\web\Response;

    return [
        'components' => [
            'response' => [
                'class' => \yii\web\Response::class,
                'format' => \yii\web\Response::FORMAT_JSON,
                'on beforeSend' => function ($event) {

                    /**
                     * @var Response $response
                     */
                    $response = $event->sender;
                    if ( $response->data !== null && ( !isset( $response->data['success'] ) || !isset( $response->data['data'] ) ) ) {

                        $response->data = [
                            'success' => $response->isSuccessful,
                            'data' => $response->data,
                        ];
                        $response->statusCode = 200;
                    }
                },
            ],
        ],
    ];