<?php
    namespace unique\yii2vue\assets;

    use yii\web\AssetBundle;

    class VueAssets extends AssetBundle {

        public function init() {

            parent::init();

            if ( YII_DEBUG ) {

                $this->js[] = '//unpkg.com/vue@3.2.29/dist/vue.global.js';
            } else {

                $this->js[] = '//unpkg.com/vue@3.2.29/dist/vue.global.prod.js';
            }
        }
    }