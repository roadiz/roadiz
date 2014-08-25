/**
 * 
 */
var DocumentWidget = function () {
	var _this = this;

	_this.widgets = $('[data-document-widget]');
	_this.toggleExplorerButtons = $('[data-document-widget-toggle-explorer]');
	_this.unlinkDocumentButtons = $('[data-document-widget-unlink-document]');

	_this.init();
};
DocumentWidget.prototype.explorer = null;
DocumentWidget.prototype.widgets = null;
DocumentWidget.prototype.toggleExplorerButtons = null;
DocumentWidget.prototype.unlinkDocumentButtons = null;
DocumentWidget.prototype.init = function() {
	var _this = this;

	$('.documents-widget-nestable').on('nestable-change', $.proxy(_this.onNestableDocumentWidgetChange, _this) );
	_this.toggleExplorerButtons.on('click', $.proxy(_this.onExplorerToggle, _this));
	_this.unlinkDocumentButtons.on('click', $.proxy(_this.onUnlinkDocument, _this));
};

/**
 * Update document widget input values after being sorted
 * @param  {[type]} event   [description]
 * @param  {[type]} element [description]
 * @return {[type]}         [description]
 */
DocumentWidget.prototype.onNestableDocumentWidgetChange = function (event, element) {
	var _this = this;

	console.log("Document: "+element.data('document-id'));

	var nestable = element.parent();
	var inputName = 'source['+nestable.data('input-name')+']';
	nestable.find('li').each(function (index) {
		$(this).find('input').attr('name', inputName+'['+index+']');
	});
};

/**
 * Create document explorer
 * 
 * @param  {[type]} event [description]
 * @return false
 */
DocumentWidget.prototype.onExplorerToggle = function(event) {
	var _this = this;

	if (_this.explorer === null) {

		_this.toggleExplorerButtons.addClass('uk-active');

		$.ajax({
			url: Rozier.routes.documentsAjaxExplorer,
			type: 'GET',
			dataType: 'json',
			data: {
				_action:'toggleExplorer',
				_token: Rozier.ajaxToken
			},
		})
		.done(function(data ) {
			console.log(data);
			_this.createExplorer(data);
			console.log("success");
		})
		.fail(function(data ) {
			console.log(data);
			console.log("error");
		})
		.always(function() {
			console.log("complete");
		});
	}
	else {
		_this.toggleExplorerButtons.removeClass('uk-active');
		_this.explorer.remove();
		_this.explorer = null;
	}

	return false;
};

DocumentWidget.prototype.onUnlinkDocument = function( event ) {
	var _this = this;

	var $element = $(event.currentTarget);

	$element.parent('li').remove();
	$element.parents().find('.documents-widget-nestable').first().trigger('nestable-change');

	return false;
};

/**
 * Populate explorer with documents thumbnails
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
DocumentWidget.prototype.createExplorer = function( data ) {
	var _this = this;

	$("body").append('<div class="document-widget-explorer"><ul class="uk-nestable" data-uk-nestable="{group:\'documents-widget\',maxDepth:1}"></ul></div>');
	_this.explorer = $('.document-widget-explorer');

	for (var i = 0; i < data.documents.length; i++) {
		var doc = data.documents[i];
		_this.explorer.find('ul').append(doc.html);
	}
};;// Avoid `console` errors in browsers that lack a console.
(function() {
    var method;
    var noop = function () {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());

// Place any jQuery/helper plugins in here.
;/*
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

	$('.nodetree-widget .root-tree').on('nestable-change', Rozier.onNestableNodeTreeChange );
	$('.tagtree-widget .root-tree').on('nestable-change', Rozier.onNestableTagTreeChange );

	/*
	 * TEMP
	 */
	$('[data-uk-pagination]').on('uk-select-page', function(e, pageIndex){
	    document.location.href = document.location.origin + document.location.pathname + '?page='+(pageIndex+1);
	});
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