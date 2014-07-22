/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */
var Rozier = {};

Rozier.onDocumentReady = function( event ) {
	/*
	 * Store Rozier configuration
	 */
	for( var index in temp ){
		Rozier[index] = temp[index];
	}

	new DocumentWidget();

	$('.root-tree').on('nestable-change', Rozier.onNestableNodeTreeChange );
	
};
Rozier.onNestableNodeTreeChange = function (event, element) {
	console.log("Node: "+element.data('node-id'));

	var node_id = parseInt(element.data('node-id'));
	var parent_node_id = parseInt(element.parents('li').first().data('node-id'));

	var postData = {
		_token: Rozier.ajaxToken,
		_action: 'updatePosition',
		nodeId: node_id
	};

	/*
	 * Get node siblings id to compute new position
	 */
	if (element.next().length) {
		postData.nextNodeId = parseInt(element.next().data('node-id'));
	}
	else if(element.prev().length) {
		postData.prevNodeId = parseInt(element.prev().data('node-id'));
	}

	/*
	 * When dropping to route
	 * set parentNodeId to NULL
	 */
	if(isNaN(parent_node_id)){
		parent_node_id = null;
	}
	postData.newParent = parent_node_id;

	console.log(postData);
	$.ajax({
		url: Rozier.routes.nodeAjaxEdit.replace("%node_id%", node_id),
		type: 'POST',
		dataType: 'json',
		data: postData
	})
	.done(function( data ) {
		console.log(data);
		$.UIkit.notify({
			message : data.responseText,
			status  : data.status,
			timeout : 3000,
			pos     : 'top-center'
		});

	})
	.fail(function( data ) {
		console.log(data);
	})
	.always(function() {
		console.log("complete");
	});
};


/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready(Rozier.onDocumentReady);