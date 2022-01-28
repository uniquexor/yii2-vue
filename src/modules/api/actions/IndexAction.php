<?php
    namespace unique\yii2vue\modules\api\components;

    use yii\data\ActiveDataProvider;
    use yii\db\ActiveQuery;
    use yii\db\BaseActiveRecord;

    class IndexAction extends \yii\rest\IndexAction {

        public $query_callback;

        public function init() {

            parent::init();

            $this->prepareDataProvider = function ( $action, $filter ) {

                $requestParams = \Yii::$app->getRequest()->getBodyParams();
                if ( empty( $requestParams ) ) {

                    $requestParams = \Yii::$app->getRequest()->getQueryParams();
                }

                /**
                 * @var \yii\db\BaseActiveRecord $modelClass
                 * @var BaseActiveRecord $model
                 */
                $modelClass = $this->modelClass;
                if ( $this->dataFilter && $this->dataFilter->searchModel ) {

                    $model = new $this->dataFilter->searchModel( [ 'scenario' => 'search' ] );
                    $model->load( $requestParams, 'filter' );

                    if ( !$model->validate() ) {

                        $this->dataFilter->addErrors( $model->getErrors() );

                        return $this->dataFilter;
                    }
                }

                /**
                 * @var ActiveQuery $query
                 */
                $query = $modelClass::find();
                if ( !empty( $filter ) ) {

                    $query->andWhere( $filter );
                }

                if ( $this->query_callback ) {

                    call_user_func( $this->query_callback, $query, $filter );
                }

                return \Yii::createObject( [
                    'class' => ActiveDataProvider::class,
                    'query' => $query,
                    'pagination' => [
                        'params' => $requestParams,
                    ],
                    'sort' => [
                        'params' => $requestParams,
                    ],
                ] );
            };
        }
    }