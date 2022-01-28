<?php
    namespace unique\yii2vue\modules\api\actions;

    class DeleteAction extends \yii\rest\DeleteAction {

        public function run( $id ) {

            parent::run( $id );

            \Yii::$app->getResponse()->setStatusCode( 200 );

            return [
                'success' => true,
                'data' => []
            ];
        }
    }