/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */
var Install = {
    selectDatabaseField: null,
    resizeContainer: null,
    import: null
};

Install.onDocumentReady = function( event ) {

    Install.resizeContainer = new resizeContainer();

    if ($("#databaseForm").length) {
        Install.selectDatabaseField = new SelectDatabaseField();
    }

    if (typeof Install.importRoutes != "undefined"){
        Install.import = new Import(Install.importRoutes);
    }

    // Add boostrap switch to checkbox
    $(".rz-boolean-checkbox").bootstrapSwitch();

};

/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready(Install.onDocumentReady);
