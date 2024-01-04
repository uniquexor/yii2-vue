<?php
    namespace unique\yii2vue\modules\api\actions;

    use unique\yii2vue\modules\api\interfaces\SearchQueryInterface;
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

        /**
         * A callable, that will receive and must return a configuration array for the DataProvider.
         * ```
         * function ( array $options );
         * ```
         * @var array|callable
         */
        public $provider_options_callback;

        public bool $is_multisort_enabled = false;

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

                $query = null;
                $modelClass = $this->modelClass;
                if ( $this->dataFilter && $this->dataFilter->searchModel ) {

                    $model = new $this->dataFilter->searchModel( [ 'scenario' => 'search' ] );
                    $model->load( $requestParams, 'filter' );

                    if ( !$model->validate() ) {

                        $this->dataFilter->addErrors( $model->getErrors() );

                        return $this->dataFilter;
                    }

                    if ( $model instanceof SearchQueryInterface ) {

                        $query = $model->getSearchQuery();
                    }
                }

                /**
                 * @var ActiveQuery $query
                 */
                if ( $query === null ) {

                    $query = $modelClass::find();
                    if ( !empty( $filter ) ) {

                        $query->andWhere( $filter );
                    }
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
                        'enableMultiSort' => $this->is_multisort_enabled,
                    ],
                ];

                if ( (int) ( $requestParams['pageSize'] ?? -1 ) === 0 ) {

                    $options['pagination'] = false;
                }

                if ( $this->provider_options_callback ) {

                    $options = call_user_func( $this->provider_options_callback, $options );
                }

                return \Yii::createObject( $options );
            };
        }
    }