<?php
    namespace unique\yii2vue\assets;

    use yii\helpers\ArrayHelper;
    use yii\web\AssetBundle;
    use yii\web\View;

    class Yii2VueComponentsAssets extends AssetBundle {

        public $sourcePath = __DIR__ . '/vue';

        public $js = [
            'js/components/loader.js',
            'js/components/relation.js',
            'js/components/models-manager-register.js',
            'js/components/models-manager.js',
            'js/components/model.js',
        ];

        public $jsOptions = [
            'position' => View::POS_HEAD
        ];

        public $use_vue_dependancy = null;

        public function init() {

            if ( $this->use_vue_dependancy === null ) {

                $this->use_vue_dependancy = ArrayHelper::getValue( \Yii::$app->params, 'use_vue_dependancy', true );
            }

            if ( $this->use_vue_dependancy ) {

                $this->depends[] = VueAssets::class;
            }

            parent::init();
        }
    }