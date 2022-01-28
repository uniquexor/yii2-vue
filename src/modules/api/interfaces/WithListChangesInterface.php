<?php
    namespace unique\yii2vue\modules\api\interfaces;

    use unique\yii2vue\modules\api\components\ListChanges;

    /**
     * Provides change tracking functionality.
     */
    interface WithListChangesInterface {

        /**
         * Sets ListChanges object
         * @param ListChanges $list_changes
         */
        public function setListChanges( ListChanges $list_changes );

        /**
         * Returns previously set ListChanges object.
         * @return ListChanges
         */
        public function getListChanges(): ListChanges;
    }