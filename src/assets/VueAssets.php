<?php
    namespace unique\yii2vue\assets;

    use yii\web\AssetBundle;

    class VueAssets extends AssetBundle {

        public function init() {

            parent::init();

            if ( YII_DEBUG ) {

                $this->js[] = 'https://unpkg.com/browse/vue@3.2.29/dist/vue.global.js';
            } else {

                $this->js[] = 'https://unpkg.com/browse/vue@3.2.29/dist/vue.global.prod.js';
            }
        }
    }