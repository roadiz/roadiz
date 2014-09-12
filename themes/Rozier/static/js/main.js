/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */
var Rozier = {};

Rozier.searchNodesSourcesDelay = null;

Rozier.onDocumentReady = function( event ) {
	/*
	 * Store Rozier configuration
	 */
	for( var index in temp ){
		Rozier[index] = temp[index];
	}

	new DocumentWidget();

	// TREES
	$('.nodetree-widget .root-tree').on('nestable-change', Rozier.onNestableNodeTreeChange );
	$('.tagtree-widget .root-tree').on('nestable-change', Rozier.onNestableTagTreeChange );
	// Search node
	$("#nodes-sources-search-input").on('keyup', Rozier.onSearchNodesSources);
	// Minify trees panel toggle button
	$('#minify-tree-panel-button').on('click', Rozier.toggleTreesPanel);

	// Switch checkboxes
	$(".rz-boolean-checkbox").bootstrapSwitch();

	Rozier.centerVerticalObjects();
	Rozier.parseActionSaveButtons();
};


/*
 * Center vetically every DOM objects that have
 * the data-vertical-center attribute
 */
Rozier.centerVerticalObjects = function() {
	var $objects = $(".rz-vertical-align");

	$objects.each(function (index, element) {
		$($objects[index]).css({
			'top': '50%',
			'margin-top': (element.offsetHeight/-2)+'px'
		});
	});
};

/*
 * You can add automatically form button to actions-menus
 * Just add them to the .rz-action-save class and use the data-action-save
 * attribute to point form ID to submit.
 */
Rozier.parseActionSaveButtons = function(){

	var $button = $($(".rz-action-save").get(0));
	var $actionMenu = $($('.actions-menu').get(0));

	if ($button.length &&
		$actionMenu.length) {
		var formToSave = $($button.attr('data-action-save'));
		if (formToSave.length) {
			$button.prependTo($actionMenu);
			$button.on('click', function (event) {
				formToSave.submit();
			});
		}
	}
};

Rozier.toggleTreesPanel = function (event) {
	var _this = this;

	$('#main-trees').toggleClass('minified');

	$('#minify-tree-panel-button').toggleClass('uk-icon-minus');
	$('#minify-tree-panel-button').toggleClass('uk-icon-plus');
	$('#minify-tree-panel-area').toggleClass('tree-panel-hidden');
	return false;
};

Rozier.toggleUserPanel = function (event) {
	var _this = this;

	$('#user-panel').toggleClass('minified');

	return false;
};

/**
 * Handle ajax search node source.
 *
 * @param event
 */
Rozier.onSearchNodesSources = function (event) {

	var $input = $(event.currentTarget);

	if ($input.val().length > 2) {
		clearTimeout(Rozier.searchNodesSourcesDelay);
		Rozier.searchNodesSourcesDelay = setTimeout(function () {
			var postData = {
				_token: Rozier.ajaxToken,
				_action:'searchNodesSources',
				searchTerms: $input.val()
			};
			console.log(postData);
			$.ajax({
				url: Rozier.routes.searchNodesSourcesAjax,
				type: 'POST',
				dataType: 'json',
				data: postData
			})
			.done(function( data ) {
				console.log(data);

				if (typeof data.data != "undefined" &&
					data.data.length > 0) {

					$results = $('#nodes-sources-search-results');
					$results.empty();

					for(var i in data.data) {
						$results.append('<li><a href="'+data.data[i].url+
								'"><span class="title">'+data.data[i].title+
						    	'</span> <span class="type">'+data.data[i].typeName+
						    	'</span></a></li>');
					}
				}
			})
			.fail(function( data ) {
				console.log(data);
			})
			.always(function() {
				console.log("complete");
			});
		}, 300);
	}
};

/**
 *
 * @param  Event event
 * @param  jQueryNode element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableNodeTreeChange = function (event, element, status) {

	console.log("Node: "+element.data('node-id')+ " status : "+status);

	/*
	 * If node removed, do not do anything, the other nodeTree will be triggered
	 */
	if (status == 'removed') {
		return false;
	}

	var node_id = parseInt(element.data('node-id'));
	var parent_node_id = parseInt(element.parents('ul').first().data('parent-node-id'));

	/*
	 * User dragged node inside itself
	 * It will destroy the Internet !
	 */
	if (node_id === parent_node_id) {
		console.log("You cannot move a node inside itself!");
		alert("You cannot move a node inside itself!");
		window.location.reload();
		return false;
	}

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
		url: Rozier.routes.nodeAjaxEdit.replace("%nodeId%", node_id),
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


/**
 *
 * @param  Event event
 * @param  jQueryTag element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableTagTreeChange = function (event, element, status) {
	console.log("Tag: "+element.data('tag-id')+ " status : "+status);

	/*
	 * If tag removed, do not do anything, the other tagTree will be triggered
	 */
	if (status == 'removed') {
		return false;
	}

	var tag_id = parseInt(element.data('tag-id'));
	var parent_tag_id = parseInt(element.parents('ul').first().data('parent-tag-id'));

	/*
	 * User dragged tag inside itself
	 * It will destroy the Internet !
	 */
	if (tag_id === parent_tag_id) {
		console.log("You cannot move a tag inside itself!");
		alert("You cannot move a tag inside itself!");
		window.location.reload();
		return false;
	}

	var postData = {
		_token: Rozier.ajaxToken,
		_action: 'updatePosition',
		tagId: tag_id
	};

	/*
	 * Get tag siblings id to compute new position
	 */
	if (element.next().length) {
		postData.nextTagId = parseInt(element.next().data('tag-id'));
	}
	else if(element.prev().length) {
		postData.prevTagId = parseInt(element.prev().data('tag-id'));
	}

	/*
	 * When dropping to route
	 * set parentTagId to NULL
	 */
	if(isNaN(parent_tag_id)){
		parent_tag_id = null;
	}
	postData.newParent = parent_tag_id;

	console.log(postData);
	$.ajax({
		url: Rozier.routes.tagAjaxEdit.replace("%tagId%", tag_id),
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