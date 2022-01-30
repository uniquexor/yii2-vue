<?php
    namespace unique\yii2vue\modules\api\components;

    use yii\base\Model;
    use yii\db\ActiveRecord;

    /**
     * Provides change tracking functionality. Changes are grouped by a model's class and it's primary ID.
     */
    class ListChanges {

        /**
         * Structure:
         * [
         *      string $class => [
         *          int|string $list_id => [
         *              'id' => mixed primary key value,
         *              string $field_name => mixed $new_value,
         *              ...
         *          ],
         *          // ...or...
         *          int|string $list_id => null,        // meaning record has been deleted.
         *          ...
         *      ],
         *      ...
         * ]
         * @var array
         */
        protected array $list_changes = [];

        /**
         * Add list changes.
         * @param string $class - Class of the model that the changes belong to
         * @param int $list_id - Primary key of the model
         * @param array $attrs - attribute-value pairs of the new data.
         */
        public function addListChange( string $class, int $list_id, array $attrs ) {

            if ( !isset( $this->list_changes[ $class ][ $list_id ] ) ) {

                $this->list_changes[ $class ][ $list_id ] = [];
            }

            // @todo Probably should do something about this hardcoded id...
            $this->list_changes[ $class ][ $list_id ] += [ 'id' => $list_id ] + $attrs;
        }

        /**
         * Marks the given primary key as deleted from the specified class.
         * @param string $class - Class of the model that the changes belong to
         * @param int $list_id - Primary key of the model that was deleted.
         */
        public function markDeleted( string $class, int $list_id ) {

            $this->list_changes[ $class ][ $list_id ] = null;
        }

        /**
         * Adds all dirty attributes of the model to the changes list.
         * @param ActiveRecord $model
         */
        public function addByModel( ActiveRecord $model ) {

            $this->addListChange( get_class( $model ), (int) $model->getPrimaryKey(), $model->getDirtyAttributes() );
        }

        /**
         * Returns changes.
         * Structure: {@see $list_changes}
         * @return array
         */
        public function getListChanges(): array {

            return $this->list_changes;
        }

        /**
         * Merge the given changes to `this` changes object.
         * @param ListChanges $list_changes
         */
        public function merge( ListChanges $list_changes ) {

            foreach ( $list_changes->getListChanges() as $class => $changes_list ) {

                foreach ( $changes_list as $list_id => $changes ) {

                    if ( $changes === null ) {

                        $this->markDeleted( $class, $list_id );
                    } else {

                        $this->addListChange( $class, $list_id, $changes );
                    }
                }
            }
        }
    }