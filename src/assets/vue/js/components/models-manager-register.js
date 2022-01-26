class ModelsManagerRegister {

    /**
     * @type {ModelsManager[]}
     */
    static managers = {};

    /**
     * Užregistruoja ModelsManager objektą pagal endpoint'o klasę
     * @param {ModelsManager} manager
     */
    static addModelsManager( manager ) {

        if ( manager.endpoint_model_class ) {

            ModelsManagerRegister.managers[ manager.endpoint_model_class ] = manager;
        }
    }

    /**
     * Update'ina list change'us pagal atitinkamus ModelsManager'ius.
     * @param list_changes
     */
    static updateListChanges( list_changes ) {

        for ( let endpoint_class_name in list_changes ) {

            if ( !ModelsManagerRegister.managers[ endpoint_class_name ] ) {

                throw endpoint_class_name + ' is not registered with the ModelsManagerRegister';
            }

            ModelsManagerRegister.managers[ endpoint_class_name ].updateData( list_changes[ endpoint_class_name ] );
        }
    }
}