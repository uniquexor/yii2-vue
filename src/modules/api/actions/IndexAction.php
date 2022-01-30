<?php
    namespace unique\yii2vue\modules\api\actions;

    use yii\data\ActiveDataProvider;
    use yii\db\ActiveQuery;
    use yii\db\BaseActiveRecord;

    class IndexAction extends \yii\rest\IndexAction {

        /**
         * A callable, that will receive generated ActiveQuery and DataFilter parameters.
         * ```
         * function ( ActiveQuery $query, DataFilter $filter );
         * ```
         * @var array|callable
         */
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

                $options = [
                    'class' => ActiveDataProvider::class,
                    'query' => $query,
                    'pagination' => [
                        'params' => $requestParams,
                    ],
                    'sort' => [
                        'params' => $requestParams,
                    ],
                ];

                if ( (int) ( $requestParams['pageSize'] ?? -1 ) === 0 ) {

                    $options['pagination'] = false;
                }

                return \Yii::createObject( $options );
            };
        }
    }