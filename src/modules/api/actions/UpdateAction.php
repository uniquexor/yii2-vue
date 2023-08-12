<?php
    namespace unique\yii2vue\modules\api\actions;

    use unique\yii2vue\modules\api\components\ListChanges;
    use unique\yii2vue\modules\api\interfaces\WithListChangesInterface;
    use yii\db\ActiveRecord;
    use yii\web\ServerErrorHttpException;

    class UpdateAction extends \yii\rest\UpdateAction {

        public function run( $id ) {

            /* @var $model ActiveRecord */
            $model = $this->findModel($id);

            if ($this->checkAccess) {
                call_user_func($this->checkAccess, $this->id, $model);
            }

            $model->scenario = $this->scenario;
            $model->load( \Yii::$app->getRequest()->getBodyParams(), '' );
            if ( ( $res = $model->save() ) === false && !$model->hasErrors() ) {

                throw new ServerErrorHttpException( 'Failed to update the object for unknown reason.' );
            }

            if ( $res instanceof ListChanges && $this->controller instanceof WithListChangesInterface ) {

                $this->controller->setListChanges( $res );
            }

            $expand = \Yii::$app->request->get( '_expand', [] );
            if ( is_string( $expand ) ) {

                $expand = explode( ',', $expand );
                $expand = array_map( 'trim', $expand );
            }

            return $model->toArray( [], $expand );
        }
    }