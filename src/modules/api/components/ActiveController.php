<?php
    namespace unique\yii2vue\modules\api\components;

    use unique\yii2vue\modules\api\interfaces\WithListChangesInterface;
    use yii\data\ActiveDataFilter;

    /**
     * Class ActiveController
     * @method actionView( int $id ) Returns specified entity.
     * @method actionIndex() Returns a list of entities.
     * @method actionCreate() Creates a new entity.
     * @method actionUpdate( int $id ) Updates a specified entity.
     * @method actionDelete( int $id ) Deletes an entity.
     */
    class ActiveController extends \yii\rest\ActiveController implements WithListChangesInterface {

        public $search_model;

        protected $list_changes;

        /**
         * @inheritdoc
         */
        public function afterAction( $action, $result ) {

            $result = parent::afterAction( $action, $result );
            if ( is_array( $result ) && $this->list_changes instanceof ListChanges ) {

                $result['_list_changes_'] = $this->list_changes->getListChanges();
            }

            return $result;
        }

        /**
         * @inheritdoc
         */
        public function actions() {

            $actions = parent::actions();
            $actions['delete']['class'] = DeleteAction::class;
            $actions['create']['class'] = CreateAction::class;
            $actions['update']['class'] = UpdateAction::class;
            $actions['view']['class'] = ViewAction::class;
            $actions['index']['class'] = IndexAction::class;

            if ( $this->search_model ) {

                $model = new $this->search_model( [ 'scenario' => 'search' ] );
                $actions['index']['dataFilter'] = [ 'class' => ActiveDataFilter::class, 'searchModel' => $model ];
            }

            return array_filter( $actions, function ( $action ) {

                return !$this->hasMethod( 'action' . ucfirst( $action ) );
            }, ARRAY_FILTER_USE_KEY );
        }

        /**
         * @inheritdoc
         */
        public function setListChanges( ListChanges $list_changes ) {

            $this->list_changes = $list_changes;
        }

        /**
         * @inheritdoc
         */
        public function getListChanges(): ListChanges {

            return $this->list_changes;
        }
    }