/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */
var Install = {
    importFixtures: null,
    selectDatabaseField: null,
    resizeContainer: null
};

Install.onDocumentReady = function( event ) {

    if(typeof Install.importRoutes != "undefined"){
        Install.importFixtures = new ImportFixtures(Install.importRoutes);
    }

    if ($("#formDatabase").length) {
        Install.selectDatabaseField = new SelectDatabaseField();
    }

    Install.resizeContainer = new resizeContainer();

};

/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready(Install.onDocumentReady);