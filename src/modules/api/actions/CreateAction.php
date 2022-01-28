<?php
    namespace unique\yii2vue\modules\api\components;

    use unique\yii2vue\modules\api\interfaces\WithListChangesInterface;
    use Yii;
    use yii\helpers\Url;
    use yii\rest\Serializer;
    use yii\web\ServerErrorHttpException;

    class CreateAction extends \yii\rest\CreateAction {

        public function run() {

            if ( $this->checkAccess ) {
                call_user_func( $this->checkAccess, $this->id );
            }

            /* @var $model \yii\db\ActiveRecord */
            $model = new $this->modelClass( [
                'scenario' => $this->scenario,
            ] );

            $model->load( Yii::$app->getRequest()->getBodyParams(), '' );
            if ( $res = $model->save() ) {

                $expand = \Yii::$app->request->get( '_expand', [] );

                if ( $res instanceof ListChanges && $this->controller instanceof WithListChangesInterface ) {

                    $this->controller->setListChanges( $res );
                }

                return $model->toArray( [], $expand );
            } elseif ( !$model->hasErrors() ) {

                // We ignore relation errors
                // @todo we should either make sure it's a relation problem or find a better way to handle it?..
                $model->addError( 'id', 'Unable To Save Data' );
            }

            return $model;
        }
    }