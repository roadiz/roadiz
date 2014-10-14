/**
 *
 */
var DocumentWidget = function () {
	var _this = this;

	_this.$widgets = $('[data-document-widget]');
	_this.$nestables = $('.documents-widget-nestable');
	_this.$toggleExplorerButtons = $('[data-document-widget-toggle-explorer]');
	_this.$unlinkDocumentButtons = $('[data-document-widget-unlink-document]');

	_this.init();
};
DocumentWidget.prototype.$explorer = null;
DocumentWidget.prototype.$widgets = null;
DocumentWidget.prototype.$toggleExplorerButtons = null;
DocumentWidget.prototype.$unlinkDocumentButtons = null;
DocumentWidget.prototype.$nestables = null;
DocumentWidget.prototype.init = function() {
	var _this = this;

	var changeProxy = $.proxy(_this.onNestableDocumentWidgetChange, _this);
	_this.$nestables.on('nestable-change', changeProxy);

	_this.$toggleExplorerButtons.on('click', $.proxy(_this.onExplorerToggle, _this));
	_this.$unlinkDocumentButtons.on('click', $.proxy(_this.onUnlinkDocument, _this));
};

/**
 * Update document widget input values after being sorted.
 *
 * @param  {[type]} event   [description]
 * @param  {[type]} element [description]
 * @return {void}
 */
DocumentWidget.prototype.onNestableDocumentWidgetChange = function(event, element) {
	var _this = this;

	console.log("Document: "+element.data('document-id'));

	var nestable = element.parent();
	var inputName = 'source['+nestable.data('input-name')+']';
	nestable.find('li').each(function (index) {
		$(this).find('input').attr('name', inputName+'['+index+']');
	});
};

/**
 * Create document explorer.
 *
 * @param  {[type]} event [description]
 * @return false
 */
DocumentWidget.prototype.onExplorerToggle = function(event) {
	var _this = this;

	if (_this.$explorer === null) {

		_this.$toggleExplorerButtons.addClass('uk-active');

		var ajaxData = {
			'_action':'toggleExplorer',
			'_token': Rozier.ajaxToken
		};

		$.ajax({
			url: Rozier.routes.documentsAjaxExplorer,
			type: 'get',
			dataType: 'json',
			data: ajaxData
		})
		.success(function(data) {
			console.log(data);
			console.log("success");

			if (typeof data.documents != "undefined") {

				var $currentNestable = $($(event.currentTarget).parents('.documents-widget')[0]).find('.documents-widget-nestable');
				_this.createExplorer(data, $currentNestable);
			}
		})
		.fail(function(data) {
			console.log(data.responseText);
			console.log("error");
		});
	}
	else {
		_this.$toggleExplorerButtons.removeClass('uk-active');
		_this.$explorer.removeClass('visible');
		_this.$explorer.one('transitionend webkitTransitionEnd mozTransitionEnd msTransitionEnd', function(event) {
			/* Act on the event */
			_this.$explorer.remove();
			_this.$explorer = null;
		});
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
DocumentWidget.prototype.createExplorer = function(data, $originWidget) {
	var _this = this;
	console.log($originWidget);
	var changeProxy = $.proxy(_this.onNestableDocumentWidgetChange, _this);

	$("body").append('<div class="document-widget-explorer"><ul class="uk-nestable"></ul></div>');
	_this.$explorer = $('.document-widget-explorer');
	var $nestable = _this.$explorer.find('.uk-nestable');

	for (var i = 0; i < data.documents.length; i++) {
		var doc = data.documents[i];
		$nestable.append(doc.html);
	}

	$nestable.find('li').each (function (index, element) {
		var $link = $(element).find('.link-button');
		if($link.length){
			$link.on('click', function (event) {

				var $object = $(event.currentTarget).parent();
				$object.appendTo($originWidget);

				var inputName = 'source['+$originWidget.data('input-name')+']';
				$originWidget.find('li').each(function (index, element) {
					$(element).find('input').attr('name', inputName+'['+index+']');
				});

				return false;
			});
		}
	});

	window.setTimeout(function () {
		_this.$explorer.addClass('visible');
	}, 0);
};