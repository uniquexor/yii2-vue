/**
 * All created `ModelsManager` instances are automatically registered with a static instance
 * of `ModelsManagerRegister`. `ModelsManagerRegister` can be used to update many
 * `ModelsManger`s at the same time. This is convenient, when you are performing an API call,
 * that updates not only the model it's been given, but other models as well.
 *
 * In this case, an API call can return `_list_changes_`, to update other models. For example,
 * imagine we are creating a new Car model, which updates Stats model to reflect the number
 * of cars of the same make_id.
 *
 * After receiving a response, we could call `ModelsManagerRegister.updateListChanges( data )`, which, providing
 * we have created ModelsManager for both `Car` and `Stats` models, would update data in them.
 */
class ModelsManagerRegister {

    /**
     * All models managers, indexed by {@see ModelsManager.endpoint_model_class} property.
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
     * Updates the data managers to reflect data in list_changes.
     * list_changes needs to have the following structure:
     * {
     *     [ ModelsManager.endpoint_model_class ]: {
     *         [ Model's id, by which it is indexed in ModelsManager ]: {
     *             [ Updated property name ]: [ Updated property value ],
     *             ...
     *         }
     *     }
     * }
     * @param {Object} list_changes
     */
    static updateListChanges( list_changes ) {

        for ( let endpoint_class_name in list_changes ) {

            if ( !ModelsManagerRegister.managers[ endpoint_class_name ] ) {

                throw endpoint_class_name + ' is not registered with the ModelsManagerRegister';
            }

            ModelsManagerRegister.managers[ endpoint_class_name ].updateData( list_changes[ endpoint_class_name ], false );
        }
    }
}