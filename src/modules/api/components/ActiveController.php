<?php
    namespace unique\yii2vue\modules\api\components;

    use unique\yii2vue\modules\api\actions\CreateAction;
    use unique\yii2vue\modules\api\actions\DeleteAction;
    use unique\yii2vue\modules\api\actions\IndexAction;
    use unique\yii2vue\modules\api\actions\UpdateAction;
    use unique\yii2vue\modules\api\actions\ViewAction;
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

        /**
         * A class of the Model that needs to be used to search for data in the index action.
         * Will be set as a {@see ActiveDataFilter::searchModel}
         * @var string
         */
        public $search_model;

        /**
         * Can be set by action if list_changes need to be returned.
         * If set, afterAction will append response json to include _list_changes_ - a serialization of all the changes to each of the models.
         * @var ListChanges
         */
        protected $list_changes;

        /**
         * Public options for each of the action: delete, update, create, index, view.
         * @var array
         */
        protected $action_options = [];

        /**
         * Overwrite default Serializer options, because it is important for us to preserve the keys,
         * since data in the ModelsManager will be indexed by keys.
         *
         * @var array
         */
        public $serializer = [
            'class' => '\yii\rest\Serializer',
            'preserveKeys' => true,
        ];

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

            $actions = array_merge_recursive( $actions, $this->action_options );

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