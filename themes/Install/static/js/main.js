/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */
var Install = {
    importFixtures: null,
    selectDatabaseField: null,
    resizeContainer: null,
    importNodeType: null

};

Install.onDocumentReady = function( event ) {
    
    Install.resizeContainer = new resizeContainer();

    if(typeof Install.importRoutes != "undefined"){
        Install.importFixtures = new ImportFixtures(Install.importRoutes);
    }

    if ($("#databaseForm").length) {
        Install.selectDatabaseField = new SelectDatabaseField();
    }

    if (typeof Install.importNodeTypeRoutes != "undefined"){
        Install.importNodeType = new ImportNodeType(Install.importNodeTypeRoutes);
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