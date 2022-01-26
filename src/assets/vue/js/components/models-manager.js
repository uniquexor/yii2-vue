class ModelsManager {

    access_token;

    url_create;
    url_update;
    url_delete;
    url_list;

    endpoint_model_class;
    model_class;
    model_id = 'id';
    is_loading = false;

    data = {};

    constructor( props ) {

        for ( let key in props ) {

            this[ key ] = props[ key ];
        }

        ModelsManagerRegister.addModelsManager( this );
    }

    reloadData() {

        if ( !this.url_list ) {

            throw 'No `url_list` specified.';
        }

        this.is_loading = true;
        let _this = this;

        return $.ajax( {
            url: this.url_list,
            method: 'get',
            dataType: 'json',
            beforeSend: function ( xhr ) {

                if ( _this.access_token ) {

                    xhr.setRequestHeader( 'Authorization', 'Basic ' + btoa( _this.access_token + ':' ) );
                }
            },
        } ).then(
            function ( data ) {

                _this.is_loading = false;

                if ( data ) {

                    _this.refreshData( data );
                }
            },
            function ( jqXhr, status, status_text ) {

                _this.is_loading = false;
                throw status + ': ' + status_text;
            }
        )
    }

    refreshData( new_data ) {

        let used_keys = {};

        for ( let i in new_data ) {

            used_keys[ i ] = i;
            if ( this.data[ i ] ) {

                this.data[ i ].updateData( new_data[ i ] );
            } else {

                new_data[ i ] = $.extend( { is_new_record : false }, new_data[ i ] );

                let obj = Reflect.construct( this.model_class, [ this.models_manager, new_data[i] ] );
                Vue.set( this.data, i, obj );
            }
        }

        for ( let i in this.data ) {

            if ( !used_keys[ i ] ) {

                Vue.delete( this.data, i );
            }
        }
    }

    /**
     * Suupdate'ina duomenis data masyve pagal paduotus.
     * Paduoti duomenys turi būti sugrupuoti pagal tą patį ID kaip ir this.data
     * @param {array} props
     */
    updateData( props ) {

        for ( let id in props ) {

            if ( props[id] === null ) {

                Vue.delete( this.data, id );
            } else {

                for ( let field in props[ id ] ) {

                    this.data[ id ][ field ] = props[ id ][ field ];
                }
            }
        }
    }

    setData( models, new_records ) {

        if ( typeof new_records === "undefined" ) {

            new_records = false;
        }

        let data = {};
        let class_name = this.model_class;

        for ( let i in models ) {

            if ( typeof( models[ i ].is_new_record ) === 'undefined' ) {

                models[ i ].is_new_record = new_records;
            }

            data[ models[i][ this.model_id ] ] = Reflect.construct( class_name, [ this, models[i] ] );
        }

        this.data = data;

        return this;
    }

    addItem( item, new_record ) {

        if ( typeof new_record === 'undefined' ) {

            new_record = false;
        }

        item.is_new_record = new_record;

        Vue.set( this.data, item[ this.model_id ], item );
    }

    static handleUnsuccessfulRequest( model, data, default_key ) {

        if ( data.data.name ) {

            Vue.set( model.errors, default_key, data.data.name + (data.data.message ? ': ' + data.data.message : '') );
        } else {

            for ( let key in data.data ) {

                let field = data.data[ key ].field;

                if ( field.indexOf( '.' ) !== -1 ) {

                    let keys = field.split( '.' );
                    let relation = keys.shift();
                    let relations = model.relations();

                    if ( typeof( relations[ relation ] ) !== 'undefined' && relations[ relation ].constructor.name === 'Relation' ) {

                        let o = { success: data.success, data: [] };

                        if ( relations[ relation ].type === Relation.TYPE_HAS_MANY ) {

                            let relation_key = keys.shift();
                            o.data.push( { field: keys.join( '.' ), message: data.data[ key ].message } );
                            this.handleUnsuccessfulRequest( model[ relation ][ relation_key ], o );
                        } else {

                            o.data.push( { field: keys.join( '.' ), message: data.data[ key ].message } );
                            this.handleUnsuccessfulRequest( model[ relation ], o );
                        }
                    } else {

                        console.error( 'Model has no relation named `' + relation + '`.', model );
                    }
                } else {

                    Vue.set( model.errors, field, data.data[ key ].message );
                }
            }
        }
    }

    createUrl( url, data ) {

        if ( !url ) {

            throw 'URL cannot be empty.';
        }

        if ( !data || !Object.keys( data ).length ) {

            return url;
        }

        if ( url.indexOf ( '?' ) === -1 ) {

            url += '?';
        }

        for ( let key in data ) {

            url += '&' + key + '=' + encodeURIComponent( data[ key ] );
        }

        return url;
    }

    removeItem( item_id, remove_from_data ) {

        if ( typeof remove_from_data === 'undefined' ) {

            remove_from_data = true;
        }

        let item = item_id;
        if ( !( item instanceof Model ) ) {

            item = this.data[ item_id ];
        }

        let data = {};
        if ( item.setPrimaryKeys ) {

            item.setPrimaryKeys( data )
        } else {

            data[ this.model_id ] = item[ this.model_id ];
        }

        let url = this.createUrl( this.url_delete, data );

        let _this = this;

        return this.request( 'DELETE', url, data, item )
            .then(
                function ( data ) {

                    if ( data.success && remove_from_data ) {

                        Vue.delete( _this.data, item_id );
                    } else {

                        ModelsManager.handleUnsuccessfulRequest( item, data, _this.model_id );
                    }

                    return data;
                },
                function ( jqXhr, status, status_text ) {

                    Vue.set( item.errors, _this.model_id, status + ': ' + status_text );
                }
            );
    }

    saveItem( item, request_data ) {

        let data = $.extend( {}, request_data, item.toBody() );
        let _this = this;
        let method = item.is_new_record ? 'POST' : 'PUT';
        let url = this.url_create;
        if ( !item.is_new_record ) {

            url = this.createUrl( this.url_update, { id: item.getPrimaryKey() } );
        }

        return this.request( method, url, data, item )
            .then(
                function ( data ) {

                    if ( !data.success ) {

                        ModelsManager.handleUnsuccessfulRequest( item, data, _this.model_id );
                    } else {

                        item.is_new_record = false;
                        if ( data.data['_list_changes_'] ) {

                            ModelsManagerRegister.updateListChanges( data.data['_list_changes_'] );
                            Vue.delete( data.data, '_list_changes_' );
                        }

                        item.setAttributes( data.data );
                    }

                    return data;
                },
                function ( jqXhr, status, status_text ) {

                    Vue.set( item.errors, _this.model_id, status + ': ' + status_text );
                }
            );
    }

    /**
     * Įvykdo saugojimo, trinimo request'ą.
     *
     * @param {String} method
     * @param {String} url
     * @param {Object} data
     * @param {Model|Model[]|null} item
     * @returns {*}
     */
    request( method, url, data, item ) {

        let _this = this;

        /**
         * Priklausomai nuo to ar item yra vienas modelis, modelių array'us ar išvis nieko, pritaikys paduotą iteravimo funkciją.
         * @param {Model|Model[]|null} item
         * @param {Function} iterator
         */
        let iterator = function ( item, iterator ) {

            if ( !item ) {

                return;
            } else if ( !Array.isArray( item ) ) {

                iterator.call( _this, item );
            } else {

                _.forEach( item, function ( item ) {

                    iterator.call( _this, item );
                } )
            }
        }

        iterator( item, function ( /** @type {Model} */ item ) {

            item.is_loading = true;
            Vue.set( item, 'errors', {} );
        } );

        return $.ajax( {
            method: method,
            data: data,
            dataType: 'json',
            url: url,
            beforeSend: function ( xhr ) {

                if ( _this.access_token ) {

                    xhr.setRequestHeader( 'Authorization', 'Basic ' + btoa( _this.access_token + ':' ) );
                }
            },
        } )
            .always( () => {

                iterator( item, function ( /** @type {Model} */ item ) {

                    item.is_loading = false;
                    Vue.set( item, 'errors', {} );
                } );
            } );
    }
}