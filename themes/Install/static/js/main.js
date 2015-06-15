/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */
var Install = {
    importFixtures: null,
    selectDatabaseField: null,
    resizeContainer: null,
    import: null
};

Install.onDocumentReady = function( event ) {

    Install.resizeContainer = new resizeContainer();

    if(typeof Install.importRoutes != "undefined"){
        Install.importFixtures = new ImportFixtures(Install.importRoutes);
    }

    if ($("#databaseForm").length) {
        Install.selectDatabaseField = new SelectDatabaseField();
    }

    if (typeof Install.importThemeRoutes != "undefined"){
        Install.import = new Import(Install.importThemeRoutes);
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
