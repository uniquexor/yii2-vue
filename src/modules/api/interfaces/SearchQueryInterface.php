<?php
    namespace unique\yii2vue\modules\api\interfaces;

    use yii\db\QueryInterface;

    interface SearchQueryInterface {

        public function getSearchQuery(): QueryInterface;
    }