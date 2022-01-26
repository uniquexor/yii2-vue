/**
 * Models Manager allows to create, update and store model data (not unlike a database table)
 */
class ModelsManager {

    /**
     * An access An access token, that, if provided, will be sent with API requests
     * as a basic Auth header. The string will be sent as a username.
     * @type {string|undefined}
     */
    access_token;

    /**
     * A URL that will be used to create new Models
     * @type {string|undefined}
     */
    url_create;

    /**
     * A URL that will be used to update Models.
     * @type {string|undefined}
     */
    url_update;

    /**
     * A URL that will be used to delete Models
     * @type {string|undefined}
     */
    url_delete;

    /**
     * A URL that will be used to reload data
     * @type {string|undefined}
     */
    url_list;

    /**
     * A php class name of the model used in the backend.
     * (can be used to update many manager data at once {@see ModelsManagerRegister})
     *
     * @type {string|undefined}
     */
    endpoint_model_class;

    /**
     * A JS model's class name, that will be created by this Manager
     * @type {string|undefined}
     */
    model_class;

    /**
     * A property, which will be used to index models by.
     * @type {string}
     */
    model_id = 'id';

    /**
     * Indicates weather the item is being loaded. Can be used to view a loading screen or something...
     * @type {boolean}
     */
    is_loading = false;

    /**
     * A data store for the Model's manager. Contains all data indexed by each Model's {@see model_id} value.
     * @type {Model[]}
     */
    data = {};

    /**
     * Constructs a ModelsManager object.
     * @param {Object} props - An index: value pairs of properties to be assigned.
     */
    constructor( props ) {

        for ( let key in props ) {

            this[ key ] = props[ key ];
        }

        ModelsManagerRegister.addModelsManager( this );
    }

    /**
     * Used to reload data in the manager. {@see url_list} must be specified.
     * @returns {Promise}
     */
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

                return data;
            },
            function ( jqXhr, status, status_text ) {

                _this.is_loading = false;
                throw status + ': ' + status_text;
            }
        )
    }

    /**
     * If only_update is false or not set, this will iterate all the models managed by this manager and update their
     * properties to reflect given new data. If models need to be created - they will be,
     * if need to be deleted - they will be. Relations are also updated.
     *
     * If only_update is true, then current models will only be updated with properties from new_data.
     * New models will not be created and old models will not be deleted.
     *
     * @param {Object} new_data - New data to be set. Must be indexed using the same keys as `ModelsManager.data`
     * @param {boolean} delete_missing - If true - will delete record not in the new_data Object, otherwise - will only create new and update old.
     */
    updateData( new_data, delete_missing = true ) {

        let used_keys = {};

        for ( let i in new_data ) {

            used_keys[ i ] = i;
            if ( this.data[ i ] ) {

                this.data[ i ].setProperties( new_data[ i ], delete_missing );
            } else if ( !only_update ) {

                new_data[ i ] = $.extend( { is_new_record : false }, new_data[ i ] );

                let obj = Reflect.construct( this.model_class, [ this.models_manager, new_data[i] ] );
                Vue.set( this.data, i, obj );
            }
        }

        if ( !only_update ) {

            for ( let i in this.data ) {

                if ( !used_keys[ i ] ) {

                    Vue.delete( this.data, i );
                }
            }
        }
    }

    /**
     * Wipes old data from the Manager and sets new data, by creating new models.
     * @param {Object[]} new_data - Data to be converted in to models.
     * @param {boolean} new_records - If new_data does not contain `is_new_record` attribute, then this value will be used.
     * @returns {ModelsManager}
     */
    setData( new_data, new_records = false ) {

        let data = {};
        let class_name = this.model_class;

        for ( let i in new_data ) {

            if ( typeof( new_data[ i ].is_new_record ) === 'undefined' ) {

                new_data[ i ].is_new_record = new_records;
            }

            data[ new_data[i][ this.model_id ] ] = Reflect.construct( class_name, [ this, new_data[i] ] );
        }

        this.data = data;

        return this;
    }

    /**
     * Adds a model to the data store.
     * @param {Model} model - Model to be added.
     * @param {boolean} new_record - The `is_new_record` attribute of the model will be set to this value.
     */
    addModel( model, new_record = false ) {

        model.is_new_record = new_record;

        Vue.set( this.data, model[ this.model_id ], model );
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

    /**
     * Adds query data to the given URL.
     * @param {string} url
     * @param {object} query_data
     * @returns {string}
     */
    formUrlWithQueryData( url, query_data ) {

        if ( !url ) {

            throw 'URL cannot be empty.';
        }

        if ( !query_data || !Object.keys( query_data ).length ) {

            return url;
        }

        if ( url.indexOf ( '?' ) === -1 ) {

            url += '?';
        }

        for ( let key in query_data ) {

            url += '&' + key + '=' + encodeURIComponent( query_data[ key ] );
        }

        return url;
    }

    /**
     * Performs a DELETE API call to the given {@see url_delete} url.
     * Models's {@see Model.setPrimaryKeys()} is used to set the id for the request.
     *
     * @param {Model|int|string} item - Model or Model's ID from the ModelsManager data store to be deleted
     * @param {boolean} remove_from_data - After a successful API call, should the data be deleted from the ModelsManager's store as well?
     * @returns {Promise}
     */
    deleteItem( item, remove_from_data = true ) {

        let item_id = item;
        if ( !( item instanceof Model ) ) {

            /**
             * @type {Model}
             */
            item = this.data[ item ];
        } else {

            item_id = item[ this.model_id ];
        }

        let data = {};
        if ( item.setPrimaryKeys ) {

            item.setPrimaryKeys( data )
        } else {

            data[ this.model_id ] = item[ this.model_id ];
        }

        let url = this.formUrlWithQueryData( this.url_delete, data );

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

    /**
     * Calls the API to create or update the given model.
     * Additional data can be passed, by providing a `request_data` object.
     * It will be merged with the Model's data to be saved. (Model's data takes precedence).
     * @param {Model} item
     * @param {Object} request_data
     * @returns {Promise}
     */
    saveItem( item, request_data ) {

        let data = $.extend( {}, request_data, item.toBody() );
        let _this = this;
        let method = item.is_new_record ? 'POST' : 'PUT';
        let url = this.url_create;
        if ( !item.is_new_record ) {

            url = this.formUrlWithQueryData( this.url_update, { id: item.getPrimaryKey() } );
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

                        item.setProperties( data.data );
                    }

                    return data;
                },
                function ( jqXhr, status, status_text ) {

                    Vue.set( item.errors, _this.model_id, status + ': ' + status_text );
                }
            );
    }

    /**
     * Performs the request.
     *
     * @param {String} method
     * @param {String} url
     * @param {Object} data
     * @param {Model|Model[]|null} item
     * @returns {Promise}
     */
    request( method, url, data, item ) {

        let _this = this;

        /**
         * Depending on weather item is a single Model or an array of Models, will perform the given action.
         * @param {Model|Model[]|null} item
         * @param {Function} iterator
         */
        let iterator = function ( item, iterator ) {

            if ( !item ) {

                return;
            } else if ( !Array.isArray( item ) ) {

                iterator.call( _this, item );
            } else {

                for ( let it in item ) {

                    iterator.call( _this, item );
                }
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