<?php
    namespace unique\yii2vue\assets;

    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use yii\web\AssetBundle;

    class VueAssetBundle extends AssetBundle {

        public string $applications_path = 'js/apps/';
        public string $mixins_path = 'js/mixins/';
        public string $components_path = 'js/components/';
        public string $models_path = 'js/models/';
        public string $css_path = 'css/';

        protected function loadPath( string $path, bool $recursively = true ) {

            $path = realpath( \Yii::getAlias( $path ) );
            $assets_path = $this->sourcePath ? realpath( \Yii::getAlias( $this->sourcePath ) ) : realpath( \Yii::getAlias( $this->basePath ) );

            if ( $this->sourcePath && strpos( $path, $assets_path ) !== 0 ) {

                throw new \Exception( 'A given path must be within the sourcePath or sourcePath must be NULL to use basePath.' );
            } elseif ( $this->basePath && strpos( $path, $assets_path ) !== 0 ) {

                throw new \Exception( 'A given path must be within either sourcePath or basePath.' );
            } elseif ( !$this->sourcePath && !$this->basePath ) {

                throw new \Exception( 'Either sourcePath or basePath must be specified.' );
            }

            $relative_path = str_replace( '\\', '/', substr( $path, strlen( $assets_path ) ) );
            if ( strlen( $relative_path ) > 1 && $relative_path[0] === '/' ) {

                $relative_path = substr( $relative_path, 1 );
            }

            if ( !str_ends_with( $relative_path, '/' ) ) {

                $relative_path .= '/';
            }

            if ( $recursively ) {

                $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) );
            } else {

                $iterator = new \IteratorIterator( new \DirectoryIterator( $path ) );
            }

            foreach ( $iterator as $file ) {

                /**
                 * @var \DirectoryIterator $file
                 */

                if ( $file->isDir() ) {

                    continue;
                }

                $file = substr( $file->getRealPath(), strlen( $path ) + 1 );
                $file = str_replace( '\\', '/', $file );

                if ( str_ends_with( $file, '.js' ) ) {

                    $this->js[ $relative_path . $file ] = $relative_path . $file;
                } elseif ( str_ends_with( $file, '.css' ) ) {

                    $this->css[ $relative_path . $file ] = $relative_path . $file;
                }
            }
        }

        public function addApplication( string $name ) {

            $this->js[ $this->applications_path . $name . '.js' ] = $this->applications_path . $name . '.js';
            return $this;
        }

        public function addComponent( string $name ) {

            $this->js[ $this->components_path . $name . '.js' ] = $this->components_path . $name . '.js';
            return $this;
        }

        public function addMixin( string $name ) {

            $this->js[ $this->mixins_path . $name . '.js' ] = $this->mixins_path . $name . '.js';
            return $this;
        }

        public function addModel( string $name ) {

            $this->js[ $this->models_path . $name . '.js' ] = $this->models_path . $name . '.js';
            return $this;
        }

        public function addCss( string $name, ?string $package = null ) {

            $path = $this->css_path . ( $package ? $package . '/' : '' ) . $name . '.css';
            $this->css[ $path ] = $path;
            return $this;
        }
    }